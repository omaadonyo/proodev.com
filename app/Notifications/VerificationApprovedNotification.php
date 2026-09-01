<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VerificationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VerificationRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'verification_approved',
            'title' => 'Verification approved',
            'body' => $this->request->label ?: 'Your professional identity has been verified.',
            'icon' => 'check-badge',
            'url' => route('devid', $notifiable->handle(), absolute: false),
        ];
    }
}
