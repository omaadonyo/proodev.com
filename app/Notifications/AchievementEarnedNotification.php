<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AchievementEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Achievement $achievement) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'achievement_earned',
            'title' => "Badge earned: {$this->achievement->name}",
            'body' => $this->achievement->description,
            'icon' => $this->achievement->icon,
            'url' => route('reputation', absolute: false),
        ];
    }
}
