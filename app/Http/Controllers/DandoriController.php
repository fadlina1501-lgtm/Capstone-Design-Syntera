<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\MaterialBox;
use App\Models\MaterialMovement;
use App\Models\Preparation;
use App\Models\PreparationDetail;
use App\Models\ProductionPlan;
use App\Support\AuditLogger;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DandoriController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $request->validate(['plan_date' => ['nullable', 'date']]);
        $selectedDate = $request->input('plan_date');
        $planDates = ProductionPlan::query()->select('plan_date')->distinct()->orderByDesc('plan_date')->pluck('plan_date');
        $plans = ProductionPlan::with('finishedGood')
            ->whereIn('plan_status', ['planned', 'in_progress'])
            ->when($selectedDate, fn ($query) => $query->whereDate('plan_date', $selectedDate))
            ->orderBy('plan_date')
            ->orderBy('plan_id')
            ->get();

        $planSummary = [];
        foreach ($plans as $plan) {
            $lines = BillOfMaterial::with('component.unitOfMaterial')
                ->where('fg_part_id', $plan->fg_part_id)
                ->get();

            $planSummary[$plan->plan_id] = [
                'target' => (float) $plan->qty_plan,
                'fg_prepared' => (float) Preparation::where('plan_id', $plan->plan_id)
                    ->whereIn('preparation_status', ['prepared', 'assembled'])
                    ->sum('qty_fg'),
                'components' => $lines->map(function ($line) use ($plan) {
                    $rackStock = (float) DB::table('stocks as s')
                        ->join('locations as l', 'l.location_id', '=', 's.location_id')
                        ->where('s.part_id', $line->component_part_id)
                        ->where('l.location_name', 'Incoming Rack')
                        ->sum('s.stock_qty');

                    $preparedQty = (float) DB::table('preparation_details as d')
                        ->join('preparations as p', 'p.preparation_id', '=', 'd.preparation_id')
                        ->join('material_boxes as b', 'b.box_id', '=', 'd.box_id')
                        ->where('p.plan_id', $plan->plan_id)
                        ->whereIn('p.preparation_status', ['prepared', 'assembled'])
                        ->where('b.part_id', $line->component_part_id)
                        ->sum('d.qty_prepared');

                    return [
                        'part_number' => $line->component?->part_number,
                        'part_name' => $line->component?->part_name,
                        'uom' => $line->component?->unitOfMaterial?->uom_name,
                        'qty_per_unit' => (float) $line->qty_per_unit,
                        'rack_stock' => $rackStock,
                        'prepared_qty' => $preparedQty,
                    ];
                })->values(),
            ];
        }

        $preparations = Preparation::with(['plan.finishedGood', 'user'])
            ->orderByDesc('preparation_id')
            ->limit(20)
            ->get();
        $remainders = MaterialBox::with('part')
            ->where('receiving_area', 'Dandori')
            ->where('qty_in_box', '>', 0)
            ->where('box_status', 'stored')
            ->orderBy('box_barcode')
            ->get();

        return view('dandori.index', compact('plans', 'planSummary', 'preparations', 'remainders', 'planDates', 'selectedDate'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:production_plans,plan_id'],
            'qty_fg' => ['required', 'numeric', 'min:0.001'],
            'barcodes' => ['required', 'string'],
        ]);
        $barcodes = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['barcodes'])))));
        if (!$barcodes) {
            throw ValidationException::withMessages(['barcodes' => 'Pindai minimal satu barcode box komponen.']);
        }

        DB::transaction(function () use ($request, $data, $barcodes): void {
            $plan = ProductionPlan::where('plan_id', $data['plan_id'])->lockForUpdate()->firstOrFail();
            $alreadyPrepared = (float) Preparation::where('plan_id', $plan->plan_id)
                ->whereIn('preparation_status', ['prepared', 'assembled'])
                ->sum('qty_fg');
            if ($alreadyPrepared + (float) $data['qty_fg'] > (float) $plan->qty_plan + 0.0001) {
                throw ValidationException::withMessages([
                    'qty_fg' => 'Akumulasi persiapan tidak boleh melebihi sisa target Production Plan. Sisa target: '.number_format(max(0, (float) $plan->qty_plan - $alreadyPrepared), 3).'.',
                ]);
            }

            $bom = BillOfMaterial::where('fg_part_id', $plan->fg_part_id)->get();
            if ($bom->isEmpty()) {
                throw ValidationException::withMessages(['plan_id' => 'Bill of Material untuk Finished Good ini belum tersedia.']);
            }

            $required = [];
            foreach ($bom as $line) {
                $required[$line->component_part_id] = (float) $line->qty_per_unit * (float) $data['qty_fg'];
            }

            $boxes = [];
            foreach ($barcodes as $barcode) {
                $box = MaterialBox::where('box_barcode', $barcode)->lockForUpdate()->first();
                if (!$box) {
                    throw ValidationException::withMessages(['barcodes' => 'Barcode '.$barcode.' tidak ditemukan.']);
                }

                $location = $this->stockService->currentLocation($box);
                if (!$location || !in_array($location->location_name, ['Incoming Rack', 'Dandori'], true)) {
                    throw ValidationException::withMessages(['barcodes' => 'Box '.$barcode.' harus berada di Incoming Rack atau merupakan sisa box yang tersimpan di Dandori.']);
                }
                if (!array_key_exists($box->part_id, $required)) {
                    throw ValidationException::withMessages(['barcodes' => 'Part pada box '.$barcode.' bukan komponen pada BOM Finished Good yang dipilih.']);
                }

                // A physical box can be reserved by only one open preparation at a time.
                $isReserved = PreparationDetail::where('box_id', $box->box_id)
                    ->whereHas('preparation', fn ($query) => $query->where('preparation_status', 'prepared'))
                    ->exists();
                if ($isReserved) {
                    throw ValidationException::withMessages(['barcodes' => 'Box '.$barcode.' masih digunakan oleh Part Tag lain yang belum diproses di Assembly.']);
                }
                if ((float) $box->qty_in_box <= 0) {
                    throw ValidationException::withMessages(['barcodes' => 'Jumlah material pada box '.$barcode.' sudah habis.']);
                }

                $boxes[] = $box;
            }

            $remaining = $required;
            $allocations = [];
            foreach ($boxes as $box) {
                $needed = (float) ($remaining[$box->part_id] ?? 0);
                if ($needed <= 0.0001) {
                    throw ValidationException::withMessages(['barcodes' => 'Komponen pada box '.$box->box_barcode.' sudah tercukupi; hapus barcode box yang berlebih.']);
                }
                $allocation = min((float) $box->qty_in_box, $needed);
                if ($allocation > 0) {
                    $allocations[] = ['box' => $box, 'qty' => $allocation];
                    $remaining[$box->part_id] = max(0, $needed - $allocation);
                }
            }

            $missing = array_filter($remaining, fn ($qty) => $qty > 0.0001);
            if ($missing) {
                $partNumbers = DB::table('parts')->whereIn('part_id', array_keys($missing))->orderBy('part_number')->pluck('part_number')->implode(', ');
                throw ValidationException::withMessages(['barcodes' => 'Jumlah komponen belum mencukupi kebutuhan BOM untuk: '.$partNumbers.'.']);
            }

            $tagCode = 'TAG-'.now()->format('ymd-His').'-'.Str::upper(Str::random(4));
            $preparation = Preparation::create([
                'tag_code' => $tagCode,
                'plan_id' => $plan->plan_id,
                'user_id' => $request->user()->user_id,
                'qty_fg' => $data['qty_fg'],
                'preparation_status' => 'prepared',
                'tag_printed_at' => now(),
                'preparation_time' => now(),
            ]);

            foreach ($allocations as $allocation) {
                /** @var MaterialBox $box */
                $box = $allocation['box'];
                $currentLocation = $this->stockService->currentLocation($box);
                if (!$currentLocation || $currentLocation->location_name !== 'Dandori') {
                    $this->stockService->moveBox($box, $this->stockService->location('Dandori'), $request->user()->user_id, 'dandori_preparation', $request);
                }
                PreparationDetail::create([
                    'preparation_id' => $preparation->preparation_id,
                    'box_id' => $box->box_id,
                    'qty_prepared' => $allocation['qty'],
                ]);
            }

            AuditLogger::log($request->user()->user_id, 'Persiapan material Dandori dengan tag '.$tagCode, $request);
        });

        return redirect()->route('transactions.dandori')->with('status', 'Persiapan material berhasil dicatat. Part Tag tersedia untuk dicetak dari daftar persiapan.');
    }

    public function printBoxTag(int $id)
    {
        $box = MaterialBox::with(['part.unitOfMaterial'])->findOrFail($id);
        abort_unless($box->receiving_area === 'Dandori' && (float) $box->qty_in_box > 0, 404);

        return view('dandori.box-tag', compact('box'));
    }

    public function printTag(int $id)
    {
        $preparation = Preparation::with(['plan.finishedGood', 'details.box.part.unitOfMaterial'])->findOrFail($id);

        return view('dandori.tag', compact('preparation'));
    }
}
