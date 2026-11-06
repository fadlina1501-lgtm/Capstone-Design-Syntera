<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    protected $fillable = ['role_id', 'username', 'password_hash', 'full_name', 'status_account', 'last_login'];
    protected $hidden = ['password_hash'];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function role() { return $this->belongsTo(Role::class, 'role_id', 'role_id'); }
    public function auditLogs() { return $this->hasMany(AuditLog::class, 'user_id', 'user_id'); }
    public function notifications() { return $this->hasMany(Notification::class, 'user_id', 'user_id'); }
    public function movements() { return $this->hasMany(MaterialMovement::class, 'user_id', 'user_id'); }
}
