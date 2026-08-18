<?php

namespace App\Console\Commands;

use App\Services\AutoScanService;
use Illuminate\Console\Command;

class AutoScanReposCommand extends Command
{
    protected $signature = 'os:auto-scan';

    protected $description = 'Automatically scan queued URLs (repos, articles, packages, demos…) for developers with an active auto-scan subscription';

    public function handle(AutoScanService $autoScan): int
    {
        $users = $autoScan->activeUsers();

        if ($users->isEmpty()) {
            $this->info('No active auto-scan subscribers.');

            return self::SUCCESS;
        }

        $imported = 0;
        $xp = 0;

        foreach ($users as $user) {
            try {
                $result = $autoScan->scan($user);

                if ($result['new_evidence'] > 0) {
                    $imported++;
                }

                $xp += $result['xp'];
            } catch (\Throwable $e) {
                $this->error("Auto-scan failed for {$user->handle()}: {$e->getMessage()}");
            }
        }

        $this->info('Auto-scanned '.$users->count()." developer(s): {$imported} found new work, {$xp} XP awarded.");

        return self::SUCCESS;
    }
}
