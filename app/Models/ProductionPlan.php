<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $table = 'production_plans';
    protected $primaryKey = 'plan_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['fg_part_id', 'created_by', 'plan_date', 'qty_plan', 'plan_status'];

    public function finishedGood() { return $this->belongsTo(Part::class, 'fg_part_id', 'part_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by', 'user_id'); }
}
