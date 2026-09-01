<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable->wantsNotification('mentions')) {
            return [];
        }

        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mention',
            'title' => "{$this->comment->user->name} mentioned you",
            'body' => Str::limit($this->comment->body, 120),
            'icon' => 'at-symbol',
            'url' => null,
        ];
    }
}
