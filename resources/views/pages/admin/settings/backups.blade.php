<?php

use App\Mail\DatabaseBackupMail;
use App\Models\BackupRun;
use App\Services\DatabaseBackupService;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Database Backups')] class extends Component
{
    use WithPagination;

    public function runBackup(): void
    {
        $run = app(DatabaseBackupService::class)->run();

        if ($run->status !== 'success') {
            Flux::toast(variant: 'danger', text: 'Backup failed: '.$run->error);

            return;
        }

        unset($this->runs);

        Flux::toast(variant: 'success', text: 'Backup created: '.$run->file_name.' ('.$run->humanSize().')');
    }

    public function email(BackupRun $run): void
    {
        if ($run->status !== 'success') {
            Flux::toast(variant: 'warning', text: 'Only successful backups can be emailed.');

            return;
        }

        Mail::to(config('backup.email_to'))->send(new DatabaseBackupMail($run));

        $run->update(['emailed_at' => now()]);

        unset($this->runs);

        Flux::toast(variant: 'success', text: 'Backup emailed to '.config('backup.email_to').'.');
    }

    public function download(BackupRun $run)
    {
        if ($run->status !== 'success' || ! Storage::disk('backups')->exists($run->file_name)) {
            Flux::toast(variant: 'danger', text: 'Backup file not found on disk.');

            return;
        }

        unset($this->runs);

        return response()->streamDownload(
            fn () => readfile(Storage::disk('backups')->path($run->file_name)),
            $run->file_name,
            ['Content-Type' => 'application/sql'],
        );
    }

    #[Computed]
    public function stats(): array
    {
        $service = app(DatabaseBackupService::class);
        $latest = $service->latestSuccessful();

        return [
            'dbSize' => $service->humanSize($service->databaseSize()),
            'lastBackup' => $latest?->completed_at,
            'lastSize' => $latest?->humanSize(),
            'total' => BackupRun::where('status', 'success')->count(),
            'schedule' => 'every '.config('backup.every_hours', 8).' hours',
            'emailTo' => config('backup.email_to'),
        ];
    }

    #[Computed]
    public function runs()
    {
        return BackupRun::query()
            ->latest('started_at')
            ->paginate(15);
    }
}
?>

<x-pages::admin.settings.layout :heading="__('Database backups')" :subheading="'A full .sql dump of the entire database, generated automatically every '.config('backup.every_hours', 8).' hours and emailed to '.config('backup.email_to').'.'">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Database size</div>
            <div class="text-2xl font-bold">{{ $this->stats['dbSize'] }}</div>
            <div class="mt-1 text-xs text-zinc-400">Current size on disk</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Last backup</div>
            <div class="text-2xl font-bold">
                {{ $this->stats['lastBackup'] ? $this->stats['lastBackup']->diffForHumans() : '-' }}
            </div>
            <div class="mt-1 text-xs text-zinc-400">
                {{ $this->stats['lastBackup'] ? $this->stats['lastBackup']->format('M j, Y g:i A') : 'No backup yet' }}
            </div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Last size</div>
            <div class="text-2xl font-bold">{{ $this->stats['lastSize'] ?? '-' }}</div>
            <div class="mt-1 text-xs text-zinc-400">Latest .sql dump</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Schedule</div>
            <div class="text-2xl font-bold">{{ $this->stats['schedule'] }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ $this->stats['total'] }} successful backups</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="sm">Backup runs</flux:heading>
        <flux:button wire:click="runBackup" wire:loading.attr="disabled">
            <flux:icon name="arrow-path" variant="micro" />
            Run backup now
        </flux:button>
    </div>

    <div class="overflow-hidden">
        <flux:table :paginate="$this->runs">
            <flux:table.columns>
                <flux:table.column>File</flux:table.column>
                <flux:table.column>Size</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Started</flux:table.column>
                <flux:table.column>Emailed</flux:table.column>
                <flux:table.column class="text-right">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->runs as $run)
                    <flux:table.row :key="$run->id">
                        <flux:table.cell class="font-medium">{{ $run->file_name }}</flux:table.cell>
                        <flux:table.cell>{{ $run->humanSize() }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge
                                :color="$run->status === 'success' ? 'green' : ($run->status === 'failed' ? 'red' : 'yellow')"
                                size="sm"
                            >
                                {{ $run->status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $run->started_at?->format('M j, Y g:i A') }}</flux:table.cell>
                        <flux:table.cell>{{ $run->emailed_at?->format('M j, Y g:i A') ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <div class="flex justify-end gap-1">
                                <flux:button
                                    variant="subtle"
                                    size="sm"
                                    wire:click="email({{ $run->id }})"
                                    :disabled="$run->status !== 'success'"
                                >
                                    <flux:icon name="envelope" variant="micro" />
                                    Email
                                </flux:button>
                                <flux:button
                                    variant="subtle"
                                    size="sm"
                                    wire:click="download({{ $run->id }})"
                                    :disabled="$run->status !== 'success'"
                                >
                                    <flux:icon name="arrow-down-tray" variant="micro" />
                                    Download
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    @php
        $lastFailed = BackupRun::where('status', 'failed')->latest('started_at')->first();
    @endphp
    @if ($lastFailed)
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/20">
            <flux:heading size="sm" class="text-red-700 dark:text-red-400">Last failed backup</flux:heading>
            <p class="mt-1 text-sm text-red-700/80 dark:text-red-300/80">{{ $lastFailed->error }}</p>
        </div>
    @endif
</x-pages::admin.settings.layout>
