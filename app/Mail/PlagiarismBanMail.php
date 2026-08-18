<?php

namespace App\Mail;

use App\Models\PlagiarismStrike;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlagiarismBanMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PlagiarismStrike $strike) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ProoDev account has been banned for plagiarism',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.plagiarism-ban',
            with: [
                'repoUrl' => $this->strike->repo_url,
                'repoOwner' => $this->strike->repo_owner,
                'repoName' => $this->strike->repo_name,
                'strikeNumber' => $this->strike->strike_number,
                'supportEmail' => config('billing.seller.email', 'support@proodev.com'),
            ],
        );
    }
}
