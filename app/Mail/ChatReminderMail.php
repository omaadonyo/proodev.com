<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Wirechat\Wirechat\Models\Conversation;

class ChatReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $sender,
        public Conversation $conversation,
        public string $preview,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->sender->name.' sent you a message on ProoDev',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.chat-reminder',
        );
    }
}
