<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VerificationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'verification_approved',
            'title' => ($this->payment->company?->name ?? 'Your company').' is verified 🎉',
            'body' => 'Full recruiter and company tools are unlocked. Your held job post is live.',
            'icon' => 'check-badge',
            'url' => route('companies.manage', $this->payment->company, absolute: false),
        ];
    }
}
