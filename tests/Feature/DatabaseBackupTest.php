<?php

use App\Mail\DatabaseBackupMail;
use App\Models\BackupRun;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Storage::fake('backups');
});

test('the backup command creates a .sql dump and records a successful run', function () {
    $this->artisan('os:database-backup')
        ->expectsOutputToContain('Backup created')
        ->assertExitCode(0);

    $run = BackupRun::query()->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('success')
        ->and($run->file_name)->toEndWith('.sql')
        ->and($run->file_size)->toBeGreaterThan(0)
        ->and($run->completed_at)->not->toBeNull();

    Storage::disk('backups')->assertExists($run->file_name);
});

test('the dump contains tables and insert statements', function () {
    $service = app(DatabaseBackupService::class);

    $sql = $service->dumpSql();

    expect($sql)->toContain('CREATE TABLE')
        ->and($sql)->toContain('INSERT INTO');
});

test('database size is reported', function () {
    if (DB::connection()->getDriverName() === 'sqlite' && DB::connection()->getDatabaseName() === ':memory:') {
        $this->markTestSkipped('In-memory SQLite has no file to measure.');
    }

    $service = app(DatabaseBackupService::class);

    expect($service->databaseSize())->toBeGreaterThan(0);
});

test('the --email option sends the backup to the configured admin', function () {
    $this->artisan('os:database-backup --email')->assertExitCode(0);

    $run = BackupRun::query()->first();

    Mail::assertQueued(DatabaseBackupMail::class, fn ($mail) => $mail->hasTo(config('backup.email_to')));

    expect($run->emailed_at)->not->toBeNull();
});

test('admin can view the backups page and run a backup from the UI', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.settings.backups')
        ->assertOk()
        ->assertSee('Database backups')
        ->call('runBackup')
        ->assertDispatched('toast-show');

    expect(BackupRun::count())->toBe(1);
});
