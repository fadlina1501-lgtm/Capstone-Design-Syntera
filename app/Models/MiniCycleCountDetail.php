<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MiniCycleCountDetail extends Model
{
    protected $table = 'mini_cycle_count_details';
    protected $primaryKey = 'mcc_detail_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['mcc_id', 'part_id', 'stock_system', 'stock_actual', 'gap_qty', 'match_status', 'follow_up_status', 'follow_up_note'];

    public function count() { return $this->belongsTo(MiniCycleCount::class, 'mcc_id', 'mcc_id'); }
    public function part() { return $this->belongsTo(Part::class, 'part_id', 'part_id'); }
}
