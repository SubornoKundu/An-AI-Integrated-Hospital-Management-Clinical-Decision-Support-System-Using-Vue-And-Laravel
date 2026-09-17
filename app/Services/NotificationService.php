<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function send(int $userId, string $role, string $title, string $message, string $type = 'general', string $link = ''): void
    {
        Notification::create([
            'user_id'   => $userId,
            'user_role' => $role,
            'title'     => $title,
            'message'   => $message,
            'type'      => $type,
            'link'      => $link,
        ]);
    }

    public function unreadCount(int $userId, string $role): int
    {
        return Notification::where('user_id', $userId)
            ->where('user_role', $role)
            ->where('is_read', false)
            ->count();
    }
}
