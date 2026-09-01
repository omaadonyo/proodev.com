<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Welcome to ProoDev, '.$this->user->name.'!',
            'body' => 'Your evidence-backed engineering identity is ready. Complete your profile to get started.',
            'icon' => 'sparkles',
            'url' => route('dashboard', absolute: false),
        ];
    }
}
