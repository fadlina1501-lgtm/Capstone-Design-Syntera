<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manpower extends Model
{
    protected $table = 'manpowers';
    protected $primaryKey = 'manpower_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['nik', 'manpower_name', 'process_stage', 'manpower_status'];

}
