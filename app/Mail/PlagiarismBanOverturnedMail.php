<?php

namespace App\Mail;

use App\Models\PlagiarismStrike;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlagiarismBanOverturnedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PlagiarismStrike $strike) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ProoDev account has been reinstated',
        );
    }

    public function content(): Content
    {
        $offender = $this->strike->offender;

        return new Content(
            view: 'mail.plagiarism-ban-overturned',
            with: [
                'repoUrl' => $this->strike->repo_url,
                'repoOwner' => $this->strike->repo_owner,
                'repoName' => $this->strike->repo_name,
                'passportUrl' => $offender ? route('devid', $offender->handle()) : null,
                'supportEmail' => config('billing.seller.email', 'support@proodev.com'),
            ],
        );
    }
}
