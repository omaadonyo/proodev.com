<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VerificationInvoiceNotification extends Notification implements ShouldQueue
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
            'type' => 'verification_invoice_pending',
            'title' => 'Invoice #'.$this->payment->id.' — $'.number_format((float) $this->payment->amount, 0).' hiring verification',
            'body' => 'Pay this invoice to verify '.($this->payment->company?->name ?? 'your company').' and publish your job post.',
            'icon' => 'receipt-percent',
            'url' => route('checkout', $this->payment, absolute: false),
        ];
    }
}
