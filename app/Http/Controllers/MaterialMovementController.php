<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\MaterialBox;
use App\Models\MaterialMovement;
use App\Models\Part;
use App\Models\ProductionPlan;
use App\Models\Supplier;
use App\Support\AuditLogger;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaterialMovementController extends Controller
{
    public function __construct(private StockService $stockService) {}


    public function incomingWarehouse()
    {
        $boxes = MaterialBox::with(['part', 'supplier'])
            ->whereHas('movements', fn ($query) => $query->where('movement_type', 'receiving'))
            ->orderByDesc('box_id')
            ->limit(30)
            ->get();
        $parts = Part::with('unitOfMaterial')->orderBy('part_number')->get();
        $suppliers = Supplier::orderBy('supplier_name')->get();

        return view('transactions.incoming-warehouse', compact('boxes', 'parts', 'suppliers'));
    }

    public function storeIncomingWarehouse(Request $request)
    {
        $request->merge(['box_barcode' => trim((string) $request->input('box_barcode'))]);
        $data = $request->validate([
            'box_barcode' => ['required', 'string', 'max:120', 'unique:material_boxes,box_barcode'],
            'part_id' => ['required', 'integer', 'exists:parts,part_id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,supplier_id'],
            'po_number' => ['nullable', 'string', 'max:80'],
            'delivery_date' => ['nullable', 'date'],
            'box_sequence' => ['nullable', 'integer', 'min:1'],
            'qty_in_box' => ['required', 'numeric', 'min:0.001'],
            'shelf_no' => ['nullable', 'string', 'max:40'],
        ]);

        $data['box_sequence'] = $data['box_sequence'] ?? 1;

        DB::transaction(function () use ($data, $request): void {
            $box = MaterialBox::create(array_merge($data, [
                'receiving_area' => 'Incoming Warehouse',
                'box_status' => 'received',
            ]));

            $location = $this->stockService->location('Incoming Warehouse');
            $this->stockService->adjustStock((int) $box->part_id, (int) $location->location_id, (float) $box->qty_in_box);
            MaterialMovement::create([
                'box_id' => $box->box_id,
                'user_id' => $request->user()->user_id,
                'from_location_id' => null,
                'to_location_id' => $location->location_id,
                'movement_type' => 'receiving',
                'quantity' => $box->qty_in_box,
                'scan_time' => now(),
            ]);
            AuditLogger::log($request->user()->user_id, 'Penerimaan material '.$box->box_barcode, $request);
        });

        return redirect()->route('transactions.incoming-warehouse')->with('status', 'Barcode supplier berhasil diverifikasi dan penerimaan material dicatat.');
    }

    public function incomingRack()
    {
        $recent = MaterialMovement::with(['box.part', 'fromLocation', 'toLocation', 'user'])
            ->whereHas('toLocation', fn ($query) => $query->where('location_name', 'Incoming Rack'))
            ->orderByDesc('movement_id')
            ->limit(20)
            ->get();

        return view('transactions.incoming-rack', compact('recent'));
    }

    public function storeIncomingRack(Request $request)
    {
        $request->merge(['box_barcode' => trim((string) $request->input('box_barcode'))]);
        $request->validate(['box_barcode' => ['required', 'string', 'exists:material_boxes,box_barcode']]);

        DB::transaction(function () use ($request): void {
            $box = MaterialBox::where('box_barcode', $request->box_barcode)->lockForUpdate()->firstOrFail();
            $current = $this->stockService->currentLocation($box);
            if (!$current || $current->location_name !== 'Incoming Warehouse') {
                throw ValidationException::withMessages([
                    'box_barcode' => 'Box harus berada di Incoming Warehouse sebelum dipindahkan ke Incoming Rack.',
                ]);
            }

            $this->stockService->moveBox($box, $this->stockService->location('Incoming Rack'), $request->user()->user_id, 'incoming_rack', $request);
        });

        return redirect()->route('transactions.incoming-rack')->with('status', 'Perpindahan material ke Incoming Rack berhasil dicatat.');
    }

    public function delivery(Request $request)
    {
        $request->validate(['plan_date' => ['nullable', 'date']]);
        $selectedDate = $request->input('plan_date');
        $planDates = ProductionPlan::query()->select('plan_date')->distinct()->orderByDesc('plan_date')->pluck('plan_date');

        $recentQuery = MaterialMovement::with(['box.part', 'box.assembly.preparation.plan', 'fromLocation', 'toLocation', 'user'])
            ->whereIn('movement_type', ['delivery_outgoing', 'delivery_loading']);
        if ($selectedDate) {
            $recentQuery->whereHas('box.assembly.preparation.plan', fn ($query) => $query->whereDate('plan_date', $selectedDate));
        }
        $recent = $recentQuery->orderByDesc('movement_id')->limit(20)->get();

        $waitingLoadingQuery = MaterialBox::with(['part', 'assembly.preparation.plan'])
            ->where('receiving_area', 'Outgoing Area')
            ->where('box_status', 'outgoing')
            ->whereHas('assembly.preparation.plan');
        if ($selectedDate) {
            $waitingLoadingQuery->whereHas('assembly.preparation.plan', fn ($query) => $query->whereDate('plan_date', $selectedDate));
        }
        $waitingLoading = $waitingLoadingQuery->orderBy('box_barcode')->get();

        return view('transactions.delivery', compact('recent', 'waitingLoading', 'planDates', 'selectedDate'));
    }

    public function storeDelivery(Request $request)
    {
        $request->merge(['box_barcode' => trim((string) $request->input('box_barcode'))]);
        $data = $request->validate([
            'box_barcode' => ['required', 'string', 'exists:material_boxes,box_barcode'],
            'plan_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $box = MaterialBox::where('box_barcode', $data['box_barcode'])->lockForUpdate()->firstOrFail();
            $part = Part::findOrFail($box->part_id);
            if (strtolower((string) $part->part_type) !== 'fg') {
                throw ValidationException::withMessages(['box_barcode' => 'Delivery hanya menerima barcode Part Tag untuk Finished Good (FG).']);
            }

            $assembly = $box->assembly_id ? \App\Models\AssemblyProcess::with('preparation.plan')->find($box->assembly_id) : null;
            if (!$assembly || !$assembly->preparation?->plan) {
                throw ValidationException::withMessages(['box_barcode' => 'Barcode harus berasal dari Part Tag yang dihasilkan melalui proses Dandori dan Assembly.']);
            }
            if (date('Y-m-d', strtotime((string) $assembly->preparation->plan->plan_date)) !== $data['plan_date']) {
                throw ValidationException::withMessages(['plan_date' => 'Tanggal produksi tidak sesuai dengan Part Tag yang dipindai.']);
            }

            $current = $this->stockService->currentLocation($box);
            if (!$current || $current->location_name !== 'Assembly') {
                throw ValidationException::withMessages(['box_barcode' => 'Finished Good harus berada di lokasi Assembly sebelum dipindahkan ke Outgoing Area.']);
            }

            $this->stockService->moveBox($box, $this->stockService->location('Outgoing Area'), $request->user()->user_id, 'delivery_outgoing', $request);
        });

        return redirect()->route('transactions.delivery', ['plan_date' => $data['plan_date']])->with('status', 'Part Tag berhasil dipindai. Produk tercatat pada Outgoing Area.');
    }

    public function storeLoading(Request $request)
    {
        $request->merge(['box_barcode' => trim((string) $request->input('box_barcode'))]);
        $data = $request->validate(['box_barcode' => ['required', 'string', 'exists:material_boxes,box_barcode']]);

        DB::transaction(function () use ($request, $data): void {
            $box = MaterialBox::where('box_barcode', $data['box_barcode'])->lockForUpdate()->firstOrFail();
            $part = Part::findOrFail($box->part_id);
            if (strtolower((string) $part->part_type) !== 'fg') {
                throw ValidationException::withMessages(['box_barcode' => 'Hanya Finished Good yang dapat dipindahkan ke Loading Area.']);
            }
            $current = $this->stockService->currentLocation($box);
            if (!$current || $current->location_name !== 'Outgoing Area') {
                throw ValidationException::withMessages(['box_barcode' => 'Produk harus tercatat di Outgoing Area sebelum dipindahkan ke Loading Area.']);
            }

            $this->stockService->moveBox($box, $this->stockService->location('Loading Area'), $request->user()->user_id, 'delivery_loading', $request);
        });

        return redirect()->route('transactions.delivery')->with('status', 'Produk berhasil dipindahkan dari Outgoing Area ke Loading Area.');
    }
}
