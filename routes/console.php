<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('os:weekly-reports')->weeklyOn(1, '8:00');
Schedule::command('os:expire-verifications')->hourly();
Schedule::command('os:decay-streaks')->hourly();
Schedule::command('os:auto-scan')->dailyAt('4:00');
Schedule::command('os:database-backup --email')->cron('0 */'.config('backup.every_hours', 8).' * * *');
Schedule::command('os:hiring-nudges')->weeklyOn(1, '9:00');
