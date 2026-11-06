<?php

namespace App\Support;

use App\Models\Location;
use App\Models\MaterialBox;
use App\Models\MaterialMovement;
use App\Models\Notification;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function location(string $name): Location
    {
        $location = Location::where('location_name', $name)->first();
        if (!$location) {
            $location = Location::create([
                'location_name' => $name,
                'process_stage' => $name,
                'is_active' => true,
            ]);
        }
        return $location;
    }

    public function currentLocation(MaterialBox $box): ?Location
    {
        $movement = MaterialMovement::where('box_id', $box->box_id)->orderByDesc('movement_id')->first();
        if ($movement) return Location::find($movement->to_location_id);
        return $box->receiving_area ? Location::where('location_name', $box->receiving_area)->first() : null;
    }

    public function adjustStock(int $partId, int $locationId, float $delta): Stock
    {
        $stock = Stock::where('part_id', $partId)->where('location_id', $locationId)->lockForUpdate()->first();
        if (!$stock) {
            $stock = new Stock();
            $stock->part_id = $partId;
            $stock->location_id = $locationId;
            $stock->stock_qty = 0;
        }
        $newQty = (float) $stock->stock_qty + $delta;
        if ($newQty < -0.0001) {
            throw ValidationException::withMessages(['box_barcode' => 'Saldo persediaan pada lokasi asal tidak mencukupi.']);
        }
        $stock->stock_qty = max(0, $newQty);
        $stock->last_updated = now();
        $stock->save();
        return $stock;
    }

    public function moveBox(MaterialBox $box, Location $destination, int $userId, string $type, ?Request $request = null, ?float $quantity = null): MaterialMovement
    {
        $quantity ??= (float) $box->qty_in_box;
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['box_barcode' => 'Jumlah material pada box harus lebih besar dari nol.']);
        }
        if ($quantity - (float) $box->qty_in_box > 0.0001) {
            throw ValidationException::withMessages(['box_barcode' => 'Jumlah perpindahan melebihi jumlah material pada box.']);
        }

        $source = $this->currentLocation($box);
        if ($source && $source->location_id === $destination->location_id && $type !== 'box_split') {
            throw ValidationException::withMessages(['box_barcode' => 'Box sudah berada di lokasi tujuan.']);
        }
        if ($source) {
            $this->adjustStock((int) $box->part_id, (int) $source->location_id, -$quantity);
        }

        // For assembly_consumption, material is consumed at Assembly and is not added to Assembly on-hand stock.
        if ($type !== 'assembly_consumption' && $type !== 'box_split') {
            $this->adjustStock((int) $box->part_id, (int) $destination->location_id, $quantity);
        }

        $movement = MaterialMovement::create([
            'box_id' => $box->box_id,
            'user_id' => $userId,
            'from_location_id' => $source?->location_id,
            'to_location_id' => $destination->location_id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'scan_time' => now(),
        ]);

        $box->receiving_area = $destination->location_name;
        if ($type === 'assembly_consumption') {
            $box->box_status = 'consumed';
        } elseif ($type === 'delivery_outgoing') {
            $box->box_status = 'outgoing';
        } elseif ($type === 'delivery_loading') {
            $box->box_status = 'loaded';
        } elseif ($type === 'delivery') {
            $box->box_status = 'delivered';
        } else {
            $box->box_status = 'stored';
        }
        $box->save();

        AuditLogger::log($userId, 'Perpindahan material '.$box->box_barcode.' ke '.$destination->location_name.' ('.$type.')', $request);
        return $movement;
    }

    public function notifyRoles(array $roleNames, string $title, string $message, string $type = 'info', array $extra = []): void
    {
        $users = User::whereHas('role', fn ($q) => $q->whereIn('role_name', $roleNames))->get();
        foreach ($users as $user) {
            Notification::create(array_merge([
                'user_id' => $user->user_id,
                'notification_title' => $title,
                'notification_message' => $message,
                'notification_type' => $type,
                'is_read' => false,
                'created_at' => now(),
            ], $extra));
        }
    }
}
