<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\MarkNotificationReadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->serialize($notification));

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(MarkNotificationReadRequest $request, string $notification): JsonResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->markAsRead();

        return response()->json([
            'notification' => $this->serialize($record->fresh()),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     data: array<string, mixed>,
     *     read_at: string|null,
     *     created_at: string|null
     * }
     */
    private function serialize(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => class_basename($notification->type),
            'data' => $notification->data,
            'read_at' => $notification->read_at instanceof Carbon
                ? $notification->read_at->toIso8601String()
                : $notification->read_at,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
