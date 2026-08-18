<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LevelUpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $level, public string $title) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'level_up',
            'title' => "Level up: {$this->title}",
            'body' => "You reached level {$this->level}. Keep shipping.",
            'icon' => 'trophy',
            'url' => route('growth', absolute: false),
        ];
    }
}
