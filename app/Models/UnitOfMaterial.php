<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitOfMaterial extends Model
{
    protected $table = 'unit_of_materials';
    protected $primaryKey = 'uom_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['uom_code', 'uom_name'];

}
