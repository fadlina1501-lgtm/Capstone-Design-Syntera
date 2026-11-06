<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillOfMaterial extends Model
{
    protected $table = 'bill_of_materials';
    protected $primaryKey = 'bom_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['fg_part_id', 'component_part_id', 'qty_per_unit'];

    public function finishedGood() { return $this->belongsTo(Part::class, 'fg_part_id', 'part_id'); }
    public function component() { return $this->belongsTo(Part::class, 'component_part_id', 'part_id'); }
}
