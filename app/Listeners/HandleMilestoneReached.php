<?php

namespace App\Listeners;

use App\Events\MilestoneReached;
use App\Services\AchievementService;

class HandleMilestoneReached
{
    public function __construct(private AchievementService $achievements) {}

    public function handle(MilestoneReached $event): void
    {
        if ($event->type === 'streak') {
            $keys = [
                7 => 'streak-7',
                30 => 'streak-30',
                100 => 'streak-100',
                365 => 'streak-365',
            ];

            if (isset($keys[$event->value])) {
                $this->achievements->award($event->user, $keys[$event->value]);
            }
        }

        if ($event->type === 'activity') {
            $keys = [
                100 => 'hundred-activities',
                1000 => 'thousand-activities',
            ];

            if (isset($keys[$event->value])) {
                $this->achievements->award($event->user, $keys[$event->value]);
            }
        }
    }
}
