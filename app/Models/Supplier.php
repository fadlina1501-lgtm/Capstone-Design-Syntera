<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['supplier_code', 'supplier_name'];

}
