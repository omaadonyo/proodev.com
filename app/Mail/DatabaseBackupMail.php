<?php

namespace App\Mail;

use App\Models\BackupRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers the latest database backup file to the platform admin.
 */
class DatabaseBackupMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public BackupRun $backupRun) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Database backup — '.$this->backupRun->completed_at?->format('Y-m-d H:i'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.database-backup',
        );
    }

    /**
     * Attach the .sql dump from the backups disk.
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('backups', $this->backupRun->file_name)
                ->withMime('application/sql'),
        ];
    }
}
