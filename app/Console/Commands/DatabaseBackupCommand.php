<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackupMail;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'os:database-backup
                            {--email : Send the backup file to the configured admin address}';

    protected $description = 'Dump the entire database to a .sql file (optionally emailed to the admin)';

    public function handle(DatabaseBackupService $backup): int
    {
        $this->info('Starting database backup…');

        $run = $backup->run();

        if ($run->status !== 'success') {
            $this->error("Backup failed: {$run->error}");

            return self::FAILURE;
        }

        $this->info('Backup created: '.$run->file_name.' ('.$run->humanSize().')');

        if ($this->option('email')) {
            $this->info('Sending backup to '.config('backup.email_to').'…');

            Mail::to(config('backup.email_to'))->send(new DatabaseBackupMail($run->fresh()));

            $run->update(['emailed_at' => now()]);

            $this->info('Backup emailed successfully.');
        }

        return self::SUCCESS;
    }
}
