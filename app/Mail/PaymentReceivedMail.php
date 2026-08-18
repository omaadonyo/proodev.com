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
 * Lightweight acknowledgment sent to the buyer the moment they confirm a
 * manual payment (WorldRemit / bank transfer). It is deliberately separate
 * from the invoice — the receipt is emailed once the admin verifies the
 * transfer and marks the payment paid.
 */
class PaymentReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment received — we\'re verifying it ('
                .$this->payment->invoiceNumber().')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.payment-received',
            with: [
                'method' => $this->payment->payment_method,
                'submittedAt' => $this->payment->customer_confirmed_at ?? now(),
            ],
        );
    }
}
