<?php

namespace App\Notifications;

use App\Models\Vouch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VouchReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Vouch $vouch) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'vouch_received',
            'title' => "New {$this->vouch->type->label()} from {$this->vouch->voucher->name}",
            'body' => $this->vouch->message ?: 'Someone vouched for your abilities.',
            'icon' => 'shield-check',
            'url' => route('vouches', absolute: false),
        ];
    }
}
