<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxPackagingQuantity extends Model
{
    protected $table = 'box_packaging_quantities';
    protected $primaryKey = 'box_packaging_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['part_id', 'std_qty_per_box'];

}
