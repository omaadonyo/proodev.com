<?php

use App\Jobs\ImportScoutedReposJob;
use App\Models\Skill;
use App\Models\User;
use App\Services\EngineeringMagnitudeService;
use App\Services\LevelService;
use App\Services\OnboardingImportService;
use App\Services\ProfileBioService;
use App\Services\ProfileScoutService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Complete your profile')]
#[Layout('layouts.onboarding')]
class extends Component
{
    public ?string $url = null;

    public string $phase = 'input';

    public ?string $source = null;

    public ?string $handle = null;

    public array $log = [];

    public int $stage = 0;

    public ?array $result = null;

    public ?string $error = null;

    // GitHub repo-scan pipeline state.
    public array $plan = [];

    public int $step = 0;

    public int $totalSteps = 0;

    public array $completed = [];

    public ?array $scan = null;

    public int $xp = 0;

    public ?array $summary = null;

    public int $queued = 0;

    // The live passport being built on the right side of the screen.
    public array $passport = [
        'profile' => null,
        'stats' => ['repos' => 0, 'evidence' => 0, 'projects' => 0, 'journal' => 0],
        'skills' => [],
        'evidence' => [],
        'projects' => [],
        'journal' => [],
        'factors' => [],
        'level' => null,
        'magnitude' => null,
    ];

    public function mount(): void
    {
        if (auth()->user()->hasCompletedOnboarding()) {
            $this->redirect(route('home'), navigate: true);
        }
    }

    /**
     * GitHub handle behind the current user's linked GitHub URL, or null
     * when no account is linked.
     */
    #[Computed]
    public function githubHandle(): ?string
    {
        $url = auth()->user()->github_url;

        if (! $url) {
            return null;
        }

        $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

        return $parts[0] ?? null;
    }

    /**
     * Whether the URL currently typed into the scout input points at a
     * GitHub profile page.
     */
    #[Computed]
    public function urlLooksLikeGithubProfile(): bool
    {
        $url = trim($this->url ?? '');

        if ($url === '' || ! str_contains($url, '://')) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        if (! Str::contains($host, 'github.')) {
            return false;
        }

        return trim((string) parse_url($url, PHP_URL_PATH), '/') !== '';
    }

    public function begin(): void
    {
        $this->resetErrorBag();
        $this->error = null;
        $this->validate(['url' => ['required', 'string', 'url', 'max:255']]);

        $url = $this->normalizeUrl(trim($this->url));

        try {
            $scout = app(ProfileScoutService::class);
            $this->source = $scout->source($url);
            $this->handle = $scout->handle($url, $this->source ?? '');
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return;
        }

        if (! $this->source) {
            $this->error = 'Please enter a supported profile URL (GitHub, GitLab, Bitbucket or LinkedIn).';

            return;
        }

        if ($this->source === 'github' && ! $this->handle) {
            $this->error = 'We could not find a username in that GitHub URL.';

            return;
        }

        $this->url = $url;

        if ($this->source === 'github') {
            $this->beginGithubScan();

            return;
        }

        $this->beginLegacy();
    }

    public function tick(): void
    {
        if ($this->phase !== 'scouting') {
            return;
        }

        $this->stage++;

        try {
            if ($this->source === 'github') {
                $this->tickGithub();
            } else {
                $this->tickLegacy();
            }
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            $this->phase = 'input';
        } catch (Throwable) {
            $this->error = 'Something went wrong while scouting your profile. Please try again.';
            $this->phase = 'input';
        }
    }

    public function skip(): void
    {
        auth()->user()->completeOnboarding();
        $this->redirect(route('home'), navigate: true);
    }

    public function finish(): void
    {
        $this->redirect(route('home'), navigate: true);
    }

    #[Computed]
    public function spinner(): string
    {
        return ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'][$this->stage % 10];
    }

    #[Computed]
    public function currentTask(): ?string
    {
        return $this->plan[$this->step]['label'] ?? null;
    }

    #[Computed]
    public function progress(): int
    {
        return (int) round(($this->step / max(1, $this->totalSteps)) * 100);
    }

    #[Computed]
    public function levelSnapshot(): array
    {
        return app(LevelService::class)->snapshot($this->xp);
    }

    private function beginGithubScan(): void
    {
        $this->phase = 'scouting';
        $this->stage = 0;
        $this->step = 0;
        $this->queued = 0;
        // The finalize step is appended at the end of the plan once the repo
        // scan reveals how much work needs importing.
        $this->plan = [
            ['kind' => 'profile', 'label' => 'Profile', 'index' => 1, 'total' => 2],
            ['kind' => 'repos', 'label' => 'Repositories', 'index' => 2, 'total' => 2],
        ];
        $this->totalSteps = count($this->plan);

        $this->log = [
            $this->term('cmd', '$ proodev scout --github '.$this->handle),
            $this->term('info', 'Resolving @'.$this->handle.' …'),
        ];
    }

    private function beginLegacy(): void
    {
        $this->phase = 'scouting';
        $this->stage = 0;

        $this->log = [
            $this->term('cmd', '$ proodev scout --profile '.$this->url),
            $this->term('info', 'Resolving '.Str::ucfirst($this->source).' profile …'),
        ];
    }

    private function tickGithub(): void
    {
        if ($this->step >= count($this->plan)) {
            $this->phase = 'done';

            return;
        }

        $step = $this->plan[$this->step];

        match ($step['kind']) {
            'profile' => $this->runProfileStep(),
            'repos' => $this->runReposStep(),
            'evidence' => $this->runEvidenceStep($step),
            'project' => $this->runProjectStep($step),
            'journal' => $this->runJournalStep($step),
            'finalize' => $this->runFinalizeStep(),
        };

        $this->step++;
    }

    private function runProfileStep(): void
    {
        $profile = app(ProfileScoutService::class)->githubProfile($this->handle);

        $this->result = array_merge($profile, [
            'source' => 'github',
            'handle' => $this->handle,
            'profile_url' => $this->url,
        ]);

        $this->passport['profile'] = [
            'name' => $profile['name'] ?? $this->handle,
            'handle' => $this->handle,
            'avatar' => $profile['avatar_url'] ?? null,
            'headline' => $profile['headline'] ?? null,
            'location' => $profile['location'] ?? null,
        ];

        $this->passport['stats']['repos'] = (int) ($profile['public_repos'] ?? 0);

        $this->passport['skills'] = array_values(array_unique(array_merge(
            $this->passport['skills'],
            array_slice($profile['languages'] ?? [], 0, 6),
        )));

        $this->passport['factors'] = $this->passportFactors();
        $this->completed[] = 'profile';

        $this->log[] = $this->term(
            'ok',
            'Profile fetched · @'.$this->handle.' · '.number_format((int) ($profile['followers'] ?? 0)).' followers · ★ '.number_format((int) ($profile['total_stars'] ?? 0)),
        );
        $this->log[] = $this->term('info', 'Scanning every public repository for evidence…');
    }

    private function runReposStep(): void
    {
        $import = app(OnboardingImportService::class);
        $this->scan = $import->scanRepos($this->handle);

        $repos = $this->scan['repos'] ?? [];
        $this->passport['stats']['repos'] = max($this->passport['stats']['repos'], count($repos));

        if (($this->scan['failed'] ?? false) || $repos === []) {
            $this->log[] = $this->term('warn', 'No public repositories found — continuing with your profile.');
        } else {
            $this->log[] = $this->term('ok', 'Scanned '.count($repos).' public repositories', '+'.count($repos).' repos');

            foreach (collect($repos)->pluck('name')->chunk(3) as $chunk) {
                $this->log[] = $this->term('dim', '→ '.$chunk->implode(' · '));
            }
        }

        $evidenceRepos = $import->evidenceRepos($repos);
        $projectRepos = $import->projectRepos($repos);
        $journalRepos = $import->journalRepos($repos);

        // Import a first batch inline; everything else is queued so very
        // large accounts are fully captured without blocking the scan.
        $inlineEvidence = array_slice($evidenceRepos, 0, OnboardingImportService::INLINE_EVIDENCE);
        $inlineProjects = array_slice($projectRepos, 0, OnboardingImportService::INLINE_PROJECTS);
        $inlineJournal = array_slice($journalRepos, 0, OnboardingImportService::INLINE_JOURNAL);

        $queuedEvidence = array_slice($evidenceRepos, OnboardingImportService::INLINE_EVIDENCE);
        $queuedProjects = array_slice($projectRepos, OnboardingImportService::INLINE_PROJECTS);
        $queuedJournal = array_slice($journalRepos, OnboardingImportService::INLINE_JOURNAL);

        $this->queued = count($queuedEvidence) + count($queuedProjects) + count($queuedJournal);

        if ($this->queued > 0) {
            ImportScoutedReposJob::dispatch(
                auth()->id(),
                $queuedEvidence,
                $queuedProjects,
                $queuedJournal,
                'onboarding',
            );

            $this->log[] = $this->term(
                'info',
                'Queued '.number_format($this->queued).' item'.($this->queued === 1 ? '' : 's').' for background scanning — they will appear on your DevID shortly.',
            );
        }

        $evidenceChunks = array_chunk($inlineEvidence, 3);
        $journalChunks = array_chunk($inlineJournal, 2);

        $plan = [];

        foreach ($evidenceChunks as $i => $chunk) {
            $plan[] = ['kind' => 'evidence', 'repos' => $chunk, 'label' => 'Evidence', 'index' => $i + 1, 'total' => count($evidenceChunks)];
        }

        foreach ($inlineProjects as $i => $repo) {
            $plan[] = ['kind' => 'project', 'repo' => $repo, 'label' => 'Projects', 'index' => $i + 1, 'total' => count($inlineProjects)];
        }

        foreach ($journalChunks as $i => $chunk) {
            $plan[] = ['kind' => 'journal', 'repos' => $chunk, 'label' => 'Journal', 'index' => $i + 1, 'total' => count($journalChunks)];
        }

        $plan[] = ['kind' => 'finalize', 'label' => 'Level & magnitude', 'index' => 1, 'total' => 1];

        $this->plan = array_merge($this->plan, $plan);
        $this->totalSteps = count($this->plan);
        $this->completed[] = 'repos';
    }

    private function runEvidenceStep(array $step): void
    {
        $import = app(OnboardingImportService::class);
        $user = auth()->user();

        $titles = [];

        foreach ($step['repos'] as $repo) {
            try {
                $evidence = $import->createEvidence($user, $repo, 'onboarding');
            } catch (\Throwable) {
                continue;
            }

            $titles[] = $evidence->title;
            $this->xp += OnboardingImportService::XP_EVIDENCE_SCANNED;
        }

        $this->passport['stats']['evidence'] += count($titles);
        $this->passport['evidence'] = array_values(array_unique(array_merge($titles, $this->passport['evidence'])));
        $this->refreshDevIDSkills($user);
        $this->passport['factors'] = $this->passportFactors();

        $this->log[] = $this->term(
            'ok',
            'Imported '.count($titles).' repositor'.(count($titles) === 1 ? 'y' : 'ies').' as evidence',
            '+'.count($titles) * OnboardingImportService::XP_EVIDENCE_SCANNED.' XP',
        );

        foreach ($titles as $title) {
            $this->log[] = $this->term('dim', '  → '.$title.' · GitHub Repository · queued for AI analysis');
        }

        if ($step['index'] >= $step['total']) {
            $this->completed[] = 'evidence';
        }
    }

    private function runProjectStep(array $step): void
    {
        $import = app(OnboardingImportService::class);
        $user = auth()->user();

        try {
            $project = $import->createProject($user, $step['repo']);
        } catch (\Throwable) {
            $project = null;
        }

        if (! $project) {
            return;
        }

        $this->xp += OnboardingImportService::XP_PROJECT_PUBLISHED;
        $this->passport['stats']['projects']++;
        array_unshift($this->passport['projects'], $project->title);
        $this->passport['factors'] = $this->passportFactors();

        $this->log[] = $this->term('ok', 'Published project · '.$project->title, '+'.OnboardingImportService::XP_PROJECT_PUBLISHED.' XP');
        $this->log[] = $this->term('dim', '  → '.$step['repo']['html_url']);

        if ($step['index'] >= $step['total']) {
            $this->completed[] = 'projects';
        }
    }

    private function runJournalStep(array $step): void
    {
        $import = app(OnboardingImportService::class);
        $user = auth()->user();

        $titles = [];

        foreach ($step['repos'] as $repo) {
            try {
                $entry = $import->createJournalEntry($user, $repo);
            } catch (\Throwable) {
                continue;
            }

            $titles[] = $entry->title;
            $this->xp += OnboardingImportService::XP_JOURNAL_ENTRY;
        }

        $this->passport['stats']['journal'] += count($titles);
        $this->passport['journal'] = array_values(array_unique(array_merge($titles, $this->passport['journal'])));
        $this->passport['factors'] = $this->passportFactors();

        $this->log[] = $this->term(
            'ok',
            'Wrote '.count($titles).' journal entr'.(count($titles) === 1 ? 'y' : 'ies').' dated from repo history',
            '+'.count($titles) * OnboardingImportService::XP_JOURNAL_ENTRY.' XP',
        );

        foreach ($titles as $title) {
            $this->log[] = $this->term('dim', '  → '.$title);
        }

        if ($step['index'] >= $step['total']) {
            $this->completed[] = 'journal';
        }
    }

    private function runFinalizeStep(): void
    {
        $user = auth()->user();

        try {
            $bio = app(ProfileBioService::class)->generate($this->result ?? []);
        } catch (\Throwable) {
            $bio = '';
        }

        $this->persist($bio);

        $import = app(OnboardingImportService::class);

        $import->awardScanExperience($user, $this->result ?? [], [
            'evidence' => $this->passport['stats']['evidence'],
            'projects' => $this->passport['stats']['projects'],
            'journal' => $this->passport['stats']['journal'],
        ]);

        $fresh = $user->fresh();

        $this->passport['level'] = app(LevelService::class)->snapshot($fresh->experience_points);
        $this->passport['magnitude'] = app(EngineeringMagnitudeService::class)->breakdown($fresh);

        $this->summary = [
            'repos' => (int) ($this->scan['total'] ?? 0),
            'evidence' => $this->passport['stats']['evidence'],
            'projects' => $this->passport['stats']['projects'],
            'journal' => $this->passport['stats']['journal'],
            'queued' => $this->queued,
            'stars' => (int) ($this->result['total_stars'] ?? 0),
            'followers' => (int) ($this->result['followers'] ?? 0),
            'languages' => array_slice($this->result['languages'] ?? [], 0, 5),
            'xp' => $fresh->experience_points,
        ];

        $this->log[] = $this->term(
            'ok',
            'Scanned '.number_format($this->summary['repos']).' repositor'.((int) $this->summary['repos'] === 1 ? 'y' : 'ies')
                .' · '.$this->summary['evidence'].' evidence · '.$this->summary['projects'].' projects · '.$this->summary['journal'].' journal',
        );

        if ($this->queued > 0) {
            $this->log[] = $this->term('info', number_format($this->queued).' item'.($this->queued === 1 ? '' : 's').' importing in the background — your DevID keeps updating.');
        }

        $this->log[] = $this->term(
            'ok',
            'Level '.$this->passport['level']['current'].' · '.$this->passport['level']['title'].' — '.$this->passport['magnitude']['total'].'/1000 magnitude',
            $fresh->experience_points.' XP',
        );

        $this->completed[] = 'finalize';
        $this->phase = 'done';
    }

    private function tickLegacy(): void
    {
        match ($this->stage) {
            1 => $this->stageFetch(),
            2 => $this->stageSkills(),
            3 => $this->stageAchievements(),
            default => $this->stageFinish(),
        };
    }

    private function stageFetch(): void
    {
        $this->result = ['source' => $this->source, 'handle' => $this->handle, 'profile_url' => $this->url];

        $this->passport['profile'] = [
            'name' => auth()->user()->name,
            'handle' => $this->handle,
            'avatar' => null,
            'headline' => null,
            'location' => null,
        ];

        $this->log[] = $this->term('ok', 'Profile link saved · '.$this->url);
        $this->log[] = $this->term('info', 'Checking for skills & achievements…');
    }

    private function stageSkills(): void
    {
        $this->log[] = $this->term('ok', 'Skills — no public signals for this profile type');
    }

    private function stageAchievements(): void
    {
        $this->log[] = $this->term('ok', 'Achievements — newcomer profile');
    }

    private function stageFinish(): void
    {
        $bio = app(ProfileBioService::class)->generate($this->result ?? []);
        $this->persist($bio);

        $this->summary = [
            'repos' => null,
            'evidence' => 0,
            'projects' => 0,
            'journal' => 0,
            'stars' => null,
            'followers' => null,
            'languages' => [],
            'xp' => auth()->user()->experience_points,
        ];

        $this->log[] = $this->term('ok', 'Profile read & summarized — bio written');

        $this->phase = 'done';
    }

    private function persist(string $bio): void
    {
        $user = auth()->user();
        $result = $this->result;

        if (($result['source'] ?? null) === 'github') {
            $attributes = [
                'github_url' => $result['profile_url'] ?? $user->github_url,
                'name' => $result['name'] ?? $user->name,
                'headline' => $result['headline'] ?: $user->headline,
                'location' => $result['location'] ?: $user->location,
                'bio' => $bio ?: $user->bio,
            ];

            // Fill remaining profile fields from the scanned evidence.
            if (! empty($result['blog'])) {
                $attributes['website_url'] = str_starts_with($result['blog'], 'http') ? $result['blog'] : 'https://'.$result['blog'];
            }

            if (empty($user->avatar_path) && ! empty($result['avatar_url'])) {
                app(\App\Services\AvatarImportService::class)->import($user, $result['avatar_url']);
            }

            $user->update($attributes);

            foreach (array_slice($result['languages'] ?? [], 0, 5) as $language) {
                $skill = Skill::firstOrCreate(
                    ['slug' => $this->skillSlug($language)],
                    ['name' => $language, 'category' => 'language'],
                );

                if ($skill->name !== $language) {
                    $skill->forceFill(['name' => $language])->save();
                }

                $user->skills()->syncWithoutDetaching([$skill->id => ['level' => 3, 'verified_at' => now()]]);
            }
        } elseif (($result['source'] ?? null) === 'linkedin') {
            $user->update(['linkedin_url' => $result['profile_url']]);
        } else {
            $user->update(['website_url' => $result['profile_url']]);
        }

        $user->completeOnboarding();
    }

    private function refreshDevIDSkills(User $user): void
    {
        $names = $user->fresh()->skills()->orderByPivot('level', 'desc')->pluck('name')->all();

        $this->passport['skills'] = array_values(array_unique(array_merge(
            $this->passport['skills'],
            $names,
        )));
    }

    /**
     * Live preview of the Engineering Magnitude factors, converging on the
     * real breakdown once onboarding finalizes.
     *
     * @return array<int, array<string, int|string>>
     */
    private function passportFactors(): array
    {
        $evidence = (int) $this->passport['stats']['evidence'];
        $projects = (int) $this->passport['stats']['projects'];
        $journal = (int) $this->passport['stats']['journal'];
        $skills = count($this->passport['skills']);

        return [
            ['key' => 'evidence_quality', 'label' => 'Evidence Quality', 'points' => min(200, $evidence * 8), 'max' => 200],
            ['key' => 'technical_depth', 'label' => 'Technical Depth', 'points' => min(150, $projects * 6), 'max' => 150],
            ['key' => 'knowledge_sharing', 'label' => 'Knowledge Sharing', 'points' => min(150, $journal * 5), 'max' => 150],
            ['key' => 'breadth_of_expertise', 'label' => 'Breadth of Expertise', 'points' => min(100, $skills * 2), 'max' => 100],
            ['key' => 'contribution_history', 'label' => 'Open Source', 'points' => min(50, $evidence * 8), 'max' => 50],
        ];
    }

    private function normalizeUrl(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : 'https://'.$url;
    }

    private function skillSlug(string $language): string
    {
        return match ($language) {
            'C++' => 'c-plus-plus',
            'C#' => 'c-sharp',
            'F#' => 'f-sharp',
            'Objective-C' => 'objective-c',
            'Objective-C++' => 'objective-cpp',
            '.NET' => 'dotnet',
            default => Str::slug($language),
        };
    }

    private function term(string $kind, string $text, ?string $meta = null): array
    {
        $entry = ['kind' => $kind, 'text' => $text, 'meta' => $meta];

        if (count($this->log) > 140) {
            $this->log = array_slice($this->log, -140);
        }

        return $entry;
    }
}
?>

<div class="w-full">
    @if ($phase === 'input')
        <div class="p-8">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent">
                <flux:icon name="sparkles" variant="micro" />
                Profile scout
            </div>
            <flux:heading size="xl">Let's build your DevID.</flux:heading>
            <flux:text class="mt-2">
                Paste your GitHub profile URL and we'll scan every public repository — building your evidence library, projects, journal, level and engineering magnitude live, straight from your repo history.
            </flux:text>

            <form wire:submit="begin" class="mt-6 grid gap-4">
                <div>
                    <flux:input
                        wire:model.live.debounce.500ms="url"
                        placeholder="https://github.com/your-username"
                        type="url"
                        autofocus
                    />
                    <flux:error name="url" />
                    @if ($error)
                        <p class="mt-2 text-sm text-red-500">{{ $error }}</p>
                    @endif
                    @if (! $this->githubHandle && $this->urlLooksLikeGithubProfile)
                        <div class="mt-3 flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-3 dark:border-amber-500/20 dark:bg-amber-500/10">
                            <flux:icon name="exclamation-triangle" variant="micro" class="mt-0.5 size-4 shrink-0 text-amber-500" />
                            <div class="text-xs leading-relaxed text-amber-700 dark:text-amber-300">
                                <span class="font-semibold">Heads up — you haven't linked a GitHub account yet.</span>
                                The GitHub profile you scan will be linked to your profile, so the plagiarism guard can verify these repositories are yours before they're added. Only scan a profile you own.
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <flux:button type="submit" variant="primary" class="shrink-0">
                        <flux:icon name="magnifying-glass" variant="micro" />
                        Scan my GitHub
                    </flux:button>
                    <button type="button" wire:click="skip" class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
                        Skip for now
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-zinc-100 pt-4 text-xs text-zinc-400 dark:border-zinc-700/60">
                Works with
                <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">github.com/…</code>,
                <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">linkedin.com/in/…</code>
                and other profile URLs.
            </div>
        </div>
    @elseif ($phase === 'scouting')
        <div wire:poll.500ms="tick" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
            {{-- Terminal --}}
            <div class="flex h-full max-h-[640px] flex-col overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 shadow-xl">
                <div class="flex shrink-0 items-center gap-1.5 border-b border-zinc-800/80 px-4 py-2.5">
                    <span class="size-2.5 rounded-full bg-rose-500/80"></span>
                    <span class="size-2.5 rounded-full bg-amber-500/80"></span>
                    <span class="size-2.5 rounded-full bg-emerald-500/80"></span>
                    <span class="ms-2 font-mono text-xs text-zinc-500">proodev · scout</span>
                    <span class="ms-auto font-mono text-xs tabular-nums text-zinc-600">{{ $this->progress }}%</span>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4 font-mono text-[13px] leading-6">
                    {{-- Section checklist --}}
                    <div class="mb-3 grid gap-1 border-b border-zinc-800/60 pb-3">
                        @foreach ([
                            ['key' => 'profile', 'label' => 'Profile fetch'],
                            ['key' => 'repos', 'label' => 'Repository scan'],
                            ['key' => 'evidence', 'label' => 'Evidence library'],
                            ['key' => 'projects', 'label' => 'Projects'],
                            ['key' => 'journal', 'label' => 'Journal'],
                            ['key' => 'finalize', 'label' => 'Level & magnitude'],
                        ] as $section)
                            @php
                                $done = in_array($section['key'], $this->completed, true);
                                $active = ! $done && ($this->plan[$this->step]['kind'] ?? null) === $section['key'];
                            @endphp
                            <div class="flex items-center gap-2 text-xs">
                                @if ($done)
                                    <span class="text-emerald-400">✔</span>
                                    <span class="text-zinc-500 line-through decoration-zinc-700">{{ $section['label'] }}</span>
                                @elseif ($active)
                                    <span class="text-cyan-400">{{ $this->spinner }}</span>
                                    <span class="text-zinc-200">{{ $section['label'] }}</span>
                                @else
                                    <span class="text-zinc-700">○</span>
                                    <span class="text-zinc-600">{{ $section['label'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @foreach ($log as $entry)
                        <div class="flex items-baseline gap-2">
                            @if ($entry['kind'] === 'cmd')
                                <span class="shrink-0 text-emerald-400">$</span>
                                <span class="truncate text-zinc-400">{{ $entry['text'] }}</span>
                            @elseif ($entry['kind'] === 'ok')
                                <span class="shrink-0 text-emerald-400">✔</span>
                                <span class="flex-1 truncate text-zinc-100">{{ $entry['text'] }}</span>
                            @elseif ($entry['kind'] === 'warn')
                                <span class="shrink-0 text-amber-400">⚠</span>
                                <span class="flex-1 truncate text-amber-200/80">{{ $entry['text'] }}</span>
                            @elseif ($entry['kind'] === 'dim')
                                <span class="w-4 shrink-0"></span>
                                <span class="truncate text-zinc-600">{{ $entry['text'] }}</span>
                            @else
                                <span class="shrink-0 text-cyan-400">{{ $this->spinner }}</span>
                                <span class="flex-1 truncate text-zinc-400">{{ $entry['text'] }}</span>
                            @endif

                            @if ($entry['meta'])
                                <span class="shrink-0 tabular-nums text-zinc-600">{{ $entry['meta'] }}</span>
                            @endif
                        </div>
                    @endforeach

                    <div class="flex items-center gap-2 text-emerald-400">
                        <span class="shrink-0">{{ $this->spinner }}</span>
                        <span>{{ $this->currentTask ?? 'processing' }}…</span>
                    </div>
                </div>
            </div>

            {{-- Live passport build --}}
            <div class="flex h-full max-h-[640px] flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-950/80">
                <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-white/10">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="check-badge" variant="micro" class="text-emerald-500" />
                        DevID build
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        live
                    </span>
                </div>

                <div class="grid min-h-0 flex-1 gap-4 overflow-y-auto p-4">
                    {{-- Profile --}}
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            @if ($this->passport['profile']['avatar'] ?? null)
                                <img src="{{ $this->passport['profile']['avatar'] }}" alt="" class="size-12 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-white/10" />
                            @else
                                <div class="flex size-12 items-center justify-center rounded-full bg-black text-sm font-bold text-white ring-2 ring-zinc-200 dark:bg-white dark:text-black dark:ring-zinc-800">
                                    {{ auth()->user()->initials() }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $this->passport['profile']['name'] ?? auth()->user()->name }}
                            </div>
                            @if ($this->passport['profile']['handle'] ?? null)
                                <div class="truncate text-xs text-zinc-500">{{ '@'.$this->passport['profile']['handle'] }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-zinc-200 dark:bg-white/10">
                        @foreach ([
                            ['label' => 'Repos', 'value' => $this->passport['stats']['repos']],
                            ['label' => 'Evidence', 'value' => $this->passport['stats']['evidence']],
                            ['label' => 'Projects', 'value' => $this->passport['stats']['projects']],
                            ['label' => 'Journal', 'value' => $this->passport['stats']['journal']],
                        ] as $stat)
                            <div class="bg-zinc-50 px-1 py-2.5 text-center dark:bg-zinc-900">
                                <div class="text-base font-bold tabular-nums text-zinc-900 dark:text-white">{{ $stat['value'] }}</div>
                                <div class="mt-0.5 text-[9px] uppercase tracking-wide text-zinc-500">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Level --}}
                    <div class="rounded-lg border border-zinc-100 p-3 dark:border-white/10">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $this->levelSnapshot['title'] }}</span>
                            <span class="tabular-nums text-zinc-500">Lv {{ $this->levelSnapshot['current'] }} · {{ number_format($this->xp) }} XP</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                            <div class="h-full rounded-full bg-zinc-900 transition-all duration-500 dark:bg-white" style="width: {{ $this->levelSnapshot['progress'] }}%"></div>
                        </div>
                        <div class="mt-1 text-[10px] text-zinc-500">{{ $this->levelSnapshot['xp_to_next'] }} XP to {{ $this->levelSnapshot['next_title'] }}</div>
                    </div>

                    {{-- Skills --}}
                    @if ($this->passport['skills'] !== [])
                        <div>
                            <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Capabilities</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($this->passport['skills'] as $skill)
                                    <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Magnitude factors --}}
                    <div>
                        <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Engineering Magnitude</div>
                        @foreach ($this->passport['factors'] as $factor)
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="w-28 shrink-0 truncate text-[11px] text-zinc-500">{{ $factor['label'] }}</div>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                                    <div class="h-full rounded-full bg-zinc-900 dark:bg-white transition-all duration-500" style="width: {{ ($factor['points'] / max(1, $factor['max'])) * 100 }}%"></div>
                                </div>
                                <div class="w-10 shrink-0 text-right text-[10px] tabular-nums text-zinc-500">{{ $factor['points'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- All scouted records --}}
                    <div class="grid gap-2">
                        @foreach ($this->passport['evidence'] as $item)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400" />
                                <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                <span class="shrink-0 text-emerald-500">queued</span>
                            </div>
                        @endforeach
                        @foreach ($this->passport['projects'] as $item)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                <flux:icon name="folder" variant="micro" class="shrink-0 text-accent" />
                                <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                <span class="shrink-0 text-emerald-500">published</span>
                            </div>
                        @endforeach
                        @foreach ($this->passport['journal'] as $item)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                <flux:icon name="book-open" variant="micro" class="shrink-0 text-amber-500" />
                                <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                <span class="shrink-0 text-emerald-500">dated</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @elseif ($phase === 'done')
        <div class="p-8">
            <div class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <flux:icon name="check" variant="micro" />
                DevID ready
            </div>

            <div class="flex items-center gap-4">
                @if ($result['avatar_url'] ?? null)
                    <img src="{{ $result['avatar_url'] }}" alt="{{ $result['name'] ?? auth()->user()->name }}" class="size-16 rounded-full ring-2 ring-accent/40" />
                @else
                    <flux:avatar :src="auth()->user()->avatarUrl()" :alt="auth()->user()->name" size="xl" circle />
                @endif
                <div class="min-w-0">
                    <flux:heading size="lg">{{ $result['name'] ?? auth()->user()->name }}</flux:heading>
                    <flux:text>
                        @if ($result['handle'] ?? null)
                            {{ '@'.$result['handle'] }} ·
                        @endif
                        {{ $result['headline'] ?? auth()->user()->headline }}
                    </flux:text>
                </div>
            </div>

            @if ($summary)
                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @if ($summary['repos'] !== null)
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                            <div class="text-xl font-bold tabular-nums">{{ number_format($summary['repos']) }}</div>
                            <div class="text-xs text-zinc-500">Repos scanned</div>
                        </div>
                    @endif
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                        <div class="text-xl font-bold tabular-nums">{{ number_format($summary['evidence']) }}</div>
                        <div class="text-xs text-zinc-500">Evidence queued</div>
                    </div>
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                        <div class="text-xl font-bold tabular-nums">{{ number_format($summary['projects']) }}</div>
                        <div class="text-xs text-zinc-500">Projects published</div>
                    </div>
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                        <div class="text-xl font-bold tabular-nums">{{ number_format($summary['journal']) }}</div>
                        <div class="text-xs text-zinc-500">Journal entries</div>
                    </div>
                </div>

                @if ($summary['stars'] !== null)
                    <div class="mt-3 grid grid-cols-3 gap-3">
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                            <div class="text-lg font-bold tabular-nums">★ {{ number_format($summary['stars']) }}</div>
                            <div class="text-xs text-zinc-500">GitHub stars found</div>
                        </div>
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                            <div class="text-lg font-bold tabular-nums">{{ number_format($summary['followers']) }}</div>
                            <div class="text-xs text-zinc-500">Followers</div>
                        </div>
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                            <div class="text-lg font-bold tabular-nums">{{ number_format($summary['xp']) }}</div>
                            <div class="text-xs text-zinc-500">XP earned</div>
                        </div>
                    </div>
                @endif

                @if ($this->passport['level'])
                    <div class="mt-4 flex items-center gap-4 rounded-xl border border-accent/20 bg-accent/5 p-4">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Level from your scan</div>
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-2xl font-bold">{{ $this->passport['level']['title'] }}</span>
                                <span class="text-sm text-zinc-500">Lv {{ $this->passport['level']['current'] }}</span>
                            </div>
                        </div>
                        @if ($this->passport['magnitude'])
                            <div class="text-end">
                                <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Magnitude</div>
                                <div class="mt-1 text-2xl font-bold tabular-nums text-accent">{{ number_format($this->passport['magnitude']['total']) }}<span class="text-sm font-semibold text-zinc-500">/1000</span></div>
                            </div>
                        @endif
                    </div>
                @endif
            @endif

            @if (auth()->user()->bio)
                <div class="mt-6 rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                    <div class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-accent">
                        <flux:icon name="sparkles" variant="micro" />
                        Written by AI
                    </div>
                    <p class="text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ auth()->user()->bio }}</p>
                </div>
            @endif

            @if (auth()->user()->skills->isNotEmpty())
                <div class="mt-6">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Skills detected</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (auth()->user()->skills->take(6) as $skill)
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <flux:button variant="primary" wire:click="finish">
                    Continue to your feed
                </flux:button>
                <flux:text>
                    View your
                    <a href="{{ route('devid', auth()->user()->handle()) }}" wire:navigate class="text-accent hover:underline">public DevID</a>.
                </flux:text>
            </div>
        </div>
    @endif
</div>
