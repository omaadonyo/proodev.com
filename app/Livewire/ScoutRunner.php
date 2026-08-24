<?php

namespace App\Livewire;

use App\Jobs\ImportScoutedReposJob;
use App\Models\Project;
use App\Models\User;
use App\Services\AvatarImportService;
use App\Services\EngineeringMagnitudeService;
use App\Services\EvidenceScoutService;
use App\Services\ExperienceService;
use App\Services\LevelService;
use App\Services\OnboardingImportService;
use App\Services\ProfileScoutService;
use App\Services\RepoScanService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ScoutRunner extends Component
{
    public ?string $url = null;

    public string $phase = 'input';

    /** @var 'profile'|'single'|null */
    public ?string $mode = null;

    public ?string $error = null;

    /** @var array<int, array{kind: string, text: string, meta: string|null}> */
    public array $log = [];

    public int $stage = 0;

    // Pipeline state.
    /** @var array<int, array<string, mixed>> */
    public array $plan = [];

    public int $step = 0;

    public int $totalSteps = 0;

    /** @var array<int, string> */
    public array $completed = [];

    /** @var array<string, mixed>|null */
    public ?array $scan = null;

    public int $xp = 0;

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    /** @var array{evidence: int, projects: int, journal: int} */
    public array $added = ['evidence' => 0, 'projects' => 0, 'journal' => 0];

    public int $queued = 0;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    /** @var array<string, mixed>|null */
    public ?array $material = null;

    /** @var array<string, mixed>|null */
    public ?array $singleRepo = null;

    // The live passport being built on the right side of the scout.
    /** @var array<string, mixed> */
    public array $passport = [
        'profile' => null,
        'stats' => ['sources' => 0, 'evidence' => 0, 'projects' => 0, 'journal' => 0],
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
        $this->primeDevID();
    }

    public function begin(): void
    {
        $this->resetErrorBag();
        $this->error = null;

        $validated = $this->validate(['url' => ['required', 'string', 'url', 'max:255']]);

        $this->url = Str::startsWith($validated['url'], ['http://', 'https://'])
            ? $validated['url']
            : 'https://'.$validated['url'];

        $this->mode = $this->detectMode($this->url);

        $this->phase = 'scouting';
        $this->stage = 0;
        $this->step = 0;
        $this->plan = [];
        $this->completed = [];
        $this->scan = null;
        $this->result = null;
        $this->material = null;
        $this->singleRepo = null;
        $this->xp = 0;
        $this->summary = null;
        $this->queued = 0;
        $this->added = ['evidence' => 0, 'projects' => 0, 'journal' => 0];

        if ($this->mode === 'profile') {
            $this->beginProfileScan();
        } else {
            $this->beginSingleScout();
        }
    }

    public function tick(): void
    {
        if ($this->phase !== 'scouting') {
            return;
        }

        $this->stage++;

        try {
            if ($this->step >= count($this->plan)) {
                $this->phase = 'done';

                return;
            }

            $step = $this->plan[$this->step];

            match ($step['kind']) {
                'profile' => $this->runProfileStep(),
                'repos' => $this->runReposStep(),
                'classify' => $this->runClassifyStep(),
                'fetch' => $this->runFetchStep(),
                'evidence' => $this->runEvidenceStep($step),
                'project' => $this->runProjectStep($step),
                'journal' => $this->runJournalStep($step),
                'finalize' => $this->runFinalizeStep(),
            };

            $this->step++;
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            $this->phase = 'input';
        } catch (\Throwable) {
            $this->error = 'Something went wrong while scouting that URL. Please try again.';
            $this->phase = 'input';
        }
    }

    public function restart(): void
    {
        $this->phase = 'input';
        $this->url = null;
        $this->mode = null;
        $this->error = null;
        $this->log = [];
        $this->stage = 0;
        $this->step = 0;
        $this->plan = [];
        $this->completed = [];
        $this->scan = null;
        $this->result = null;
        $this->material = null;
        $this->singleRepo = null;
        $this->xp = 0;
        $this->summary = null;
        $this->added = ['evidence' => 0, 'projects' => 0, 'journal' => 0];

        $this->primeDevID();
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

    /**
     * @return array{current: int, next: int, title: string, next_title: string, progress: float, xp_to_next: int, xp: int}
     */
    #[Computed]
    public function levelSnapshot(): array
    {
        $user = auth()->user();
        $base = $user ? (int) $user->experience_points : 0;

        return app(LevelService::class)->snapshot($base + $this->xp);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    #[Computed]
    public function sections(): array
    {
        if ($this->mode === 'profile') {
            return [
                ['key' => 'profile', 'label' => 'Profile fetch'],
                ['key' => 'repos', 'label' => 'Repository scan'],
                ['key' => 'evidence', 'label' => 'Evidence library'],
                ['key' => 'projects', 'label' => 'Projects'],
                ['key' => 'journal', 'label' => 'Journal'],
                ['key' => 'finalize', 'label' => 'Level & magnitude'],
            ];
        }

        return [
            ['key' => 'classify', 'label' => 'Classify source'],
            ['key' => 'fetch', 'label' => 'Fetch content'],
            ['key' => 'evidence', 'label' => 'Evidence library'],
            ['key' => 'projects', 'label' => 'Projects'],
            ['key' => 'finalize', 'label' => 'Level & magnitude'],
        ];
    }

    /**
     * @return 'profile'|'single'
     */
    private function detectMode(string $url): string
    {
        if (app(ProfileScoutService::class)->source($url) === 'github') {
            $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

            if (count($parts) === 1) {
                return 'profile';
            }
        }

        return 'single';
    }

    private function beginProfileScan(): void
    {
        $handle = app(ProfileScoutService::class)->handle($this->url, 'github');

        if (! $handle) {
            $this->error = 'We could not find a username in that GitHub URL.';
            $this->phase = 'input';

            return;
        }

        // The finalize step is appended once the repo scan reveals the work.
        $this->plan = [
            ['kind' => 'profile', 'label' => 'Profile', 'index' => 1, 'total' => 2],
            ['kind' => 'repos', 'label' => 'Repositories', 'index' => 2, 'total' => 2],
        ];
        $this->totalSteps = count($this->plan);

        $this->log = [
            $this->term('cmd', '$ proodev scout --github '.$handle),
            $this->term('info', 'Resolving @'.$handle.' …'),
        ];
    }

    private function beginSingleScout(): void
    {
        $this->plan = [
            ['kind' => 'classify', 'label' => 'Classify', 'index' => 1, 'total' => 5],
            ['kind' => 'fetch', 'label' => 'Fetch', 'index' => 2, 'total' => 5],
            ['kind' => 'evidence', 'label' => 'Evidence', 'mode' => 'single', 'index' => 3, 'total' => 5],
            ['kind' => 'project', 'label' => 'Projects', 'mode' => 'single', 'index' => 4, 'total' => 5],
            ['kind' => 'finalize', 'label' => 'Level & magnitude', 'index' => 5, 'total' => 5],
        ];
        $this->totalSteps = count($this->plan);

        $this->log = [
            $this->term('cmd', '$ proodev scout --url '.$this->url),
            $this->term('info', 'Classifying source …'),
        ];
    }

    private function runProfileStep(): void
    {
        $handle = app(ProfileScoutService::class)->handle($this->url, 'github');

        $profile = app(ProfileScoutService::class)->githubProfile($handle);

        $this->result = array_merge($profile, [
            'source' => 'github',
            'handle' => $handle,
            'profile_url' => $this->url,
        ]);

        $this->passport['profile'] = [
            'name' => $profile['name'] ?? $handle,
            'handle' => $handle,
            'avatar' => $profile['avatar_url'] ?? null,
            'headline' => $profile['headline'] ?? null,
            'location' => $profile['location'] ?? null,
        ];

        if (auth()->user() && ! auth()->user()->avatar_path) {
            app(AvatarImportService::class)->import(auth()->user(), $profile['avatar_url'] ?? null);
        }

        $this->passport['skills'] = array_values(array_unique(array_merge(
            $this->passport['skills'],
            array_slice($profile['languages'] ?? [], 0, 6),
        )));

        $this->log[] = $this->term(
            'ok',
            'Profile fetched · @'.$handle.' · '.number_format((int) ($profile['followers'] ?? 0)).' followers · ★ '.number_format((int) ($profile['total_stars'] ?? 0)),
        );
        $this->log[] = $this->term('info', 'Scanning every public repository for evidence…');

        $this->completed[] = 'profile';
    }

    private function runReposStep(): void
    {
        $import = app(OnboardingImportService::class);
        $this->scan = $import->scanRepos($this->result['handle']);

        $repos = (array) ($this->scan['repos'] ?? []);
        $this->passport['stats']['sources'] = count($repos);

        // Pull requests are collaboration evidence repo scans miss.
        try {
            $pullRequests = app(RepoScanService::class)->pullRequests($this->result['handle']);
        } catch (\Throwable) {
            $pullRequests = [];
        }

        if (($this->scan['failed'] ?? false) || ($repos === [] && $pullRequests === [])) {
            $this->log[] = $this->term('warn', 'No public repositories found — nothing to import.');
        } else {
            if ($repos !== []) {
                $this->log[] = $this->term('ok', 'Scanned '.count($repos).' public repositories', '+'.count($repos).' repos');

                foreach (collect($repos)->pluck('name')->chunk(3) as $chunk) {
                    $this->log[] = $this->term('dim', '→ '.$chunk->implode(' · '));
                }
            }

            if ($pullRequests !== []) {
                $this->log[] = $this->term('ok', 'Found '.count($pullRequests).' pull request'.(count($pullRequests) === 1 ? '' : 's').' across the ecosystem');

                foreach (collect($pullRequests)->pluck('name')->take(5) as $title) {
                    $this->log[] = $this->term('dim', '  → '.$title);
                }
            }
        }

        $evidenceRepos = array_merge(
            $pullRequests,
            $import->evidenceRepos($repos),
        );
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

        if ($this->queued > 0 && auth()->user()) {
            ImportScoutedReposJob::dispatch(
                auth()->id(),
                $queuedEvidence,
                $queuedProjects,
                $queuedJournal,
                'profile_scan',
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

    private function runClassifyStep(): void
    {
        $classified = app(EvidenceScoutService::class)->classify($this->url);

        $this->material = ['classified' => $classified];

        $this->log[] = $this->term('ok', 'Classified · '.$classified['type']->label());
        $this->log[] = $this->term('info', 'Fetching source content …');

        $this->completed[] = 'classify';
    }

    private function runFetchStep(): void
    {
        $this->material = app(EvidenceScoutService::class)->fetch($this->url);
        $this->singleRepo = $this->repoFromMaterial($this->material);

        $this->passport['stats']['sources'] = 1;

        $this->log[] = $this->term(
            'ok',
            'Fetched · '.Str::limit((string) ($this->material['title'] ?? $this->url), 60).' · '.Str::ucfirst((string) ($this->material['source'] ?? 'web')),
        );

        $this->completed[] = 'fetch';
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function runEvidenceStep(array $step): void
    {
        $import = app(OnboardingImportService::class);
        $user = auth()->user();

        $repos = isset($step['repo'])
            ? [$step['repo']]
            : (($step['mode'] ?? null) === 'single' ? [$this->singleRepo] : ($step['repos'] ?? []));

        $titles = [];

        foreach ($repos as $repo) {
            if (! $repo) {
                continue;
            }

            try {
                $evidence = $import->createEvidence($user, $repo, ($step['mode'] ?? null) === 'single' ? 'single_scout' : 'profile_scan');
            } catch (\Throwable) {
                continue;
            }

            if (! $evidence->wasRecentlyCreated) {
                continue;
            }

            $titles[] = $evidence->title;
            $this->xp += OnboardingImportService::XP_EVIDENCE_SCANNED;
            $this->added['evidence']++;
            $this->passport['stats']['evidence']++;
            $this->passport['evidence'] = array_values(array_unique(array_merge([$evidence->title], $this->passport['evidence'])));
        }

        $this->refreshDevIDSkills($user);
        $this->passport['factors'] = $this->passportFactors();

        if ($titles !== []) {
            $label = ($step['mode'] ?? null) === 'single' ? 'Saved as evidence · '.$titles[0] : 'Imported '.count($titles).' repositor'.(count($titles) === 1 ? 'y' : 'ies').' as evidence';

            $this->log[] = $this->term('ok', $label, '+'.count($titles) * OnboardingImportService::XP_EVIDENCE_SCANNED.' XP');

            foreach ($titles as $title) {
                $this->log[] = $this->term('dim', '  → '.$title.' · queued for AI analysis');
            }
        } else {
            $this->log[] = $this->term('dim', 'Already in your library — nothing new imported');
        }

        if ($step['index'] >= $step['total']) {
            $this->completed[] = 'evidence';
        }
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function runProjectStep(array $step): void
    {
        $import = app(OnboardingImportService::class);
        $user = auth()->user();

        $repo = ($step['mode'] ?? null) === 'single' ? $this->singleRepo : ($step['repo'] ?? null);

        if ($repo) {
            try {
                $project = $import->createProject($user, $repo);
            } catch (\Throwable) {
                $project = null;
            }

            if ($project) {
                $this->xp += OnboardingImportService::XP_PROJECT_PUBLISHED;
                $this->added['projects']++;
                $this->passport['stats']['projects']++;
                array_unshift($this->passport['projects'], $project->title);
                $this->passport['factors'] = $this->passportFactors();

                $this->log[] = $this->term('ok', 'Published project · '.$project->title, '+'.OnboardingImportService::XP_PROJECT_PUBLISHED.' XP');
                $this->log[] = $this->term('dim', '  → '.$repo['html_url']);
            } else {
                $this->log[] = $this->term('dim', 'Project already published for this source');
            }
        }

        if ($step['index'] >= $step['total']) {
            $this->completed[] = 'projects';
        }
    }

    /**
     * @param  array<string, mixed>  $step
     */
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
            $this->added['journal']++;
            $this->passport['stats']['journal']++;
            $this->passport['journal'] = array_values(array_unique(array_merge([$entry->title], $this->passport['journal'])));
        }

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

        // Fill the profile from the scanned evidence (profile scans only).
        if ($this->mode === 'profile' && ($this->result['source'] ?? null) === 'github') {
            $updates = [];

            foreach ([
                'github_url' => $this->result['profile_url'] ?? null,
                'headline' => $this->result['headline'] ?? null,
                'location' => $this->result['location'] ?? null,
                'bio' => $this->result['bio'] ?? null,
            ] as $field => $value) {
                if (blank($fresh = $user->$field) && filled($value)) {
                    $updates[$field] = $value;
                }
            }

            if (blank($user->website_url) && ! empty($this->result['blog'])) {
                $blog = $this->result['blog'];
                $updates['website_url'] = str_starts_with($blog, 'http') ? $blog : 'https://'.$blog;
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }

            if (blank($user->avatar_path) && ! empty($this->result['avatar_url'])) {
                app(AvatarImportService::class)->import($user, $this->result['avatar_url']);
            }
        }

        if ($this->xp > 0) {
            app(ExperienceService::class)->award($user, $this->xp, 'Scout — work imported from '.$this->url);
        }

        $fresh = $user->fresh();

        $this->passport['level'] = app(LevelService::class)->snapshot($fresh->experience_points);
        $this->passport['magnitude'] = app(EngineeringMagnitudeService::class)->breakdown($fresh);

        $this->summary = [
            'mode' => $this->mode,
            'sources' => $this->passport['stats']['sources'],
            'evidence' => $this->added['evidence'],
            'projects' => $this->added['projects'],
            'journal' => $this->added['journal'],
            'queued' => $this->queued,
            'xp' => $this->xp,
            'level' => $this->passport['level'],
            'magnitude' => $this->passport['magnitude'],
        ];

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

    private function primeDevID(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->passport['profile'] = [
            'name' => $user->name,
            'handle' => $user->handle(),
            'avatar' => $user->avatarUrl(),
            'headline' => $user->headline,
            'location' => $user->location,
        ];

        $this->passport['stats'] = [
            'sources' => 0,
            'evidence' => $user->evidence()->count(),
            'projects' => Project::where('user_id', $user->id)->published()->count(),
            'journal' => $user->journalEntries()->count(),
        ];

        $this->passport['skills'] = $user->skills()->orderByPivot('level', 'desc')->limit(8)->pluck('name')->all();
        $this->passport['factors'] = $this->passportFactors();
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
     * real breakdown once the scout finalizes.
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

    /**
     * @param  array<string, mixed>  $material
     * @return array<string, mixed>
     */
    private function repoFromMaterial(array $material): array
    {
        return app(EvidenceScoutService::class)->toRepo($material, $this->url);
    }

    /**
     * @return array{kind: string, text: string, meta: string|null}
     */
    private function term(string $kind, string $text, ?string $meta = null): array
    {
        $entry = ['kind' => $kind, 'text' => $text, 'meta' => $meta];

        if (count($this->log) > 140) {
            $this->log = array_slice($this->log, -140);
        }

        return $entry;
    }
}
