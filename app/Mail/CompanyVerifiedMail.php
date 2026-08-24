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
 * Confirms to a company owner that their hiring verification is active and
 * full recruiter/company tools are unlocked.
 */
class CompanyVerifiedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'re verified — recruiter tools unlocked 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.company-verified',
        );
    }
}
