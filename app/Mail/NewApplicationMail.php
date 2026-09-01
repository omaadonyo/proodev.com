<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewApplicationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public bool $copy = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'New application — '.$this->application->user->name.' for '.$this->application->job->title;

        return new Envelope(
            subject: $this->copy ? '[Admin copy] '.$subject : $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.new-application',
            with: ['copy' => $this->copy],
        );
    }
}
