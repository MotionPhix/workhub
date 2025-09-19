<?php

namespace App\Http\Controllers;

use App\Events\NotificationRead;
use App\States\NotificationState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(15);

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    public function unread()
    {
        $unreadNotifications = Auth::user()->unreadNotifications()->latest()->get();

        return response()->json([
            'notifications' => $unreadNotifications,
            'count' => $unreadNotifications->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();

            // Fire the Verbs event
            NotificationRead::fire(
                userId: Auth::id(),
                notificationId: $id
            );
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        $notifications = Auth::user()->unreadNotifications;

        foreach ($notifications as $notification) {
            $notification->markAsRead();

            // Fire the Verbs event for each notification
            NotificationRead::fire(
                userId: Auth::id(),
                notificationId: $notification->id
            );
        }

        return response()->json(['success' => true]);
    }

    public function destroy(string $id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            $notification->delete();
        }

        return response()->json(['success' => true]);
    }

    public function clear()
    {
        Auth::user()->notifications()->delete();

        return response()->json(['success' => true]);
    }

    public function statistics()
    {
        $state = NotificationState::load(Auth::id());

        return response()->json([
            'statistics' => $state->statistics ?? [
                'total_sent' => 0,
                'total_read' => 0,
                'total_clicked' => 0,
                'channels_used' => []
            ],
            'unread_count' => Auth::user()->unreadNotifications()->count(),
            'total_count' => Auth::user()->notifications()->count(),
        ]);
    }
}
