<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'audit_id';
    public $timestamps = false;
    protected $guarded = []; 
    protected $fillable = ['user_id', 'activity', 'activity_time', 'ip_address'];

    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
}
