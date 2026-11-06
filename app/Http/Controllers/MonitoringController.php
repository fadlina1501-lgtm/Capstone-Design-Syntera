<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function stocks()
    {
        $stocks = DB::table('stocks as s')->join('parts as p','p.part_id','=','s.part_id')
            ->join('locations as l','l.location_id','=','s.location_id')
            ->leftJoin('unit_of_materials as uom','uom.uom_id','=','p.uom_id')
            ->where('s.stock_qty','>',0)->orderBy('p.part_number')->orderBy('l.location_name')
            ->paginate(25,['s.*','p.part_number','p.part_name','p.part_type','uom.uom_name','l.location_name']);
        $totalQty = (float) DB::table('stocks')->sum('stock_qty');
        return view('monitoring.stocks', compact('stocks','totalQty'));
    }

    public function movements(Request $request)
    {
         $query = DB::table('material_movements as mm')
        ->join('material_boxes as mb', 'mb.box_id', '=', 'mm.box_id')
        ->join('parts as p', 'p.part_id', '=', 'mb.part_id')
        ->join('locations as to_l', 'to_l.location_id', '=', 'mm.to_location_id')
        ->leftJoin('locations as from_l', 'from_l.location_id', '=', 'mm.from_location_id')
        ->leftJoin('users as u', 'u.user_id', '=', 'mm.user_id')
        ->select([
            'mm.movement_id',
            'mm.scan_time',
            'mm.movement_type',
            'mm.quantity',
            'mb.box_barcode',
            'p.part_number',
            'p.part_name',
            'from_l.location_name as from_location',
            'to_l.location_name as to_location',
            'u.full_name',
        ]);

    if ($request->filled('barcode')) {
        $query->where(
            'mb.box_barcode',
            'like',
            '%' . $request->barcode . '%'
        );
    }

    if ($request->filled('type')) {
        $query->where('mm.movement_type', $request->type);
    }

    if ($request->filled('from')) {
        $query->whereDate('mm.scan_time', '>=', $request->from);
    }

    if ($request->filled('to')) {
        $query->whereDate('mm.scan_time', '<=', $request->to);
    }

    $movements = $query
        ->orderByDesc('mm.movement_id')
        ->paginate(25)
        ->withQueryString();

    return view('monitoring.movements', compact('movements'));
    }


    public function production()
    {
        $dailyProduction = DB::table('assembly_processes as a')
            ->join('preparations as prep','prep.preparation_id','=','a.preparation_id')
            ->join('production_plans as plan','plan.plan_id','=','prep.plan_id')
            ->join('parts as p','p.part_id','=','plan.fg_part_id')
            ->selectRaw('DATE(a.assembly_time) as production_date, p.part_number, p.part_name, SUM(a.qty_produced) as qty_produced, COUNT(a.assembly_id) as batch_count')
            ->groupByRaw('DATE(a.assembly_time), p.part_number, p.part_name')
            ->orderByDesc('production_date')->limit(100)->get();

        $dailyNg = DB::table('part_ngs as ng')
            ->join('parts as p','p.part_id','=','ng.part_id')
            ->selectRaw('DATE(ng.ng_time) as ng_date, p.part_number, p.part_name, SUM(ng.qty_ng) as qty_ng, COUNT(ng.part_ng_id) as report_count')
            ->groupByRaw('DATE(ng.ng_time), p.part_number, p.part_name')
            ->orderByDesc('ng_date')->limit(100)->get();

        $totalProduced = (float) DB::table('assembly_processes')->sum('qty_produced');
        $totalNg = (float) DB::table('part_ngs')->sum('qty_ng');
        return view('monitoring.production', compact('dailyProduction','dailyNg','totalProduced','totalNg'));
    }

    public function discrepancies()
    {
        $latestSto = DB::table('stock_opnames')->orderByDesc('sto_date')->orderByDesc('sto_id')->first();
        $stoRows = collect();
        if ($latestSto) {
            $stoRows = DB::table('sto_details as sd')->join('parts as p','p.part_id','=','sd.part_id')
                ->where('sd.sto_id',$latestSto->sto_id)->where('sd.match_status','mismatch')
                ->orderByDesc('sd.gap_quantity')->get(['sd.*','p.part_number','p.part_name']);
        }
        return view('monitoring.discrepancies', compact('latestSto','stoRows'));
    }
}
