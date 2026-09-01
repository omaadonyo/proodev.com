<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TalentAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $match
     */
    public function __construct(
        public string $alertName,
        public array $match,
        public int $alertId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'talent_alert',
            'title' => 'New talent match: '.$this->match['name'],
            'body' => $this->alertName.' matched a candidate with an Engineering Magnitude of '.$this->match['magnitude'].'/1000.',
            'icon' => 'bell-alert',
            'url' => route('recruiter.candidates.show', $this->match['id'], false),
            'alert_id' => $this->alertId,
        ];
    }
}
