<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
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
            'type' => 'payment_received',
            'title' => 'Payment received — '.number_format((float) $this->payment->amount, 2).' '.$this->payment->currency,
            'body' => $this->payment->purpose->label().' by '.($this->payment->user?->name ?? $this->payment->company?->name ?? 'a customer').'.',
            'icon' => 'banknotes',
            'url' => route('admin.sales', absolute: false),
        ];
    }
}
