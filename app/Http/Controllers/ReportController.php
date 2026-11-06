<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        $movementCount = DB::table('material_movements')->count();
        $stockLines = DB::table('stocks')->where('stock_qty','>',0)->count();
        $partNgQty = (float) DB::table('part_ngs')->sum('qty_ng');
        $lastSto = DB::table('stock_opnames')->orderByDesc('sto_date')->orderByDesc('sto_id')->first();
        $mccPending = DB::table('mini_cycle_counts')->where('approval_status','pending')->count();
        return view('reports.index', compact('movementCount','stockLines','partNgQty','lastSto','mccPending'));
    }

    public function export(string $type): StreamedResponse
    {
        $filename = 'stock-tracking-'.$type.'-'.now()->format('Ymd-His').'.csv';
        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output','w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for spreadsheet compatibility
            if ($type === 'stocks') {
                fputcsv($out,['Part Number','Nama Part','Jenis','Lokasi','Jumlah Stock','Satuan','Terakhir Diperbarui']);
                DB::table('stocks as s')->join('parts as p','p.part_id','=','s.part_id')->join('locations as l','l.location_id','=','s.location_id')->leftJoin('unit_of_materials as u','u.uom_id','=','p.uom_id')->orderBy('p.part_number')->cursor()->each(fn($r)=>fputcsv($out,[$r->part_number,$r->part_name,$r->part_type,$r->location_name,$r->stock_qty,$r->uom_name,$r->last_updated]));
            } elseif ($type === 'movements') {
                fputcsv($out,['Movement ID','Barcode','Part Number','Jenis Movement','Dari Lokasi','Ke Lokasi','Jumlah','Petugas','Waktu']);
                DB::table('material_movements as m')->join('material_boxes as b','b.box_id','=','m.box_id')->join('parts as p','p.part_id','=','b.part_id')->join('locations as to_l','to_l.location_id','=','m.to_location_id')->leftJoin('locations as from_l','from_l.location_id','=','m.from_location_id')->join('users as u','u.user_id','=','m.user_id')->orderBy('m.movement_id')->cursor()->each(fn($r)=>fputcsv($out,[$r->movement_id,$r->box_barcode,$r->part_number,$r->movement_type,$r->from_location,$r->to_location,$r->quantity,$r->full_name,$r->scan_time]));
            } elseif ($type === 'discrepancies') {
                fputcsv($out,['STO Period','Tanggal','Part Number','Stock System','Stock Actual','Gap','Status']);
                DB::table('sto_details as d')->join('stock_opnames as s','s.sto_id','=','d.sto_id')->join('parts as p','p.part_id','=','d.part_id')->where('d.match_status','mismatch')->orderBy('s.sto_date')->cursor()->each(fn($r)=>fputcsv($out,[$r->sto_period,$r->sto_date,$r->part_number,$r->stock_system,$r->stock_actual,$r->gap_quantity,$r->match_status]));
            } elseif ($type === 'production') {
                fputcsv($out,['Jenis Catatan','Tanggal','Part Number','Nama Part','Jumlah','Keterangan']);
                DB::table('assembly_processes as a')->join('preparations as prep','prep.preparation_id','=','a.preparation_id')->join('production_plans as plan','plan.plan_id','=','prep.plan_id')->join('parts as p','p.part_id','=','plan.fg_part_id')->orderBy('a.assembly_time')->cursor()->each(fn($r)=>fputcsv($out,['Assembly',$r->assembly_time,$r->part_number,$r->part_name,$r->qty_produced,'Hasil produksi FG']));
                DB::table('part_ngs as ng')->join('parts as p','p.part_id','=','ng.part_id')->orderBy('ng.ng_time')->cursor()->each(fn($r)=>fputcsv($out,['Part NG',$r->ng_time,$r->part_number,$r->part_name,$r->qty_ng,$r->ng_reason]));
            } else {
                fputcsv($out,['Jenis laporan tidak dikenal']);
            }
            fclose($out);
        }, $filename, ['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
