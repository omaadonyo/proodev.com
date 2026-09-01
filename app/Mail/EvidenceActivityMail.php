<?php

namespace App\Mail;

use App\Models\Evidence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvidenceActivityMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Evidence $evidence,
        public bool $analyzed = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->analyzed
                ? 'Your scan of '.$this->evidence->title.' is ready'
                : 'Evidence added: '.$this->evidence->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.evidence-activity',
            with: ['analyzed' => $this->analyzed],
        );
    }
}
