<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartNg extends Model
{
    protected $table = 'part_ngs';
    protected $primaryKey = 'part_ng_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['part_id', 'plan_id', 'user_id', 'qty_ng', 'ng_reason', 'ng_time'];

    public function part() { return $this->belongsTo(Part::class, 'part_id', 'part_id'); }
    public function plan() { return $this->belongsTo(ProductionPlan::class, 'plan_id', 'plan_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
}
