<?php

namespace App\Notifications;

use App\Models\PlagiarismStrike;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PlagiarismWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PlagiarismStrike $strike) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $offender = User::find($this->strike->offender_id);

        return [
            'type' => 'plagiarism_warning',
            'title' => 'Plagiarism warning',
            'body' => 'Your claim on '.$this->strike->repo_url.' was removed — that repository is not your work. A second violation leads to a ban.',
            'icon' => 'exclamation-triangle',
            'url' => $offender ? route('devid', $offender->handle(), absolute: false) : null,
        ];
    }
}
