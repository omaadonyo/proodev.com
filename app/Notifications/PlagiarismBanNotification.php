<?php

namespace App\Notifications;

use App\Models\PlagiarismStrike;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PlagiarismBanNotification extends Notification implements ShouldQueue
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
            'type' => 'plagiarism_ban',
            'title' => 'Account banned for plagiarism',
            'body' => 'You repeatedly claimed other developers’ repositories as your own. Your account has been banned and a public notice added to your passport.',
            'icon' => 'hand-raised',
            'url' => $offender ? route('passport', $offender->handle(), absolute: false) : null,
        ];
    }
}
