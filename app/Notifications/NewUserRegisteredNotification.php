<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification implements ShouldQueue
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
            'type' => 'new_user_registered',
            'title' => 'New user: '.$this->user->name,
            'body' => $this->user->role->label().' registered ('.$this->user->email.').',
            'icon' => 'user-plus',
            'url' => route('admin.users', absolute: false),
        ];
    }
}
