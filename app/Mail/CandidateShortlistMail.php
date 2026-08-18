<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateShortlistMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public User $recruiter,
        public array $rows,
        public string $title = 'Candidate shortlist',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title.' — '.$this->recruiter->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.shortlist',
        );
    }
}
