<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\PartNg;
use App\Models\ProductionPlan;
use App\Support\AuditLogger;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartNgController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $parts = Part::orderBy('part_number')->get();
        $plans = ProductionPlan::with('finishedGood')->orderByDesc('plan_date')->orderBy('plan_id')->get();
        $recent = PartNg::with(['part', 'plan.finishedGood', 'user'])
            ->orderByDesc('ng_time')
            ->paginate(20);

        return view('assembly.part-ng', compact('parts', 'plans', 'recent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'part_id' => ['required', 'integer', 'exists:parts,part_id'],
            'plan_id' => ['nullable', 'integer', 'exists:production_plans,plan_id'],
            'qty_ng' => ['required', 'numeric', 'min:0.001'],
            'ng_reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            PartNg::create([
                'part_id' => $data['part_id'],
                'plan_id' => $data['plan_id'] ?? null,
                'user_id' => $request->user()->user_id,
                'qty_ng' => $data['qty_ng'],
                'ng_reason' => trim($data['ng_reason']),
                'ng_time' => now(),
            ]);

            $part = Part::findOrFail($data['part_id']);
            $this->stockService->notifyRoles(
                ['Staf Administrasi', 'Atasan'],
                'Pencatatan Part NG',
                'Terdapat pencatatan Part NG sebanyak '.$data['qty_ng'].' untuk '.$part->part_number.'.',
                'warning'
            );
            AuditLogger::log($request->user()->user_id, 'Mencatat Part NG '.$part->part_number.' sebanyak '.$data['qty_ng'], $request);
        });

        return redirect()->route('transactions.part-ng')->with('status', 'Data Part NG berhasil disimpan.');
    }
}
