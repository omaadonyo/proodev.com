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
 * Last email of a scan batch: details on every scanned project.
 *
 * @property array<int, array{title: string, url: string, type: string, score: int|null}> $items
 */
class ScanCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{title: string, url: string, type: string, score: int|null}>  $items
     */
    public function __construct(
        public User $user,
        public string $context,
        public array $items = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->context.' complete — '.count($this->items).' project'.(count($this->items) === 1 ? '' : 's').' scanned',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.scan-completed');
    }
}
