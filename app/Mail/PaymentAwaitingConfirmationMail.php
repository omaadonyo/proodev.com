<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Admin alert when a customer says they have sent a manual payment
 * (WorldRemit / bank transfer) and it is awaiting verification.
 */
class PaymentAwaitingConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
    ) {}

    public function envelope(): Envelope
    {
        $method = $this->payment->payment_method;

        return new Envelope(
            subject: 'Payment submitted for confirmation — '
                .number_format((float) $this->payment->amount, 2).' '.$this->payment->currency
                .' via '.($method?->label() ?? 'manual payment'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.payment-awaiting-confirmation',
            with: [
                'method' => $this->payment->payment_method,
                'submittedAt' => $this->payment->customer_confirmed_at ?? now(),
            ],
        );
    }
}
