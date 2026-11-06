<?php

namespace App\Http\Controllers;

use App\Models\AssemblyProcess;
use App\Models\MaterialBox;
use App\Models\MaterialMovement;
use App\Models\Preparation;
use App\Models\ProductionPlan;
use App\Support\AuditLogger;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssemblyController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index()
    {
        $preparations = Preparation::with(['plan.finishedGood','details.box.part'])
            ->where('preparation_status','prepared')
            ->whereDoesntHave('assemblyProcesses')
            ->orderByDesc('preparation_id')->get();
        $recent = AssemblyProcess::with(['preparation.plan.finishedGood','user'])->orderByDesc('assembly_id')->limit(15)->get();
        return view('assembly.index', compact('preparations','recent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tag_code'=>['required','string','exists:preparations,tag_code'],
            'qty_produced'=>['required','numeric','min:0.001'],
        ]);
        DB::transaction(function () use ($request, $data) {
            $prep = Preparation::where('tag_code',trim($data['tag_code']))->lockForUpdate()->firstOrFail();
            if ($prep->preparation_status !== 'prepared' || $prep->assemblyProcesses()->exists()) {
                throw ValidationException::withMessages(['tag_code'=>'Tag ini sudah diproses pada tahap Assembly.']);
            }
            if ((float)$data['qty_produced'] > (float)$prep->qty_fg) {
                throw ValidationException::withMessages(['qty_produced'=>'Jumlah hasil assembly tidak boleh melebihi jumlah FG pada tag persiapan.']);
            }
            $plan = ProductionPlan::findOrFail($prep->plan_id);
            $assembly = AssemblyProcess::create([
                'preparation_id'=>$prep->preparation_id,
                'user_id'=>$request->user()->user_id,
                'qty_produced'=>$data['qty_produced'],
                'assembly_time'=>now(),
            ]);
            $dandori = $this->stockService->location('Dandori');
            $assemblyLocation = $this->stockService->location('Assembly');

            foreach ($prep->details()->with('box')->get() as $detail) {
                $box = MaterialBox::where('box_id',$detail->box_id)->lockForUpdate()->firstOrFail();
                $consumeQty = (float)$detail->qty_prepared;
                $currentLocation = $this->stockService->currentLocation($box);
                if (!$currentLocation || $currentLocation->location_id !== $dandori->location_id) {
                    throw ValidationException::withMessages(['tag_code'=>'Salah satu box komponen tidak lagi berada di lokasi Dandori.']);
                }
                if ((float)$box->qty_in_box + 0.0001 < $consumeQty) {
                    throw ValidationException::withMessages(['tag_code'=>'Jumlah material pada box tidak mencukupi untuk proses Assembly.']);
                }

                // If only part of a box is allocated to this preparation, create a traceable remainder box.
                $remainder = (float)$box->qty_in_box - $consumeQty;
                if ($remainder > 0.0001) {
                    $remainderBox = MaterialBox::create([
                        'box_barcode'=>'R-BOX-'.$box->box_id.'-'.Str::upper(Str::random(8)),
                        'part_id'=>$box->part_id,
                        'supplier_id'=>$box->supplier_id,
                        'assembly_id'=>null,
                        'po_number'=>$box->po_number,
                        'delivery_date'=>$box->delivery_date,
                        'box_sequence'=>$box->box_sequence,
                        'qty_in_box'=>$remainder,
                        'receiving_area'=>'Dandori',
                        'shelf_no'=>$box->shelf_no,
                        'box_status'=>'stored',
                    ]);
                    AuditLogger::log($request->user()->user_id, 'Sisa box '.$remainderBox->box_barcode.' dibuat dari box '.$box->box_barcode, $request);
                    MaterialMovement::create([
                        'box_id'=>$remainderBox->box_id,
                        'user_id'=>$request->user()->user_id,
                        'from_location_id'=>$dandori->location_id,
                        'to_location_id'=>$dandori->location_id,
                        'movement_type'=>'box_split_remainder',
                        'quantity'=>$remainder,
                        'scan_time'=>now(),
                    ]);
                }
                $box->qty_in_box = $consumeQty;
                $box->save();
                $this->stockService->moveBox($box, $assemblyLocation, $request->user()->user_id, 'assembly_consumption', $request, $consumeQty);
                $box->qty_in_box = 0;
                $box->box_status = 'consumed';
                $box->save();
            }

            $fgBox = MaterialBox::create([
                'box_barcode'=>$prep->tag_code,
                'part_id'=>$plan->fg_part_id,
                'supplier_id'=>null,
                'assembly_id'=>$assembly->assembly_id,
                'po_number'=>null,
                'delivery_date'=>null,
                'box_sequence'=>1,
                'qty_in_box'=>$data['qty_produced'],
                'receiving_area'=>'Assembly',
                'shelf_no'=>null,
                'box_status'=>'produced',
            ]);
            $this->stockService->adjustStock((int)$plan->fg_part_id, (int)$assemblyLocation->location_id, (float)$data['qty_produced']);
            MaterialMovement::create([
                'box_id'=>$fgBox->box_id,
                'user_id'=>$request->user()->user_id,
                'from_location_id'=>null,
                'to_location_id'=>$assemblyLocation->location_id,
                'movement_type'=>'assembly_output',
                'quantity'=>$data['qty_produced'],
                'scan_time'=>now(),
            ]);

            $prep->preparation_status = 'assembled';
            $prep->save();
            AuditLogger::log($request->user()->user_id, 'Assembly selesai untuk tag '.$prep->tag_code, $request);
        });

        return redirect()->route('transactions.assembly')->with('status','Hasil Assembly dan pergerakan material berhasil dicatat.');
    }
}
