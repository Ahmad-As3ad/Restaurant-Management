<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // قائمة الإشعارات
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count(),
        ]);
    }

    // الإشعارات غير المقروءة
    public function unread(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    // تحديد إشعار كمقروء
    public function markAsRead(Request $request, int $id)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    // تحديد جميع الإشعارات كمقروءة
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notification::markAllAsRead($user->id);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    // حذف إشعار
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }

    // حذف جميع الإشعارات
    public function destroyAll(Request $request)
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications deleted successfully',
        ]);
    }
}
