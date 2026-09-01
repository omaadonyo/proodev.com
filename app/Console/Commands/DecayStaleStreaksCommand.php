<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DecayStaleStreaksCommand extends Command
{
    protected $signature = 'os:decay-streaks';

    protected $description = 'Reset streaks for users inactive for more than 48 hours';

    public function handle(): int
    {
        $stale = User::where('streak_count', '>', 0)
            ->where(function ($q) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', now()->subHours(48));
            })
            ->update(['streak_count' => 0]);

        $this->info("Reset {$stale} stale streak(s).");

        return self::SUCCESS;
    }
}
