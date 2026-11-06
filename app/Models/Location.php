<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';
    protected $primaryKey = 'location_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['location_name', 'process_stage', 'is_active'];

    public function stocks() { return $this->hasMany(Stock::class, 'location_id', 'location_id'); }
}
