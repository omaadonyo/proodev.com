<?php

namespace App\Services;

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVerificationStatus;
use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Events\EvidenceAdded;
use App\Jobs\AnalyzeEvidenceJob;
use App\Jobs\ExpandJournalWithAi;
use App\Jobs\GenerateProjectSummary;
use App\Models\Evidence;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Support\GitHubApi;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class OnboardingImportService
{
    public const XP_EVIDENCE_SCANNED = 8;

    public const XP_JOURNAL_ENTRY = 10;

    public const XP_PROJECT_PUBLISHED = 100;

    public const XP_SKILL_DETECTED = 5;

    public const MAX_EVIDENCE = 300;

    public const MAX_PROJECTS = 100;

    public const MAX_JOURNAL = 100;

    /**
     * How much is imported inline during a live scout before the rest is
     * handed to the background queue.
     */
    public const INLINE_EVIDENCE = 12;

    public const INLINE_PROJECTS = 4;

    public const INLINE_JOURNAL = 4;

    public function __construct(
        private RepoScanService $scanner,
        private ProjectScoutService $projectScout,
        private ExperienceService $experience,
    ) {}

    /**
     * Scan the full public repository history for a GitHub handle.
     *
     * @return array<string, mixed>
     */
    public function scanRepos(string $handle): array
    {
        return $this->scanner->scan($handle);
    }

    /**
     * Repositories that become evidence: everything that is genuinely the
     * user's own public work — forks and archived repos are skipped.
     *
     * @param  array<int, array<string, mixed>>  $repos
     * @return array<int, array<string, mixed>>
     */
    public function evidenceRepos(array $repos): array
    {
        return collect($repos)
            ->reject(fn (array $repo) => $repo['fork'] || $repo['archived'])
            ->values()
            ->take(self::MAX_EVIDENCE)
            ->all();
    }

    /**
     * Whether a normalized repo payload is an actual repository (not a
     * pull request or other lightweight evidence item).
     */
    public function isRepository(array $repo): bool
    {
        return ($repo['evidence_type'] ?? null) !== 'pull-request'
            && in_array((string) ($repo['source'] ?? 'github'), ['github', 'gitlab', 'bitbucket'], true);
    }

    /**
     * Repositories strong enough to be drafted as published projects —
     * ranked by stars then recency, and only repos with real substance.
     *
     * @param  array<int, array<string, mixed>>  $repos
     * @return array<int, array<string, mixed>>
     */
    public function projectRepos(array $repos): array
    {
        return collect($repos)
            ->reject(fn (array $repo) => $repo['fork'] || $repo['archived'])
            ->filter(fn (array $repo) => $repo['stars'] > 0 || $repo['size'] > 0 || filled($repo['description']))
            ->sortByDesc('stars')
            ->sortByDesc(fn (array $repo) => $repo['pushed_at'] ?? $repo['created_at'] ?? '')
            ->take(self::MAX_PROJECTS)
            ->values()
            ->all();
    }

    /**
     * Repositories that become dated journal entries — the oldest meaningful
     * work first, so the journal reads as a story.
     *
     * @param  array<int, array<string, mixed>>  $repos
     * @return array<int, array<string, mixed>>
     */
    public function journalRepos(array $repos): array
    {
        return collect($repos)
            ->reject(fn (array $repo) => $repo['fork'] || $repo['archived'])
            ->filter(fn (array $repo) => filled($repo['description']) || $repo['topics'] !== [])
            ->sortBy(fn (array $repo) => $repo['created_at'] ?? $repo['pushed_at'] ?? '')
            ->take(self::MAX_JOURNAL)
            ->values()
            ->all();
    }

    /**
     * Create an evidence record for a single scanned repo and queue its
     * analysis. The material fetched during the scan is stored on the
     * metadata, so the analysis job never needs to re-fetch GitHub.
     *
     * @param  array<string, mixed>  $repo
     */
    public function createEvidence(User $user, array $repo, string $origin = 'manual'): Evidence
    {
        $url = $repo['html_url'];

        $existing = Evidence::where('user_id', $user->id)->where('url', $url)->first();

        if ($existing) {
            return $existing;
        }

        $evidenceType = EvidenceType::tryFrom((string) ($repo['evidence_type'] ?? '')) ?? EvidenceType::GithubRepository;
        $source = (string) ($repo['source'] ?? 'github');

        $material = [
            'title' => $repo['name'],
            'description' => $repo['description'],
            'demo_url' => $repo['homepage'],
            'repository_url' => $url,
            'tech_stack' => array_values(array_filter([$repo['language'], ...$repo['topics']])),
            'stars' => $repo['stars'],
            'forks' => $repo['forks'],
            'content' => $this->repoContent($repo),
            'profile_url' => $url,
            'source' => $source,
            'type' => $evidenceType->value,
        ];

        $evidence = Evidence::create([
            'user_id' => $user->id,
            'type' => $evidenceType,
            'title' => $repo['name'],
            'url' => $url,
            'source' => $source,
            'description' => Str::limit((string) $repo['description'], 200) ?: null,
            'status' => EvidenceStatus::Pending,
            'metadata' => [
                'material' => $material,
                'repo' => $repo,
                'imported' => true,
                'scanned_at' => now()->toIso8601String(),
            ],
        ]);

        TimelineEvent::create([
            'user_id' => $user->id,
            'type' => TimelineEventType::EvidenceAdded,
            'title' => $evidence->title,
            'description' => 'Added '.$evidenceType->label().' evidence from the import scan.',
            'data' => ['evidence_id' => $evidence->id, 'imported' => true],
            'target_type' => Evidence::class,
            'target_id' => $evidence->id,
            'visibility' => Visibility::Public,
            'occurred_at' => now(),
        ]);

        app(RepoClaimService::class)->record($user, $url, $origin);

        AnalyzeEvidenceJob::dispatch($evidence);

        EvidenceAdded::dispatch($evidence);

        return $evidence;
    }

    /**
     * Draft and publish a project from a scanned repo, dated from the repo's
     * own history so the DevID reflects when the work actually happened.
     *
     * @param  array<string, mixed>  $repo
     */
    public function createProject(User $user, array $repo): ?Project
    {
        $url = $repo['html_url'];

        $existing = Project::where('user_id', $user->id)->where('repository_url', $url)->first();

        if ($existing) {
            return null;
        }

        $material = [
            'title' => $repo['name'],
            'tagline' => $repo['description'],
            'demo_url' => $repo['homepage'],
            'repository_url' => $url,
            'tech_stack' => array_values(array_filter([$repo['language'], ...$repo['topics']])),
            'profile_url' => $url,
            'source' => 'github',
            'content' => $this->materialContent($repo),
        ];

        $facts = collect([
            'Source URL: '.$url,
            'Title: '.($material['title'] ?? ''),
            'Tagline: '.($material['tagline'] ?? 'Not provided'),
            'Tech signals: '.implode(', ', $material['tech_stack']),
            'Stars: '.$repo['stars'].' · Forks: '.$repo['forks'],
            'Content: '.Str::limit((string) $material['content'], 8000),
        ])->filter()->implode("\n\n");

        $draft = $this->projectScout->draft($facts, $material);
        $score = $this->projectScout->score($material, $draft);

        $publishedAt = $this->repoDate($repo['pushed_at'] ?? $repo['created_at']);

        $project = Project::create([
            'user_id' => $user->id,
            'title' => (string) ($draft['title'] ?: $repo['name']),
            'slug' => Str::slug((string) ($draft['title'] ?: $repo['name'])).'-'.Str::lower(Str::random(6)),
            'tagline' => (string) ($draft['tagline'] ?: ''),
            'problem' => (string) ($draft['problem'] ?: ''),
            'solution' => (string) ($draft['solution'] ?: ''),
            'architecture' => (string) ($draft['architecture'] ?? ''),
            'tech_stack' => array_values(array_filter((array) ($draft['tech_stack'] ?? []))),
            'engineering_decisions' => array_values(array_filter((array) ($draft['engineering_decisions'] ?? []))),
            'lessons_learned' => (string) ($draft['lessons_learned'] ?? ''),
            'demo_url' => $draft['demo_url'] ?: $repo['homepage'],
            'repository_url' => $url,
            'status' => ProjectStatus::Published,
            'published_at' => $publishedAt,
            'ai_score' => $score,
            'verification_status' => ProjectVerificationStatus::Unverified,
        ]);

        app(TimelineService::class)->record(
            $user,
            TimelineEventType::ProjectPublished,
            "Published project: {$project->title}",
            $project->tagline,
            [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'title' => $project->title,
                'tagline' => $project->tagline,
                'imported' => true,
            ],
            $project,
            Visibility::Public,
            $publishedAt,
        );

        dispatch(new GenerateProjectSummary($project));

        return $project;
    }

    /**
     * Create a dated journal entry from a scanned repo.
     *
     * @param  array<string, mixed>  $repo
     */
    public function createJournalEntry(User $user, array $repo): JournalEntry
    {
        $title = 'Started '.$repo['name'];

        $existing = JournalEntry::where('user_id', $user->id)->where('title', $title)->first();

        if ($existing) {
            return $existing;
        }

        $lastUpdated = $this->repoDate($repo['pushed_at'] ?? $repo['updated_at'] ?? null);

        $content = collect([
            "I started building {$repo['name']} to solve a real problem.",
            $repo['description'] ? 'The idea: '.$repo['description'].'.' : null,
            $repo['language'] ? "It is built with {$repo['language']}." : null,
            $repo['topics'] !== [] ? 'Focus areas: '.implode(', ', array_slice($repo['topics'], 0, 4)).'.' : null,
            $repo['stars'] > 0 ? "The work has earned {$repo['stars']} stars and {$repo['forks']} forks from the community." : null,
            $lastUpdated ? 'Last updated '.$lastUpdated->format('F j, Y').', keeping it alive and evolving since.' : null,
            'Repository: '.$repo['html_url'],
        ])->filter()->implode("\n\n");

        $publishedAt = $this->repoDate($repo['created_at'] ?? $repo['pushed_at']);

        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'title' => $title,
            'content' => $content,
            'visibility' => Visibility::Public,
            'ai_processed' => false,
            'published_at' => $publishedAt,
        ]);

        app(TimelineService::class)->record(
            $user,
            TimelineEventType::JournalPublished,
            'Published journal entry: '.$title,
            null,
            ['journal_id' => $entry->id, 'imported' => true],
            $entry,
            Visibility::Public,
            $publishedAt,
        );

        dispatch(new ExpandJournalWithAi($entry));

        return $entry;
    }

    /**
     * Award experience for everything the scan found. Called once at the end
     * of onboarding so the final level is entirely derived from the repos.
     *
     * @param  array<string, mixed>  $profile
     * @param  array<string, int>  $counts
     */
    public function awardScanExperience(User $user, array $profile, array $counts): void
    {
        $xp = 0;

        $xp += (int) $counts['evidence'] * self::XP_EVIDENCE_SCANNED;
        $xp += (int) $counts['projects'] * self::XP_PROJECT_PUBLISHED;
        $xp += (int) $counts['journal'] * self::XP_JOURNAL_ENTRY;

        $languages = array_slice($profile['languages'] ?? [], 0, 5);
        $xp += count($languages) * self::XP_SKILL_DETECTED;

        $stars = (int) ($profile['total_stars'] ?? 0);
        $followers = (int) ($profile['followers'] ?? 0);
        $years = (int) ($profile['account_years'] ?? 0);

        $xp += min(100, $stars);
        $xp += min(100, $followers) * 2;
        $xp += $years * 5;

        if ($xp > 0) {
            $this->experience->award($user, $xp, 'Scout onboarding — repos scanned and work imported');
        }

        $this->awardImportAchievements($user, $counts);
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function awardImportAchievements(User $user, array $counts): void
    {
        $achievements = app(AchievementService::class);

        foreach ([1 => 'first-project', 5 => 'projects-5', 10 => 'projects-10'] as $threshold => $key) {
            if ((int) $counts['projects'] >= $threshold) {
                $achievements->award($user, $key, ['imported' => true]);
            }
        }

        if ((int) $counts['journal'] >= 1) {
            $achievements->award($user, 'first-journal', ['imported' => true]);
        }
    }

    /**
     * Content for the project draft. Single-URL scouts pass the material they
     * already fetched; repo scans fall back to the repo summary plus README.
     *
     * @param  array<string, mixed>  $repo
     */
    private function materialContent(array $repo): string
    {
        if (array_key_exists('content', $repo) && is_string($repo['content']) && trim($repo['content']) !== '') {
            return trim($repo['content']);
        }

        return $this->repoContent($repo)."\n\n".$this->readmeFor($repo);
    }

    /**
     * @param  array<string, mixed>  $repo
     */
    private function repoContent(array $repo): string
    {
        return collect([
            $repo['description'],
            $repo['language'] ? 'Primary language: '.$repo['language'].'.' : null,
            $repo['topics'] !== [] ? 'Topics: '.implode(', ', $repo['topics']).'.' : null,
        ])->filter()->implode("\n\n");
    }

    /**
     * Fetch the repository README so AI drafting has real source material.
     * Never throws — a missing or private readme just yields empty content.
     *
     * @param  array<string, mixed>  $repo
     */
    private function readmeFor(array $repo): string
    {
        $owner = Str::before($repo['full_name'] ?: $repo['name'], '/');

        if (! $owner || ! $repo['name']) {
            return '';
        }

        try {
            $data = GitHubApi::get("https://api.github.com/repos/{$owner}/{$repo['name']}/readme");

            if (! isset($data['content'])) {
                return '';
            }

            $decoded = base64_decode($data['content'], true);

            return $decoded ? (string) $decoded : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function repoDate(?string $iso): CarbonInterface
    {
        if (! $iso) {
            return now();
        }

        try {
            return Carbon::parse($iso);
        } catch (\Throwable) {
            return now();
        }
    }
}
