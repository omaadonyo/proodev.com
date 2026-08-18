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
 * Admin alert for a manual (WorldRemit / bank transfer) payment that was
 * confirmed. Includes the payout details the team needs to settle the funds.
 */
class PayoutNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
    ) {}

    public function envelope(): Envelope
    {
        $method = $this->payment->payment_method;

        return new Envelope(
            subject: 'Payout needed — '.number_format((float) $this->payment->amount, 2).' '.$this->payment->currency
                .' via '.($method?->label() ?? 'manual payment'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.payout-notification',
            with: ['method' => $this->payment->payment_method],
        );
    }
}
