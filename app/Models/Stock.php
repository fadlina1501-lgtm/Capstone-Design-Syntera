<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stocks';
    protected $primaryKey = 'stock_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['part_id', 'location_id', 'stock_qty', 'last_updated'];

    public function part() { return $this->belongsTo(Part::class, 'part_id', 'part_id'); }
    public function location() { return $this->belongsTo(Location::class, 'location_id', 'location_id'); }
}
