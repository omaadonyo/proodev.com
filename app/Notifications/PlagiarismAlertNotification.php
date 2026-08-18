<?php

namespace App\Notifications;

use App\Models\PlagiarismStrike;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PlagiarismAlertNotification extends Notification implements ShouldQueue
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
        $owner = User::find($this->strike->owner_id);

        return [
            'type' => 'plagiarism_alert',
            'title' => 'Your repository was protected',
            'body' => 'Someone tried to claim '.$this->strike->repo_url.' as their own proof. Our plagiarism guard removed it and warned the account'
                .($offender ? ' ('.$offender->name.')' : '').'.',
            'icon' => 'shield-check',
            'url' => $owner ? route('passport', $owner->handle(), absolute: false) : null,
        ];
    }
}
