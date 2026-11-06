<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['user_id', 'sto_detail_id', 'mcc_id', 'notification_title', 'notification_message', 'notification_type', 'is_read', 'created_at'];

    protected $casts = ['is_read' => 'boolean', 'created_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
    public function stoDetail() { return $this->belongsTo(StoDetail::class, 'sto_detail_id', 'sto_detail_id'); }
    public function miniCycleCount() { return $this->belongsTo(MiniCycleCount::class, 'mcc_id', 'mcc_id'); }
}
