<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'filter' => ['nullable', 'string', 'in:unread,all'],
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $query = $request->user()->notifications();

        if (($validated['filter'] ?? null) === 'unread') {
            $query = $request->user()->unreadNotifications();
        }

        $notifications = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json(array_merge(
            $notifications->toArray(),
            ['unread_count' => $request->user()->unreadNotifications()->count()]
        ));
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $this->findOwnedNotification($request, $notificationId);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'unread_count' => 0,
        ]);
    }

    private function findOwnedNotification(Request $request, string $notificationId): DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();
    }
}
