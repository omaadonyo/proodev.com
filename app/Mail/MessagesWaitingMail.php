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
 * Teaser email telling a user they have unread messages waiting.
 * Never reveals the sender or the message contents.
 */
class MessagesWaitingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have 2 new messages waiting',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.messages-waiting',
        );
    }
}
