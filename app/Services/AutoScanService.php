<?php

namespace App\Services;

use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Models\AutoScanUrl;
use App\Models\User;
use Illuminate\Support\Collection;

class AutoScanService
{
    public function __construct(
        private OnboardingImportService $import,
        private ProfileScoutService $profiles,
        private ExperienceService $experience,
        private TimelineService $timeline,
        private EvidenceScoutService $scout,
    ) {}

    /**
     * Whether a user's paid auto-scan subscription is currently active.
     */
    public function isActive(User $user): bool
    {
        return $user->autoScanActive();
    }

    /**
     * Users whose auto-scan subscription is active and who have something to
     * scan: a linked GitHub profile, or queued/failed URLs that have not been
     * successfully scanned yet.
     *
     * @return Collection<int, User>
     */
    public function activeUsers(): Collection
    {
        return User::query()
            ->where('auto_scan_enabled', true)
            ->where('auto_scan_active_until', '>', now())
            ->where(function ($query) {
                $query->whereNotNull('github_url')
                    ->orWhereHas('autoScanUrls', function ($urls) {
                        $urls->whereIn('status', [AutoScanUrl::STATUS_QUEUED, AutoScanUrl::STATUS_FAILED]);
                    });
            })
            ->get();
    }

    /**
     * Run an auto-scan for one user. Queued or previously-failed URLs are
     * fetched individually and imported — any link (GitHub repo, package,
     * article, demo, site…) becomes evidence, strong repos become published
     * projects, and the oldest meaningful ones become dated journal entries.
     * When no URLs are pending, the user's full public GitHub profile is
     * scanned instead. Returns a summary of what was found.
     *
     * @return array{scanned: int, new_evidence: int, new_projects: int, new_journal: int, xp: int, error: string|null}
     */
    public function scan(User $user): array
    {
        [$repos, $pending, $byUrl, $failedCount] = $this->pendingUrls($user);

        // Fall back to the GitHub profile when no URLs are pending at all.
        if ($repos === [] && $pending->isEmpty()) {
            $handle = $this->handle($user);

            if ($handle !== null) {
                $scan = $this->import->scanRepos($handle);
                $repos = (array) ($scan['repos'] ?? []);
            }
        }

        if ($repos === []) {
            $error = $failedCount > 0
                ? 'The queued URLs could not be fetched — check them and try again.'
                : 'Add at least one URL, or link your GitHub profile, to enable automatic scanning.';

            $this->touch($user);

            if ($failedCount > 0) {
                $this->recordRun($user, 0, 0, 0, 0, 0, $error);
            }

            return [
                'scanned' => 0,
                'new_evidence' => 0,
                'new_projects' => 0,
                'new_journal' => 0,
                'xp' => 0,
                'error' => $error,
            ];
        }

        // One start email + one summary email for the whole run instead of
        // one email per scanned item.
        $batcher = app(ScanEmailBatcher::class);
        $batcher->begin($user, 'Auto-scan');
        $batcher->announce(
            $user,
            'Scanning '.count($repos).' link'.(count($repos) === 1 ? '' : 's'),
            collect($repos)->pluck('name')->take(10)->all(),
            [],
        );

        try {
            $result = $this->runImports($user, $repos, $byUrl);
        } catch (\Throwable $e) {
            $batcher->abandon($user);

            throw $e;
        }

        $batcher->complete($user);

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $repos
     * @param  array<string, AutoScanUrl>  $byUrl
     * @return array{scanned: int, new_evidence: int, new_projects: int, new_journal: int, xp: int, error: string|null}
     */
    private function runImports(User $user, array $repos, array $byUrl): array
    {
        $alreadyImported = $user->evidence()->pluck('url')->flip();
        $evidenceRepos = collect($this->import->evidenceRepos($repos))
            ->reject(fn (array $repo) => $alreadyImported->has($repo['html_url'] ?? ''))
            ->values()
            ->all();

        // Projects & dated journal entries come from repositories only —
        // packages, articles, demos, sites and pull requests become evidence.
        $repoSource = fn (array $repo) => $this->import->isRepository($repo);
        $projectRepos = collect($this->import->projectRepos($evidenceRepos))->filter($repoSource)->values()->all();
        $journalRepos = collect($this->import->journalRepos($evidenceRepos))->filter($repoSource)->values()->all();

        $newEvidence = 0;
        $newProjects = 0;
        $newJournal = 0;

        foreach ($evidenceRepos as $repo) {
            $evidence = $this->import->createEvidence($user, $repo, 'auto_scan');

            if ($evidence->wasRecentlyCreated) {
                $newEvidence++;
            }
        }

        foreach ($projectRepos as $repo) {
            if ($this->import->createProject($user, $repo) !== null) {
                $newProjects++;
            }
        }

        foreach ($journalRepos as $repo) {
            $this->import->createJournalEntry($user, $repo);
            $newJournal++;
        }

        // Successfully fetched URLs are now scanned — no re-fetch next run.
        foreach ($byUrl as $row) {
            $row->update([
                'status' => AutoScanUrl::STATUS_SCANNED,
                'last_error' => null,
                'last_scanned_at' => now(),
            ]);
        }

        $xp = $newEvidence * OnboardingImportService::XP_EVIDENCE_SCANNED
            + $newProjects * OnboardingImportService::XP_PROJECT_PUBLISHED
            + $newJournal * OnboardingImportService::XP_JOURNAL_ENTRY;

        if ($xp > 0) {
            $this->experience->award($user, $xp, 'Auto-scan — new work imported for @'.$user->handle());

            $this->timeline->record(
                $user,
                TimelineEventType::MilestoneReached,
                "Auto-scan imported {$newEvidence} new link".($newEvidence === 1 ? '' : 's'),
                $newProjects > 0 || $newJournal > 0
                    ? $newProjects.' project'.($newProjects === 1 ? '' : 's').' published · '.$newJournal.' journal entr'.($newJournal === 1 ? 'y' : 'ies').' dated from repo history.'
                    : 'Your DevID stayed fresh automatically.',
                [
                    'auto_scan' => true,
                    'scanned' => count($repos),
                    'evidence' => $newEvidence,
                    'projects' => $newProjects,
                    'journal' => $newJournal,
                    'xp' => $xp,
                ],
                null,
                Visibility::Public,
            );
        }

        $this->touch($user);

        $this->recordRun($user, count($repos), $newEvidence, $newProjects, $newJournal, $xp, null);

        return [
            'scanned' => count($repos),
            'new_evidence' => $newEvidence,
            'new_projects' => $newProjects,
            'new_journal' => $newJournal,
            'xp' => $xp,
            'error' => null,
        ];
    }

    private function recordRun(
        User $user,
        int $scanned,
        int $newEvidence,
        int $newProjects,
        int $newJournal,
        int $xp,
        ?string $error,
    ): void {
        $user->autoScanRuns()->create([
            'scanned' => $scanned,
            'new_evidence' => $newEvidence,
            'new_projects' => $newProjects,
            'new_journal' => $newJournal,
            'xp' => $xp,
            'error' => $error,
        ]);
    }

    /**
     * Fetch every queued or previously-failed URL. Unresolvable URLs are
     * marked failed so they surface in the UI, while successful fetches
     * return the normalized repo payloads keyed by the URL row that produced
     * them.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: \Illuminate\Database\Eloquent\Collection<int, AutoScanUrl>, 2: array<string, AutoScanUrl>, 3: int}
     */
    private function pendingUrls(User $user): array
    {
        $pending = $user->autoScanUrls()
            ->whereIn('status', [AutoScanUrl::STATUS_QUEUED, AutoScanUrl::STATUS_FAILED])
            ->get();

        if ($pending->isEmpty()) {
            return [[], $pending, [], 0];
        }

        $repos = [];
        $byUrl = [];
        $failedCount = 0;

        foreach ($pending as $row) {
            try {
                $material = $this->scout->fetch($row->url);

                // Profile URLs resolve to a full repository list — import
                // each repo individually so nothing is left behind.
                if (! empty($material['repos']) && is_array($material['repos'])) {
                    foreach ($material['repos'] as $repo) {
                        $repos[] = $repo;
                        $byUrl[$repo['html_url'] ?? $row->url] = $row;
                    }

                    continue;
                }

                $repo = $this->scout->toRepo($material, $row->url);

                $repos[] = $repo;
                $byUrl[$material['repository_url'] ?? $material['profile_url'] ?? $row->url] = $row;
            } catch (\InvalidArgumentException $e) {
                $failedCount++;

                $row->update([
                    'status' => AutoScanUrl::STATUS_FAILED,
                    'last_error' => $e->getMessage(),
                    'last_scanned_at' => now(),
                ]);
            }
        }

        return [$repos, $pending, $byUrl, $failedCount];
    }

    private function handle(User $user): ?string
    {
        $url = $user->github_url;

        if (! $url) {
            return null;
        }

        return $this->profiles->handle($url, 'github');
    }

    private function touch(User $user): void
    {
        $user->forceFill(['last_auto_scan_at' => now()])->save();
    }
}
