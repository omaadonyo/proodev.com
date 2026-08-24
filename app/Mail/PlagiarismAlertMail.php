<?php

namespace App\Mail;

use App\Models\PlagiarismStrike;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlagiarismAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PlagiarismStrike $strike) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alert: someone tried to claim your repository as their own',
        );
    }

    public function content(): Content
    {
        $offender = User::find($this->strike->offender_id);
        $owner = User::find($this->strike->owner_id);

        return new Content(
            view: 'mail.plagiarism-alert',
            with: [
                'repoUrl' => $this->strike->repo_url,
                'repoName' => $this->strike->repo_name,
                'repoOwner' => $this->strike->repo_owner,
                'offenderName' => $offender?->name,
                'passportUrl' => $owner ? route('devid', $owner->handle()) : null,
            ],
        );
    }
}
