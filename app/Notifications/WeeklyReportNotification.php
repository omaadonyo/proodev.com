<?php

namespace App\Notifications;

use App\Models\WeeklyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WeeklyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WeeklyReport $report) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable->wantsNotification('weekly_reports')) {
            return [];
        }

        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $data = $this->report->data;

        return [
            'type' => 'weekly_report',
            'title' => 'Your weekly engineering report is ready',
            'body' => sprintf(
                '%d project(s), %d activity(ies), %d XP gained, %d%% growth this week.',
                $data['projects_published'] ?? 0,
                $data['activity_count'] ?? 0,
                $data['xp_gained'] ?? 0,
                $data['growth_percentage'] ?? 0,
            ),
            'icon' => 'document-chart-bar',
            'url' => route('growth', absolute: false),
        ];
    }
}
