<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->orderByDesc('notification_id')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /** Return the unread notification count for the authenticated user. */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markRead(Request $request, int $id)
    {
        $notification = Notification::where('user_id', $request->user()->user_id)
            ->where('notification_id', $id)
            ->firstOrFail();

        $notification->is_read = true;
        $notification->save();

        AuditLogger::log($request->user()->user_id, 'Membaca notifikasi #' . $id, $request);

        return back()->with('status', 'Notifikasi ditandai sudah dibaca.');
    }
}
