<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MiniCycleCount extends Model
{
    protected $table = 'mini_cycle_counts';
    protected $primaryKey = 'mcc_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['counted_by', 'approve_by', 'count_date', 'approval_status', 'approval_at'];

    public function counter() { return $this->belongsTo(User::class, 'counted_by', 'user_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approve_by', 'user_id'); }
    public function details() { return $this->hasMany(MiniCycleCountDetail::class, 'mcc_id', 'mcc_id'); }
}
