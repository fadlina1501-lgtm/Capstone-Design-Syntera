<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preparation extends Model
{
    protected $table = 'preparations';
    protected $primaryKey = 'preparation_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['tag_code', 'plan_id', 'user_id', 'qty_fg', 'preparation_status', 'tag_printed_at', 'preparation_time'];

    public function plan() { return $this->belongsTo(ProductionPlan::class, 'plan_id', 'plan_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
    public function details() { return $this->hasMany(PreparationDetail::class, 'preparation_id', 'preparation_id'); }
    public function assemblyProcesses() { return $this->hasMany(AssemblyProcess::class, 'preparation_id', 'preparation_id'); }
}
