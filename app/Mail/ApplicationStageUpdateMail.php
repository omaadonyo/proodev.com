<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\ApplicationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Automatic candidate update for a hiring-stage change. Copy is calm and
 * factual; rejection feedback (when provided) is framed as employer input,
 * never as an objective verdict.
 */
class ApplicationStageUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public ApplicationEvent $event,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your application for '.$this->application->job->title.' has been updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.application-stage-update',
        );
    }
}