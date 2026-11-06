<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialBox extends Model
{
    protected $table = 'material_boxes';
    protected $primaryKey = 'box_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['box_barcode', 'part_id', 'supplier_id', 'assembly_id', 'po_number', 'delivery_date', 'box_sequence', 'qty_in_box', 'receiving_area', 'shelf_no', 'box_status'];

    public function part() { return $this->belongsTo(Part::class, 'part_id', 'part_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id'); }
    public function assembly() { return $this->belongsTo(AssemblyProcess::class, 'assembly_id', 'assembly_id'); }
    public function movements() { return $this->hasMany(MaterialMovement::class, 'box_id', 'box_id'); }
}
