<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $table = 'parts';
    protected $primaryKey = 'part_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['part_number', 'part_name', 'part_type', 'classification', 'uom_id'];

    public function unitOfMaterial() { return $this->belongsTo(UnitOfMaterial::class, 'uom_id', 'uom_id'); }
    public function stocks() { return $this->hasMany(Stock::class, 'part_id', 'part_id'); }
    public function boxes() { return $this->hasMany(MaterialBox::class, 'part_id', 'part_id'); }
    public function billOfMaterialAsFinishedGood() { return $this->hasMany(BillOfMaterial::class, 'fg_part_id', 'part_id'); }
    public function billOfMaterialAsComponent() { return $this->hasMany(BillOfMaterial::class, 'component_part_id', 'part_id'); }
}
