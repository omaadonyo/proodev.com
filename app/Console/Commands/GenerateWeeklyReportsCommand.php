<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWeeklyReportJob;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateWeeklyReportsCommand extends Command
{
    protected $signature = 'os:weekly-reports';

    protected $description = 'Generate weekly growth reports for all active users';

    public function handle(): int
    {
        $users = User::where('last_activity_at', '>=', now()->subDays(14))->get();

        foreach ($users as $user) {
            GenerateWeeklyReportJob::dispatch($user);
        }

        $this->info("Queued weekly reports for {$users->count()} users.");

        return self::SUCCESS;
    }
}
