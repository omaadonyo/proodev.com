<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RecognitionReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Project $project, public string $type) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $label = str_replace('-', ' ', $this->type);

        return [
            'type' => 'recognition_received',
            'title' => "{$this->project->title} earned \"".ucfirst($label).'" recognition',
            'body' => 'Your engineering work was recognized by the community.',
            'icon' => 'hand-thumb-up',
            'url' => route('projects.show', $this->project, absolute: false),
        ];
    }
}
