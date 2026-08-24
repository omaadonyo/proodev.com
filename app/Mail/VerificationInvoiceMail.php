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
 * Tells a company owner their hiring-verification invoice is waiting so
 * their job post can go live.
 */
class VerificationInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action required — pay your $'.number_format((float) $this->payment->amount, 0).' hiring verification invoice',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.verification-invoice',
        );
    }
}
