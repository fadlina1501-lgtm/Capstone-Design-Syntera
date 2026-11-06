<?php

namespace App\Http\Controllers;

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
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $opnames = StockOpname::with('creator')
            ->withCount(['details as mismatch_count' => fn ($q) => $q->where('match_status', 'mismatch')])
            ->orderByDesc('sto_date')->orderByDesc('sto_id')->paginate(15);

        return view('stock-opnames.index', compact('opnames'));
    }

    public function show(int $id)
    {
        $opname = StockOpname::with(['creator', 'details.part.unitOfMaterial'])->findOrFail($id);

        return view('stock-opnames.show', compact('opname'));
    }

    public function create()
    {
        $parts = Part::with('unitOfMaterial')->orderBy('part_number')->get();
        $systemStocks = DB::table('stocks')->select('part_id', DB::raw('SUM(stock_qty) as qty'))
            ->groupBy('part_id')->pluck('qty', 'part_id');

        return view('stock-opnames.create', compact('parts', 'systemStocks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sto_period' => ['required', 'string', 'max:30'],
            'sto_date' => ['required', 'date'],
            'counts' => ['required', 'array'],
            'counts.*.stock_actual' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $sto = StockOpname::create([
                'sto_period' => $validated['sto_period'],
                'sto_date' => $validated['sto_date'],
                'sto_status' => 'completed',
                'created_by' => $request->user()->user_id,
                'import_time' => now(),
            ]);

            $parts = Part::orderBy('part_id')->get();
            foreach ($parts as $part) {
                $raw = data_get($validated['counts'], $part->part_id.'.stock_actual');
                if ($raw === null || $raw === '') {
                    continue;
                }

                $system = (float) DB::table('stocks')->where('part_id', $part->part_id)->sum('stock_qty');
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

                if (abs($gap) >= 0.0001) {
                    $this->stockService->notifyRoles(
                        ['Staf Administrasi', 'Atasan'],
                        'Inventory discrepancy terdeteksi',
                        'Part '.$part->part_number.' memiliki selisih '.$gap.' pada Stock Opname '.$sto->sto_period.'.',
                        'warning',
                        ['sto_detail_id' => $detail->sto_detail_id]
                    );
                }
            }

            AuditLogger::log($request->user()->user_id, 'Membuat Stock Opname '.$sto->sto_period, $request);
        });

        return redirect()->route('stock-opnames.index')->with('status', 'Stock Opname berhasil disimpan dan dibandingkan dengan saldo sistem.');
    }

    /** Only Staf Administrasi can revise a submitted Stock Opname. */
    public function edit(int $id)
    {
        $opname = StockOpname::with(['creator', 'details.part.unitOfMaterial'])->findOrFail($id);

        return view('stock-opnames.edit', compact('opname'));
    }

    /**
     * Correct actual counts while preserving each original system-stock snapshot.
     * Every changed value and the reason are written to the existing Audit Log.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'sto_period' => ['required', 'string', 'max:30'],
            'sto_date' => ['required', 'date'],
            'revision_reason' => ['required', 'string', 'min:5', 'max:150'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.stock_actual' => ['required', 'numeric', 'min:0'],
        ], [
            'revision_reason.required' => 'Alasan revisi wajib diisi.',
            'revision_reason.min' => 'Alasan revisi minimal 5 karakter.',
            'revision_reason.max' => 'Alasan revisi maksimal 150 karakter agar tersimpan utuh di Audit Log.',
        ]);

        $changedCount = 0;
        DB::transaction(function () use ($request, $id, $validated, &$changedCount) {
            $opname = StockOpname::with('details.part')->lockForUpdate()->findOrFail($id);
            $oldPeriod = $opname->sto_period;
            $oldDate = (string) $opname->sto_date;
            $opname->sto_period = $validated['sto_period'];
            $opname->sto_date = $validated['sto_date'];
            $opname->save();

            foreach ($opname->details as $detail) {
                $key = (string) $detail->sto_detail_id;
                if (!array_key_exists($key, $validated['details'])) {
                    throw ValidationException::withMessages([
                        'details' => 'Data detail STO tidak lengkap. Muat ulang halaman lalu coba kembali.',
                    ]);
                }

                $oldActual = (float) $detail->stock_actual;
                $newActual = (float) $validated['details'][$key]['stock_actual'];
                $newGap = $newActual - (float) $detail->stock_system;
                $newStatus = abs($newGap) < 0.0001 ? 'match' : 'mismatch';

                if (abs($oldActual - $newActual) >= 0.0001) {
                    $detail->stock_actual = $newActual;
                    $detail->gap_quantity = $newGap;
                    $detail->match_status = $newStatus;
                    $detail->save();
                    $changedCount++;

                    $partNumber = $detail->part?->part_number ?? ('Part #'.$detail->part_id);
                    AuditLogger::log(
                        $request->user()->user_id,
                        'Revisi STO #'.$opname->sto_id.' '.$partNumber.': actual '.rtrim(rtrim(number_format($oldActual, 3, '.', ''), '0'), '.').' menjadi '.rtrim(rtrim(number_format($newActual, 3, '.', ''), '0'), '.').'. Alasan: '.$validated['revision_reason'],
                        $request
                    );

                    if ($newStatus === 'mismatch' && abs($newGap) >= 0.0001) {
                        $this->stockService->notifyRoles(
                            ['Staf Administrasi', 'Atasan'],
                            'Hasil Stock Opname direvisi - discrepancy',
                            'STO #'.$opname->sto_id.', part '.$partNumber.' masih memiliki selisih '.$newGap.'. Alasan revisi: '.$validated['revision_reason'],
                            'warning',
                            ['sto_detail_id' => $detail->sto_detail_id]
                        );
                    }
                } else {
                    // Keep derived fields consistent even if the entered actual value did not change.
                    $detail->gap_quantity = $newGap;
                    $detail->match_status = $newStatus;
                    $detail->save();
                }
            }

            if ($oldPeriod !== $opname->sto_period || $oldDate !== (string) $opname->sto_date) {
                AuditLogger::log(
                    $request->user()->user_id,
                    'Revisi header STO #'.$opname->sto_id.': '.$oldPeriod.'/'.$oldDate.' menjadi '.$opname->sto_period.'/'.$opname->sto_date.'. Alasan: '.$validated['revision_reason'],
                    $request
                );
                $changedCount++;
            }

            if ($changedCount === 0) {
                AuditLogger::log($request->user()->user_id, 'Pemeriksaan revisi STO #'.$opname->sto_id.' tanpa perubahan nilai. Alasan: '.$validated['revision_reason'], $request);
            }
        });

        return redirect()->route('stock-opnames.show', $id)->with(
            'status',
            $changedCount > 0
                ? 'Revisi Stock Opname berhasil disimpan. Perubahan tercatat di Audit Log.'
                : 'Tidak ada nilai yang berubah. Pemeriksaan revisi tetap tercatat di Audit Log.'
        );
    }

    /** Atasan requests a correction; the original STO remains read-only to this role. */
    public function requestRevision(Request $request, int $id)
    {
        $validated = $request->validate([
            'request_reason' => ['required', 'string', 'min:5', 'max:200'],
        ], [
            'request_reason.required' => 'Alasan permintaan koreksi wajib diisi.',
            'request_reason.min' => 'Alasan permintaan koreksi minimal 5 karakter.',
            'request_reason.max' => 'Alasan permintaan koreksi maksimal 200 karakter.',
        ]);

        $opname = StockOpname::findOrFail($id);
        $this->stockService->notifyRoles(
            ['Staf Administrasi'],
            'Permintaan koreksi Stock Opname #'.$opname->sto_id,
            'Atasan '.$request->user()->full_name.' meminta koreksi STO periode '.$opname->sto_period.'. Alasan: '.$validated['request_reason'],
            'warning'
        );
        AuditLogger::log(
            $request->user()->user_id,
            'Mengajukan koreksi STO #'.$opname->sto_id.'. Alasan: '.$validated['request_reason'],
            $request
        );

        return redirect()->route('stock-opnames.show', $opname->sto_id)
            ->with('status', 'Permintaan koreksi dikirim ke Staf Administrasi. Data STO belum diubah.');
    }
}
