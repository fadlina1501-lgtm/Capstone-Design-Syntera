<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoDetail extends Model
{
    protected $table = 'sto_details';
    protected $primaryKey = 'sto_detail_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['sto_id', 'part_id', 'stock_system', 'stock_actual', 'gap_quantity', 'match_status'];

    public function opname() { return $this->belongsTo(StockOpname::class, 'sto_id', 'sto_id'); }
    public function part() { return $this->belongsTo(Part::class, 'part_id', 'part_id'); }
}
