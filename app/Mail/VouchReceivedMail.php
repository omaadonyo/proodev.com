<?php

namespace App\Mail;

use App\Models\Vouch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VouchReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Vouch $vouch) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->vouch->voucher->name.' vouched for you on ProoDev',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.vouch-received',
        );
    }
}
