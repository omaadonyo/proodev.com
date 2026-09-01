<?php

namespace App\Mail;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public VerificationRequest $request,
        public bool $copy = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'Verification approved — '.($this->request->label ?: 'professional identity');

        return new Envelope(
            subject: $this->copy ? '[Admin copy] '.$subject : $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.verification-approved',
            with: ['copy' => $this->copy],
        );
    }
}
