<?php

namespace App\Http\Controllers;

use App\Models\MaterialMovement;
use App\Models\Part;
use App\Models\Stock;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $partCount = Part::count();
        $stockQty = (float) Stock::sum('stock_qty');
        $todayMovements = MaterialMovement::whereDate('scan_time', today())->count();
        $latestSto = StockOpname::orderByDesc('sto_date')->orderByDesc('sto_id')->first();
        $mismatchCount = 0;
        $accuracy = null;
        if ($latestSto) {
            $summary = DB::table('sto_details')->where('sto_id', $latestSto->sto_id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN match_status = ? THEN 1 ELSE 0 END) as matched', ['match'])
                ->first();
            $mismatchCount = (int) DB::table('sto_details')->where('sto_id', $latestSto->sto_id)->where('match_status', 'mismatch')->count();
            if ($summary && (int) $summary->total > 0) {
                $accuracy = round(((int) $summary->matched / (int) $summary->total) * 100, 2);
            }
        }

        $recentMovements = DB::table('material_movements as mm')
            ->join('material_boxes as mb', 'mb.box_id', '=', 'mm.box_id')
            ->join('parts as p', 'p.part_id', '=', 'mb.part_id')
            ->join('locations as to_l', 'to_l.location_id', '=', 'mm.to_location_id')
            ->leftJoin('locations as from_l', 'from_l.location_id', '=', 'mm.from_location_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'mm.user_id')
            ->orderByDesc('mm.movement_id')
            ->limit(8)
            ->get(['mm.movement_id','mm.scan_time','mm.movement_type','mm.quantity','mb.box_barcode','p.part_number','p.part_name','from_l.location_name as from_location','to_l.location_name as to_location','u.full_name']);

        return view('dashboard', compact('partCount','stockQty','todayMovements','latestSto','mismatchCount','accuracy','recentMovements'));
    }
}
