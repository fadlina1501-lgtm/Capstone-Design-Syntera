<?php

namespace App\Http\Controllers;

use App\Models\MiniCycleCount;
use App\Models\MiniCycleCountDetail;
use App\Models\Part;
use App\Support\AuditLogger;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MiniCycleCountController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $counts = MiniCycleCount::with(['counter','approver'])->withCount(['details as mismatch_count'=>fn($q)=>$q->where('match_status','mismatch')])
            ->orderByDesc('count_date')->orderByDesc('mcc_id')->paginate(15);
        return view('mini-cycle-counts.index', compact('counts'));
    }

    public function create()
    {
        $parts = Part::with('unitOfMaterial')->orderBy('part_number')->get();
        $systemStocks = DB::table('stocks')->select('part_id',DB::raw('SUM(stock_qty) as qty'))->groupBy('part_id')->pluck('qty','part_id');
        return view('mini-cycle-counts.create', compact('parts','systemStocks'));
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'count_date'=>['required','date'],
            'counts'=>['required','array'],
            'counts.*.stock_actual'=>['nullable','numeric','min:0'],
        ])->validate();

        DB::transaction(function () use ($request) {
            $count = MiniCycleCount::create([
                'counted_by'=>$request->user()->user_id,
                'approve_by'=>null,
                'count_date'=>$request->count_date,
                'approval_status'=>'pending',
                'approval_at'=>null,
            ]);
            $parts = Part::orderBy('part_id')->get();
            $lines = 0;
            foreach ($parts as $part) {
                $raw = data_get($request->input('counts'),$part->part_id.'.stock_actual');
                if ($raw === null || $raw === '') continue;
                $system = (float) DB::table('stocks')->where('part_id',$part->part_id)->sum('stock_qty');
                $actual = (float)$raw;
                $gap = $actual - $system;
                MiniCycleCountDetail::create([
                    'mcc_id'=>$count->mcc_id,'part_id'=>$part->part_id,
                    'stock_system'=>$system,'stock_actual'=>$actual,'gap_qty'=>$gap,
                    'match_status'=>abs($gap)<0.0001?'match':'mismatch',
                    'follow_up_status'=>abs($gap)<0.0001?'not_required':'open',
                    'follow_up_note'=>null,
                ]);
                $lines++;
            }
            if ($lines === 0) throw ValidationException::withMessages(['counts'=>'Masukkan minimal satu jumlah stock actual.']);
            $this->stockService->notifyRoles(['Atasan'],'Persetujuan Mini Cycle Count diperlukan','Mini Cycle Count #'.$count->mcc_id.' menunggu pemeriksaan dan persetujuan.','approval',['mcc_id'=>$count->mcc_id]);
            AuditLogger::log($request->user()->user_id,'Membuat Mini Cycle Count #'.$count->mcc_id,$request);
        });
        return redirect()->route('mini-cycle-counts.index')->with('status','Mini Cycle Count tersimpan dan menunggu persetujuan Atasan.');
    }

    public function show(int $id)
    {
        $count = MiniCycleCount::with(['counter','approver','details.part'])->findOrFail($id);
        return view('mini-cycle-counts.show', compact('count'));
    }

    public function approve(Request $request, int $id)
    {
        $count = MiniCycleCount::with('details.part')->findOrFail($id);
        if ($request->user()->role?->role_name !== 'Atasan') abort(403);
        if ($count->approval_status !== 'pending') return back()->withErrors(['approval'=>'Mini Cycle Count ini sudah diproses.']);
        $unreviewedMismatch = $count->details()->where('match_status', 'mismatch')->where('follow_up_status', 'open')->exists();
        if ($unreviewedMismatch) {
            return back()->withErrors(['approval' => 'Periksa dan revisi terlebih dahulu hasil Stock Actual yang mismatch sebelum menyetujui Mini Cycle Count.']);
        }
        DB::transaction(function () use ($request, $count) {
            $count->approve_by = $request->user()->user_id;
            $count->approval_status = 'approved';
            $count->approval_at = now();
            $count->save();
            foreach ($count->details as $detail) {
                if ($detail->match_status === 'mismatch') {
                    $detail->follow_up_status = 'reviewed';
                    $detail->follow_up_note = $detail->follow_up_note ?: 'Ditinjau saat persetujuan Atasan.';
                    $detail->save();
                }
            }
            $this->stockService->notifyRoles(['Staf Administrasi', $count->counter?->role?->role_name ?? 'Operator Warehouse'], 'Mini Cycle Count disetujui', 'Mini Cycle Count #'.$count->mcc_id.' telah disetujui oleh Atasan.','success',['mcc_id'=>$count->mcc_id]);
            AuditLogger::log($request->user()->user_id,'Menyetujui Mini Cycle Count #'.$count->mcc_id,$request);
        });
        return redirect()->route('mini-cycle-counts.show',$id)->with('status','Mini Cycle Count berhasil disetujui.');
    }

    public function revise(Request $request, int $id)
    {
        $data = $request->validate([
            'revision_note' => ['required', 'string', 'max:500'],
            'details' => ['required', 'array'],
            'details.*.stock_actual' => ['required', 'numeric', 'min:0'],
        ]);
        $count = MiniCycleCount::with('details')->findOrFail($id);
        if ($request->user()->role?->role_name !== 'Atasan') abort(403);
        if ($count->approval_status !== 'pending') {
            return back()->withErrors(['approval' => 'Hanya Mini Cycle Count berstatus pending yang dapat direvisi sebelum disetujui.']);
        }

        $expectedDetailIds = $count->details->pluck('mcc_detail_id')->map(fn ($id) => (string) $id)->all();
        $submittedDetailIds = array_map('strval', array_keys($data['details']));
        $missingDetailIds = array_diff($expectedDetailIds, $submittedDetailIds);
        if ($missingDetailIds) {
            throw ValidationException::withMessages(['details' => 'Seluruh detail hasil Mini Cycle Count harus diperiksa sebelum revisi disimpan.']);
        }

        DB::transaction(function () use ($request, $count, $data): void {
            foreach ($count->details as $detail) {
                $detailInput = $data['details'][$detail->mcc_detail_id] ?? null;
                if (!$detailInput) continue;

                $actual = (float) $detailInput['stock_actual'];
                $system = (float) DB::table('stocks')->where('part_id', $detail->part_id)->sum('stock_qty');
                $gap = $actual - $system;
                $detail->stock_system = $system;
                $detail->stock_actual = $actual;
                $detail->gap_qty = $gap;
                $detail->match_status = abs($gap) < 0.0001 ? 'match' : 'mismatch';
                $detail->follow_up_status = abs($gap) < 0.0001 ? 'not_required' : 'revised';
                $detail->follow_up_note = abs($gap) < 0.0001 ? null : trim($data['revision_note']);
                $detail->save();
            }

            // Activity Diagram Atasan: revise the count result first, then approve it.
            // Approval fields remain empty until the separate approval action is submitted.
            $this->stockService->notifyRoles(
                ['Staf Administrasi', $count->counter?->role?->role_name ?? 'Operator Warehouse'],
                'Hasil Mini Cycle Count direvisi Atasan',
                'Mini Cycle Count #'.$count->mcc_id.' telah diperbarui oleh Atasan. Catatan: '.trim($data['revision_note']),
                'info',
                ['mcc_id' => $count->mcc_id]
            );
            AuditLogger::log($request->user()->user_id, 'Merevisi hasil Mini Cycle Count #'.$count->mcc_id, $request);
        });

        return redirect()->route('mini-cycle-counts.show', $id)->with('status', 'Hasil Mini Cycle Count berhasil direvisi. Periksa kembali detailnya, lalu setujui jika sudah sesuai.');
    }
}
