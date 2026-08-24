<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\OnboardingImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Imports the repositories a scout discovered but did not import inline,
 * so very large accounts are fully captured without blocking the UI.
 */
class ImportScoutedReposJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * @param  array<int, array<string, mixed>>  $evidence
     * @param  array<int, array<string, mixed>>  $projects
     * @param  array<int, array<string, mixed>>  $journal
     */
    public function __construct(
        public int $userId,
        public array $evidence = [],
        public array $projects = [],
        public array $journal = [],
        public string $origin = 'background_scan',
    ) {}

    public function handle(OnboardingImportService $import): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        foreach ($this->evidence as $repo) {
            try {
                $import->createEvidence($user, $repo, $this->origin);
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ($this->projects as $repo) {
            try {
                $import->createProject($user, $repo);
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ($this->journal as $repo) {
            try {
                $import->createJournalEntry($user, $repo);
            } catch (\Throwable) {
                continue;
            }
        }
    }
}
