<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(?int $userId, string $activity, ?Request $request = null): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'activity' => mb_substr($activity, 0, 255),
            'activity_time' => now(),
            'ip_address' => $request?->ip(),
        ]);
    }
}
