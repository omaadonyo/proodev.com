<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WeeklyReportNotification;
use App\Services\WeeklyReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class GenerateWeeklyReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public User $user) {}

    public function handle(WeeklyReportService $service): void
    {
        $report = $service->generate($this->user, Carbon::now()->startOfWeek());

        if ($report->data['activity_count'] > 0) {
            $this->user->notify(new WeeklyReportNotification($report));
        }
    }
}
