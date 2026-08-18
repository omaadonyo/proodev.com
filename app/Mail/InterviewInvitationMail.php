<?php

namespace App\Mail;

use App\Models\RecruiterInterview;
use App\Support\CalendarInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruiterInterview $interview,
        public int $durationMinutes = 60,
    ) {}

    public function envelope(): Envelope
    {
        $candidate = $this->interview->candidate;
        $recruiter = $this->interview->recruiter;

        return new Envelope(
            subject: 'Interview invitation — '.$candidate->name.' · '.$recruiter->name.' on ProoDev',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.interview-invitation',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => CalendarInvite::for($this->interview, $this->interview->recruiter, $this->durationMinutes),
                'interview-invite.ics',
            )->withMime('text/calendar'),
        ];
    }
}
