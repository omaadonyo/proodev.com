<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * First email of a scan batch: what is being scanned with what is queued.
 */
class ScanStartedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $scanned
     * @param  array<int, string>  $queued
     */
    public function __construct(
        public User $user,
        public string $context,
        public string $headline,
        public array $scanned = [],
        public array $queued = [],
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->context.' started — '.$this->headline;

        if ($this->queued !== []) {
            $subject .= ' · '.count($this->queued).' queued';
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.scan-started');
    }
}
