<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\Part;
use App\Models\Preparation;
use App\Models\ProductionPlan;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileStationController extends Controller
{
    public function qrIndex(Request $request)
    {
        $baseUrl = rtrim((string) (config('scan_stations.base_url') ?: $request->getSchemeAndHttpHost()), '/');

        return view('mobile-station.qr-index', [
            'stations' => config('scan_stations.stations', []),
            'baseUrl' => $baseUrl,
        ]);
    }

    public function show(Request $request, string $station)
    {
        $stationConfig = config("scan_stations.stations.{$station}");
        abort_unless(is_array($stationConfig), 404, 'QR stasiun tidak dikenali.');
        abort_unless($request->user()->role?->role_name === $stationConfig['role'], 403, 'Akun ini tidak memiliki akses ke stasiun '.$stationConfig['label'].'.');

        $parts = collect();
        $suppliers = collect();
        $plans = collect();
        $planSummary = [];
        $planDates = collect();

        if ($stationConfig['form'] === 'incoming-warehouse') {
            $parts = Part::with('unitOfMaterial')->orderBy('part_number')->get();
            $suppliers = Supplier::orderBy('supplier_name')->get();
        }

        if (in_array($stationConfig['form'], ['dandori', 'part-ng'], true)) {
            $plans = ProductionPlan::with('finishedGood')
                ->when($stationConfig['form'] === 'dandori', fn ($query) => $query->whereIn('plan_status', ['planned', 'in_progress']))
                ->orderByDesc('plan_date')->orderBy('plan_id')->get();
        }

        if ($stationConfig['form'] === 'part-ng') {
            $parts = Part::orderBy('part_number')->get();
        }

        if ($stationConfig['form'] === 'delivery') {
            $planDates = ProductionPlan::query()->select('plan_date')->distinct()->orderByDesc('plan_date')->pluck('plan_date');
        }

        if ($stationConfig['form'] === 'dandori') {
            foreach ($plans as $plan) {
                $lines = BillOfMaterial::with('component.unitOfMaterial')
                    ->where('fg_part_id', $plan->fg_part_id)->get();
                $planSummary[$plan->plan_id] = [
                    'target' => (float) $plan->qty_plan,
                    'fg_prepared' => (float) Preparation::where('plan_id', $plan->plan_id)
                        ->whereIn('preparation_status', ['prepared', 'assembled'])->sum('qty_fg'),
                    'components' => $lines->map(function ($line) {
                        $rackStock = (float) DB::table('stocks as s')
                            ->join('locations as l', 'l.location_id', '=', 's.location_id')
                            ->where('s.part_id', $line->component_part_id)
                            ->where('l.location_name', 'Incoming Rack')->sum('s.stock_qty');
                        return [
                            'part_number' => $line->component?->part_number,
                            'part_name' => $line->component?->part_name,
                            'uom' => $line->component?->unitOfMaterial?->uom_name,
                            'qty_per_unit' => (float) $line->qty_per_unit,
                            'rack_stock' => $rackStock,
                        ];
                    })->values(),
                ];
            }
        }

        return response()->view('mobile-station.show', [
            'stationKey' => $station,
            'station' => $stationConfig,
            'parts' => $parts,
            'suppliers' => $suppliers,
            'plans' => $plans,
            'planDates' => $planDates,
            'planSummary' => $planSummary,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
