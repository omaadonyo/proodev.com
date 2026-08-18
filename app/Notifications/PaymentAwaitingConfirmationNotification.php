<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentAwaitingConfirmationNotification extends Notification implements ShouldQueue
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
            'type' => 'payment_awaiting_confirmation',
            'title' => 'Payment submitted — '.number_format((float) $this->payment->amount, 2).' '.$this->payment->currency,
            'body' => ($this->payment->payment_method?->label() ?? 'Manual payment').' by '
                .($this->payment->user?->name ?? $this->payment->company?->name ?? 'a customer')
                .' — verify and confirm.',
            'icon' => 'banknotes',
            'url' => route('admin.sales', absolute: false),
        ];
    }
}
