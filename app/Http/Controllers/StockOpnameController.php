<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Part;
use App\Models\StockOpname;
use App\Models\StoDetail;
use App\Support\AuditLogger;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameController extends Controller
{
    private const AREA_BY_ROLE = [
        'Operator Warehouse' => [
            'area' => 'Warehouse',
            'locations' => ['Incoming Warehouse', 'Incoming Rack'],
        ],
        'Dandori' => [
            'area' => 'Dandori',
            'locations' => ['Dandori'],
        ],
        'Operator Assembly' => [
            'area' => 'Assembly',
            'locations' => ['Assembly'],
        ],
        'Operator Delivery' => [
            'area' => 'Delivery',
            'locations' => ['Outgoing Area', 'Loading Area'],
        ],
    ];

    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $roleName = $request->user()->role?->role_name;
        $areaConfig = self::AREA_BY_ROLE[$roleName] ?? null;

        abort_unless(
            $areaConfig || in_array($roleName, ['Staf Administrasi', 'Atasan'], true),
            403,
            'Role tidak memiliki akses ke Stock Opname.'
        );

        $query = StockOpname::with(['creator', 'reviewer'])
            ->withCount(['details as mismatch_count' => fn ($q) => $q->where('match_status', 'mismatch')]);

        // Operational users only see STO records for their own process area.
        // Admin and Atasan can inspect all areas.
        if ($areaConfig) {
            $query->where('sto_area', $areaConfig['area']);
        }

        $opnames = $query->orderByDesc('sto_date')->orderByDesc('sto_id')->paginate(15);

        return view('stock-opnames.index', compact('opnames', 'areaConfig', 'roleName'));
    }

    public function show(Request $request, int $id)
    {
        $opname = StockOpname::with([
            'creator',
            'reviewer',
            'recountParent',
            'recounts.creator',
            'details.part.unitOfMaterial',
        ])->findOrFail($id);

        $this->ensureCanView($request, $opname);

        return view('stock-opnames.show', compact('opname'));
    }

    public function create(Request $request)
    {
        $areaConfig = $this->areaForUser($request);
        $parts = Part::with('unitOfMaterial')->orderBy('part_number')->get();
        $systemStocks = $this->systemStocksForLocations($areaConfig['locations']);

        $recountParent = null;
        $recountId = $request->integer('recount_of');
        if ($recountId) {
            $recountParent = StockOpname::findOrFail($recountId);
            abort_unless(
                $recountParent->sto_area === $areaConfig['area']
                && $recountParent->sto_status === 'recount_requested',
                404
            );
        }

        return view('stock-opnames.create', compact('parts', 'systemStocks', 'areaConfig', 'recountParent'));
    }

    public function store(Request $request)
    {
        $areaConfig = $this->areaForUser($request);
        $validated = $request->validate([
            'sto_period' => ['required', 'string', 'max:30'],
            'sto_date' => ['required', 'date'],
            'counts' => ['required', 'array', 'min:1'],
            'counts.*.stock_actual' => ['nullable', 'numeric', 'min:0'],
            'recount_of' => ['nullable', 'integer', 'exists:stock_opnames,sto_id'],
        ]);

        $parts = Part::orderBy('part_id')->get();
        $systemStocks = $this->systemStocksForLocations($areaConfig['locations']);
        $createdStoId = null;

        DB::transaction(function () use ($request, $validated, $areaConfig, $parts, $systemStocks, &$createdStoId) {
            $recountParent = null;
            if (!empty($validated['recount_of'])) {
                $recountParent = StockOpname::lockForUpdate()->findOrFail((int) $validated['recount_of']);
                if ($recountParent->sto_area !== $areaConfig['area'] || $recountParent->sto_status !== 'recount_requested') {
                    throw ValidationException::withMessages([
                        'recount_of' => 'STO yang diminta untuk dihitung ulang sudah berubah status atau bukan milik area ini.',
                    ]);
                }
            }

            $sto = StockOpname::create([
                'sto_period' => $validated['sto_period'],
                'sto_area' => $areaConfig['area'],
                'sto_date' => $validated['sto_date'],
                'sto_status' => 'submitted',
                'created_by' => $request->user()->user_id,
                'recount_of_sto_id' => $recountParent?->sto_id,
                'import_time' => now(),
            ]);

            $savedDetails = 0;
            foreach ($parts as $part) {
                $raw = data_get($validated['counts'], $part->part_id.'.stock_actual');
                if ($raw === null || $raw === '') {
                    continue;
                }

                $system = (float) ($systemStocks[$part->part_id] ?? 0);
                $actual = (float) $raw;
                $gap = $actual - $system;
                $detail = StoDetail::create([
                    'sto_id' => $sto->sto_id,
                    'part_id' => $part->part_id,
                    'stock_system' => $system,
                    'stock_actual' => $actual,
                    'gap_quantity' => $gap,
                    'match_status' => abs($gap) < 0.0001 ? 'match' : 'mismatch',
                ]);
                $savedDetails++;

                if (abs($gap) >= 0.0001) {
                    $this->stockService->notifyRoles(
                        ['Staf Administrasi', 'Atasan'],
                        'Selisih Stock Opname terdeteksi',
                        'Area '.$sto->sto_area.', part '.$part->part_number.' memiliki selisih '.$gap.' pada STO '.$sto->sto_period.'.',
                        'warning',
                        ['sto_detail_id' => $detail->sto_detail_id]
                    );
                }
            }

            if ($savedDetails === 0) {
                throw ValidationException::withMessages([
                    'counts' => 'Isi minimal satu nilai Stock Actual sebelum mengirim Stock Opname.',
                ]);
            }

            if ($recountParent) {
                $recountParent->sto_status = 'recount_submitted';
                $recountParent->save();
            }

            $createdStoId = $sto->sto_id;
            AuditLogger::log(
                $request->user()->user_id,
                'Mengirim Stock Opname area '.$sto->sto_area.' periode '.$sto->sto_period.($recountParent ? ' sebagai STO ulang dari #'.$recountParent->sto_id : ''),
                $request
            );

            $this->stockService->notifyRoles(
                ['Atasan'],
                'Stock Opname baru menunggu persetujuan',
                'STO area '.$sto->sto_area.' periode '.$sto->sto_period.' telah dikirim oleh '.$request->user()->full_name.'.',
                'info'
            );
        });

        return redirect()->route('stock-opnames.show', $createdStoId)
            ->with('status', 'Stock Opname area '.$areaConfig['area'].' berhasil dikirim dan menunggu pemeriksaan Atasan.');
    }

    /** Atasan approves a submitted STO after checking its recorded details. */
    public function approve(Request $request, int $id)
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $opname = DB::transaction(function () use ($request, $id, $validated) {
            $sto = StockOpname::lockForUpdate()->findOrFail($id);
            $this->ensureReviewable($sto);

            $sto->sto_status = 'approved';
            $sto->reviewed_by = $request->user()->user_id;
            $sto->reviewed_at = now();
            $sto->review_note = $validated['review_note'] ?? null;
            $sto->save();

            AuditLogger::log($request->user()->user_id, 'Menyetujui STO #'.$sto->sto_id.' area '.$sto->sto_area, $request);
            $this->notifyStoOwner(
                (int) $sto->created_by,
                'Stock Opname disetujui',
                'STO area '.$sto->sto_area.' periode '.$sto->sto_period.' telah disetujui oleh Atasan.'.(!empty($validated['review_note']) ? ' Catatan: '.$validated['review_note'] : ''),
                'success'
            );

            return $sto;
        });

        return redirect()->route('stock-opnames.show', $opname->sto_id)
            ->with('status', 'Stock Opname berhasil disetujui.');
    }

    /** Atasan asks the responsible operational role to recount the same area. */
    public function requestRecount(Request $request, int $id)
    {
        $validated = $request->validate([
            'request_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'request_reason.required' => 'Alasan STO ulang wajib diisi.',
            'request_reason.min' => 'Alasan STO ulang minimal 5 karakter.',
            'request_reason.max' => 'Alasan STO ulang maksimal 500 karakter.',
        ]);

        $opname = DB::transaction(function () use ($request, $id, $validated) {
            $sto = StockOpname::lockForUpdate()->findOrFail($id);
            $this->ensureReviewable($sto);

            $sto->sto_status = 'recount_requested';
            $sto->reviewed_by = $request->user()->user_id;
            $sto->reviewed_at = now();
            $sto->review_note = $validated['request_reason'];
            $sto->save();

            AuditLogger::log(
                $request->user()->user_id,
                'Meminta STO ulang untuk STO #'.$sto->sto_id.' area '.$sto->sto_area.'. Alasan: '.$validated['request_reason'],
                $request
            );
            $this->notifyStoOwner(
                (int) $sto->created_by,
                'Stock Opname perlu dihitung ulang',
                'Atasan meminta STO ulang untuk area '.$sto->sto_area.' periode '.$sto->sto_period.'. Alasan: '.$validated['request_reason'],
                'warning'
            );

            return $sto;
        });

        return redirect()->route('stock-opnames.show', $opname->sto_id)
            ->with('status', 'Permintaan STO ulang berhasil dikirim kepada petugas area '.$opname->sto_area.'.');
    }

    private function areaForUser(Request $request): array
    {
        $roleName = $request->user()->role?->role_name;
        $config = self::AREA_BY_ROLE[$roleName] ?? null;
        abort_unless($config, 403, 'Hanya petugas operasional yang dapat membuat Stock Opname.');

        return $config;
    }

    private function ensureCanView(Request $request, StockOpname $opname): void
    {
        $roleName = $request->user()->role?->role_name;
        if (isset(self::AREA_BY_ROLE[$roleName])) {
            abort_unless($opname->sto_area === self::AREA_BY_ROLE[$roleName]['area'], 403);
            return;
        }

        abort_unless(in_array($roleName, ['Staf Administrasi', 'Atasan'], true), 403);
    }

    private function ensureReviewable(StockOpname $opname): void
    {
        if ($opname->sto_status !== 'submitted') {
            abort(409, 'STO ini tidak sedang menunggu persetujuan Atasan.');
        }
    }

    private function systemStocksForLocations(array $locationNames)
    {
        return DB::table('stocks as s')
            ->join('locations as l', 'l.location_id', '=', 's.location_id')
            ->whereIn('l.location_name', $locationNames)
            ->select('s.part_id', DB::raw('SUM(s.stock_qty) as qty'))
            ->groupBy('s.part_id')
            ->pluck('qty', 's.part_id');
    }

    private function notifyStoOwner(int $userId, string $title, string $message, string $type): void
    {
        Notification::create([
            'user_id' => $userId,
            'notification_title' => $title,
            'notification_message' => $message,
            'notification_type' => $type,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }
}
