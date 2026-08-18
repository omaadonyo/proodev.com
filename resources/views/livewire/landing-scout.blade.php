<?php

use App\Services\LevelService;
use App\Services\ProjectScoutService;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public ?string $url = null;

    public string $phase = 'input';

    /** @var array<int, array{key: string, label: string}> */
    public array $plan = [];

    public int $step = 0;

    public int $totalSteps = 0;

    /** @var array<int, string> */
    public array $completed = [];

    /** @var array<int, array{kind: string, text: string, meta: string|null}> */
    public array $log = [];

    /** @var array<string, mixed> */
    public array $passport = [];

    public int $xp = 0;

    /** @var array<string, mixed> */
    public array $levelSnapshot = [];

    public ?int $score = null;

    public ?string $error = null;

    public string $spinner = '⠹';

    public bool $demo = false;

    public const DEFAULT_URL = 'https://github.com/MrPunyapal/proodev';

    /** @var array<string, mixed>|null */
    public ?array $material = null;

    /** @var array<string, mixed>|null */
    public ?array $draft = null;

    public function mount(): void
    {
        $this->url = self::DEFAULT_URL;
        $this->passport = $this->defaultPassport();
        $this->levelSnapshot = app(LevelService::class)->snapshot(0);
    }

    public function begin(): void
    {
        $this->resetErrorBag();
        $this->error = null;

        $input = trim((string) $this->url);

        if ($input === '' || $input === self::DEFAULT_URL) {
            $input = self::DEFAULT_URL;
            $this->demo = true;
        } else {
            $this->demo = false;

            if (! Str::startsWith($input, ['http://', 'https://'])) {
                $input = 'https://'.$input;
            }

            if (! filter_var($input, FILTER_VALIDATE_URL)) {
                $this->error = 'That doesn\'t look like a valid URL. Try a GitHub repository or project link.';

                return;
            }
        }

        $this->url = $input;
        $this->buildPlan();
        $this->phase = 'scouting';
        $this->step = 0;
        $this->completed = [];
        $this->log = [];
        $this->passport = $this->defaultPassport();
        $this->xp = 0;
        $this->score = null;
        $this->material = null;
        $this->draft = null;
        $this->spinner = '⠹';

        $this->log[] = $this->line('cmd', 'proodev scout --github '.($this->demo ? 'MrPunyapal' : $this->url));
    }

    public function tick(): void
    {
        if ($this->phase !== 'scouting') {
            return;
        }

        $this->spinner = ['⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'][count($this->log) % 8];

        $key = $this->plan[$this->step]['key'] ?? null;

        if (! $key) {
            $this->phase = 'done';

            return;
        }

        try {
            $this->{'step'.Str::studly($key)}();
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            $this->phase = 'input';

            return;
        } catch (Throwable) {
            $this->error = 'Something went wrong while scouting that project. Please try again.';
            $this->phase = 'input';

            return;
        }

        $this->completed[] = $key;
        $this->step++;

        if ($this->step >= $this->totalSteps) {
            $this->phase = 'done';
        }
    }

    public function progress(): int
    {
        return min(100, (int) round($this->step / max(1, $this->totalSteps) * 100));
    }

    public function currentTask(): string
    {
        return $this->plan[$this->step]['label'] ?? 'Processing';
    }

    public function tryAgain(): void
    {
        $this->phase = 'input';
        $this->error = null;
        $this->url = self::DEFAULT_URL;
        $this->log = [];
        $this->passport = $this->defaultPassport();
        $this->xp = 0;
        $this->score = null;
        $this->material = null;
        $this->draft = null;
        $this->levelSnapshot = app(LevelService::class)->snapshot(0);
    }

    private function stepProfile(): void
    {
        $this->material = $this->demo ? $this->demoMaterial() : app(ProjectScoutService::class)->fetch($this->url);

        $profile = [
            'name' => $this->material['name'] ?? $this->material['title'] ?? 'Your profile',
            'handle' => $this->material['handle'] ?? null,
            'avatar' => $this->material['avatar_url'] ?? null,
            'location' => $this->material['location'] ?? null,
            'headline' => $this->material['headline'] ?? $this->material['tagline'] ?? null,
            'summary' => $this->material['summary'] ?? null,
        ];

        $this->passport['profile'] = $profile;

        $this->log[] = $this->line('ok', 'Profile fetched — '.$profile['name']);

        if ($profile['handle'] || $profile['location']) {
            $this->log[] = $this->line('dim', '@'.($profile['handle'] ?? '—').($profile['location'] ? ' · '.$profile['location'] : ''));
        }
    }

    private function stepRepos(): void
    {
        $repos = $this->demo
            ? collect($this->material['repos'] ?? [])->map(fn ($repo) => $repo['name'])->values()->all()
            : [($this->material['title'] ?? Str::afterLast(rtrim((string) $this->url, '/'), '/'))];

        $this->passport['evidence'] = $repos;
        $this->passport['stats']['repos'] = count($repos);

        $this->log[] = $this->line('ok', 'Found '.count($repos).' public repositor'.(count($repos) === 1 ? 'y' : 'ies'));

        foreach (array_slice($repos, 0, 3) as $repo) {
            $this->log[] = $this->line('dim', $repo);
        }
    }

    private function stepEvidence(): void
    {
        $count = count($this->passport['evidence']);
        $this->passport['stats']['evidence'] = $count;

        $this->passport['skills'] = $this->demo
            ? ($this->material['capabilities'] ?? [])
            : ($this->material['tech_stack'] ?? []);

        $this->log[] = $this->line('ok', 'Imported '.$count.' evidence item'.($count === 1 ? '' : 's').' — queued for analysis', '+'.($count * 8).' XP');
    }

    private function stepProjects(): void
    {
        $repos = $this->demo
            ? array_slice($this->material['repos'] ?? [], 0, 2)
            : [['name' => $this->material['title'] ?? 'Untitled project']];

        $projects = collect($repos)->map(fn ($repo) => $repo['name'])->values()->all();

        $this->passport['projects'] = $projects;
        $this->passport['stats']['projects'] = count($projects);

        $this->log[] = $this->line('ok', 'Published '.count($projects).' project'.(count($projects) === 1 ? '' : 's').' — dated from repository history', '+'.(count($projects) * 100).' XP');
    }

    private function stepJournal(): void
    {
        $entries = $this->demo
            ? collect($this->material['repos'] ?? [])->take(3)->map(fn ($repo) => 'Started '.$repo['name'])->values()->all()
            : ['Started '.($this->material['title'] ?? 'this project')];

        $this->passport['journal'] = $entries;
        $this->passport['stats']['journal'] = count($entries);

        $this->log[] = $this->line('ok', 'Wrote '.count($entries).' journal entr'.(count($entries) === 1 ? 'y' : 'ies').' — dated from repo history', '+'.(count($entries) * 10).' XP');
    }

    private function stepFinalize(): void
    {
        $this->draft = $this->demo
            ? $this->demoDraft()
            : app(ProjectScoutService::class)->draft($this->facts($this->material), $this->material);

        $this->score = $this->demo
            ? 912
            : app(ProjectScoutService::class)->score($this->material, $this->draft);

        $this->xp = $this->computeXp();
        $this->levelSnapshot = app(LevelService::class)->snapshot($this->xp);
        $this->passport['factors'] = $this->factors($this->score);

        $this->log[] = $this->line('ok', 'Engineering magnitude — '.$this->score.'/1000');
        $this->log[] = $this->line('ok', 'Level — '.$this->levelSnapshot['title'].' · Lv '.$this->levelSnapshot['current'].' · '.number_format($this->xp).' XP');
        $this->log[] = $this->line('ok', 'Passport ready — evidence-backed, explainable, live');
    }

    private function computeXp(): int
    {
        return ($this->passport['stats']['evidence'] * 8)
            + ($this->passport['stats']['projects'] * 100)
            + ($this->passport['stats']['journal'] * 10);
    }

    /**
     * @return array<int, array{label: string, points: int, max: int}>
     */
    private function factors(int $score): array
    {
        $definitions = [
            ['label' => 'Evidence quality', 'max' => 120],
            ['label' => 'Technical depth', 'max' => 160],
            ['label' => 'Knowledge sharing', 'max' => 140],
            ['label' => 'Breadth', 'max' => 100],
            ['label' => 'Consistency', 'max' => 100],
            ['label' => 'Community trust', 'max' => 180],
            ['label' => 'Open source', 'max' => 200],
        ];

        $total = (int) array_sum(array_column($definitions, 'max'));

        return collect($definitions)->map(fn ($definition) => [
            'label' => $definition['label'],
            'points' => (int) round($score * $definition['max'] / max(1, $total)),
            'max' => $definition['max'],
        ])->all();
    }

    private function buildPlan(): void
    {
        $this->plan = [
            ['key' => 'profile', 'label' => 'Profile fetch'],
            ['key' => 'repos', 'label' => 'Repository scan'],
            ['key' => 'evidence', 'label' => 'Evidence library'],
            ['key' => 'projects', 'label' => 'Projects'],
            ['key' => 'journal', 'label' => 'Journal'],
            ['key' => 'finalize', 'label' => 'Level & magnitude'],
        ];
        $this->totalSteps = count($this->plan);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPassport(): array
    {
        return [
            'profile' => [],
            'stats' => ['repos' => 0, 'evidence' => 0, 'projects' => 0, 'journal' => 0],
            'skills' => [],
            'factors' => [],
            'evidence' => [],
            'projects' => [],
            'journal' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function demoMaterial(): array
    {
        return [
            'source' => 'github',
            'profile_url' => $this->url,
            'title' => 'ProoDev',
            'tagline' => 'Evidence-backed engineering identity — proof over claims',
            'repository_url' => 'https://github.com/MrPunyapal/proodev',
            'demo_url' => 'https://proodev.dev',
            'tech_stack' => ['PHP', 'Laravel', 'Livewire', 'Tailwind CSS', 'Redis', 'Vue'],
            'content' => 'ProoDev is an AI-powered developer intelligence platform driven by evidence-backed professional identities. It turns real work — repositories, articles, and projects — into proof. Paste any URL, AI fetches the source, drafts an engineering report, maps your skills, and computes an explainable Engineering Magnitude score from 0 to 1000. Built with Laravel, Livewire and Tailwind CSS, it uses Redis for real-time activity streams and queues for background AI analysis.',
            'name' => 'Punyapal Shah',
            'handle' => 'MrPunyapal',
            'avatar_url' => 'https://github.com/MrPunyapal.png',
            'location' => 'Remote · Worldwide',
            'headline' => 'Full-stack engineer building real-time products with Laravel, Livewire and Redis.',
            'summary' => 'Full-stack engineer with a track record of shipping production systems. Proven ability to take a product from idea to launch — designing the architecture, building the full stack, and shipping real-time features that scale. Comfortable owning features end-to-end and communicating decisions clearly.',
            'capabilities' => [
                'Full-stack Laravel',
                'Livewire & real-time',
                'Redis & queues',
                'API design',
                'Automated testing',
                'Product thinking',
            ],
            'repos' => [
                ['name' => 'proodev', 'description' => 'Evidence-backed engineering identity — proof over claims.', 'language' => 'PHP', 'stars' => '1.2k'],
                ['name' => 'laravel-livewire-boilerplate', 'description' => 'Production starter with auth, roles and real-time feeds.', 'language' => 'PHP', 'stars' => '412'],
                ['name' => 'redis-streams-bus', 'description' => 'Event bus built on Redis Streams for real-time apps.', 'language' => 'PHP', 'stars' => '98'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function demoDraft(): array
    {
        return [
            'title' => 'ProoDev',
            'tagline' => 'Evidence-backed engineering identity — proof over claims',
            'problem' => 'Engineers scatter their work across projects, journals and separate tools, making it hard to prove their skills with evidence. Reputation is self-reported, so claims carry little signal and are easy to fake.',
            'solution' => 'ProoDev turns real work into proof. Paste a repo or project URL, AI fetches the source, drafts an engineering report, maps your skills, and computes an explainable Engineering Magnitude score backed by evidence — a passport that cannot be faked.',
            'architecture' => 'A Laravel application with Livewire for reactive UI, Redis for real-time feed events and queues, an evidence pipeline that fetches and classifies source material, and an AI analysis layer with an offline rule-based fallback.',
            'tech_stack' => ['PHP', 'Laravel', 'Livewire', 'Tailwind CSS', 'Redis', 'Vue'],
            'demo_url' => 'https://proodev.dev',
            'repository_url' => 'https://github.com/MrPunyapal/proodev',
            'generated_by' => 'ai',
        ];
    }

    /**
     * @param  array<string, mixed>  $material
     */
    private function facts(array $material): string
    {
        return collect([
            'Source URL: '.($material['profile_url'] ?? ''),
            'Title: '.($material['title'] ?? ''),
            'Tagline: '.($material['tagline'] ?? 'Not provided'),
            'Tech signals: '.implode(', ', $material['tech_stack'] ?? []),
            'Content: '.Str::limit((string) ($material['content'] ?? ''), 8000),
        ])->filter()->implode("\n\n");
    }

    /**
     * @return array{kind: string, text: string, meta: string|null}
     */
    private function line(string $kind, string $text, ?string $meta = null): array
    {
        return compact('kind', 'text', 'meta');
    }
}
?>

<div class="w-full">
    @if ($phase === 'input')
        <form wire:submit="begin" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <flux:icon name="plus" variant="solid" />
                </div>
                <div class="flex-1">
                    <flux:input
                        wire:model="url"
                        type="url"
                        placeholder="Paste a repo, demo or project URL to draft it with AI…"
                        class="border-none bg-transparent shadow-none focus:ring-0"
                    />
                </div>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Scout
                </flux:button>
            </div>
            <flux:error name="url" class="mt-2" />
            @if ($error)
                <p class="mt-2 text-left text-xs text-[#8f9dff]">{{ $error }}</p>
            @endif
        </form>
    @elseif ($phase === 'scouting')
        <div wire:poll.500ms="tick" class="grid gap-5 text-left lg:grid-cols-[minmax(0,1fr)_280px]">
            {{-- Terminal --}}
            <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 shadow-xl">
                <div class="flex items-center gap-1.5 border-b border-zinc-800/80 px-4 py-2.5">
                    <span class="size-2.5 rounded-full bg-rose-500/80"></span>
                    <span class="size-2.5 rounded-full bg-amber-500/80"></span>
                    <span class="size-2.5 rounded-full bg-emerald-500/80"></span>
                    <span class="ms-2 font-mono text-xs text-zinc-500">proodev · scout</span>
                    <span class="ms-auto font-mono text-xs tabular-nums text-zinc-600">{{ $this->progress() }}%</span>
                </div>

                <div class="max-h-[480px] overflow-y-auto p-4 font-mono text-[13px] leading-6">
                    {{-- Section checklist --}}
                    <div class="mb-3 grid gap-1 border-b border-zinc-800/60 pb-3">
                        @foreach ($this->plan as $section)
                            @php
                                $done = in_array($section['key'], $this->completed, true);
                                $active = ! $done && ($this->plan[$this->step]['key'] ?? null) === $section['key'];
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
                            @else
                                <span class="w-4 shrink-0"></span>
                                <span class="truncate text-zinc-600">{{ $entry['text'] }}</span>
                            @endif

                            @if ($entry['meta'])
                                <span class="shrink-0 tabular-nums text-zinc-600">{{ $entry['meta'] }}</span>
                            @endif
                        </div>
                    @endforeach

                    <div class="flex items-center gap-2 text-emerald-400">
                        <span class="shrink-0">{{ $this->spinner }}</span>
                        <span>{{ $this->currentTask() }}…</span>
                    </div>
                </div>
            </div>

            {{-- Live passport build --}}
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-950/80">
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-white/10">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="check-badge" variant="micro" class="text-emerald-500" />
                        Passport build
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        live
                    </span>
                </div>

                <div class="grid gap-4 p-4">
                    {{-- Profile --}}
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            @if ($this->passport['profile']['avatar'] ?? null)
                                <img src="{{ $this->passport['profile']['avatar'] }}" alt="" class="size-12 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-white/10" />
                            @else
                                <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ Str::initials(($this->passport['profile']['name'] ?? 'U'), true) }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $this->passport['profile']['name'] ?? 'Scouting…' }}
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
                            <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $this->levelSnapshot['title'] ?? 'Explorer' }}</span>
                            <span class="tabular-nums text-zinc-500">Lv {{ $this->levelSnapshot['current'] ?? 1 }} · {{ number_format($this->xp) }} XP</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                            <div class="h-full rounded-full bg-accent transition-all duration-500" style="width: {{ $this->levelSnapshot['progress'] ?? 0 }}%"></div>
                        </div>
                        <div class="mt-1 text-[10px] text-zinc-500">{{ number_format($this->levelSnapshot['xp_to_next'] ?? 0) }} XP to {{ $this->levelSnapshot['next_title'] ?? 'Builder' }}</div>
                    </div>

                    {{-- Skills --}}
                    @if ($this->passport['skills'] !== [])
                        <div>
                            <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Capabilities</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (array_slice($this->passport['skills'], 0, 6) as $skill)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">
                                        <x-tech-logo :name="$skill" class="size-3.5 shrink-0" />
                                        {{ $skill }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Magnitude factors --}}
                    @if ($this->passport['factors'] !== [])
                        <div>
                            <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Engineering Magnitude</div>
                            @foreach ($this->passport['factors'] as $factor)
                                <div class="mt-1.5 flex items-center gap-2">
                                    <div class="w-24 shrink-0 truncate text-[11px] text-zinc-500">{{ $factor['label'] }}</div>
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                                        <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-cyan-400 transition-all duration-500" style="width: {{ ($factor['points'] / max(1, $factor['max'])) * 100 }}%"></div>
                                    </div>
                                    <div class="w-9 shrink-0 text-right text-[10px] tabular-nums text-zinc-500">{{ $factor['points'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Recent items --}}
                    <div class="grid gap-2">
                        @foreach (array_slice($this->passport['evidence'], 0, 3) as $item)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400" />
                                <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                <span class="shrink-0 text-emerald-500">queued</span>
                            </div>
                        @endforeach
                        @foreach (array_slice($this->passport['projects'], 0, 2) as $item)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                <flux:icon name="folder" variant="micro" class="shrink-0 text-accent" />
                                <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                <span class="shrink-0 text-emerald-500">published</span>
                            </div>
                        @endforeach
                        @foreach (array_slice($this->passport['journal'], 0, 2) as $item)
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
    @else
        @php
            $profileName = $material['name'] ?? $material['title'] ?? 'Your profile';
            $handle = $material['handle'] ?? '';
            $avatar = $material['avatar_url'] ?? null;
            $location = $material['location'] ?? null;
            $headline = $material['headline'] ?? $material['tagline'] ?? '';
            $summary = $material['summary'] ?? $draft['solution'] ?? '';
            $capabilities = $material['capabilities'] ?? $draft['tech_stack'] ?? [];
            $repos = $material['repos'] ?? [];
        @endphp

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white text-left dark:border-white/10 dark:bg-zinc-950/80">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-white/10">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    <flux:icon name="check" variant="micro" />
                    Passport ready
                </div>
                @if ($score !== null)
                    <span
                        x-data="{ shown: 0 }"
                        x-init="let t = setInterval(() => { if (shown < {{ $score }}) { shown++; } else { clearInterval(t); } }, 20)"
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#3750eb]/10 px-3 py-1 text-xs font-semibold tabular-nums text-[#3750eb] dark:text-[#8f9dff]"
                    >
                        <flux:icon name="sparkles" variant="micro" />
                        <span x-text="shown"></span>/1000
                    </span>
                @endif
            </div>

            <div class="grid gap-5 p-5">
                <div class="flex items-center gap-4">
                    <div class="relative shrink-0">
                        @if ($avatar)
                            <img
                                src="{{ $avatar }}"
                                alt="{{ $profileName }}"
                                class="size-14 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-white/10"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                            />
                        @endif
                        <div class="{{ $avatar ? 'hidden' : '' }} flex size-14 items-center justify-center rounded-full bg-black text-sm font-bold text-white ring-2 ring-zinc-200 dark:bg-white dark:text-black dark:ring-zinc-800">
                            {{ Str::initials($profileName ?: 'U', true) }}
                        </div>
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-lg font-semibold text-zinc-900 dark:text-white">{{ $profileName }}</div>
                        @if ($handle || $location)
                            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                @if ($handle){{ '@'.$handle }}@endif
                                @if ($handle && $location) · @endif
                                @if ($location){{ $location }}@endif
                            </div>
                        @endif
                        @if ($headline)
                            <div class="mt-1 truncate text-sm text-zinc-700 dark:text-zinc-300">{{ $headline }}</div>
                        @endif
                    </div>
                </div>

                @if ($summary)
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Summary</div>
                        <p class="mt-1.5 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $summary }}</p>
                    </div>
                @endif

                @if ($capabilities !== [])
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Capabilities</div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($capabilities as $capability)
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">
                                    <x-tech-logo :name="$capability" class="size-3.5 shrink-0" />
                                    {{ $capability }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($repos !== [])
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Repositories</div>
                        <div class="mt-2 grid gap-2">
                            @foreach ($repos as $repo)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/10 dark:bg-zinc-900/70">
                                    <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400 dark:text-zinc-500" />
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $repo['name'] ?? $repo }}</div>
                                        <div class="truncate text-xs text-zinc-500">{{ $repo['description'] ?? '' }}</div>
                                    </div>
                                    @if ($repo['language'] ?? null)
                                        <span class="shrink-0 text-xs text-zinc-400">{{ $repo['language'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-zinc-200 px-5 py-4 sm:flex-row dark:border-white/10">
                <p class="text-left text-xs text-zinc-500">Your whole engineering identity, proven from a single URL.</p>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="tryAgain" class="rounded-lg border border-zinc-200 bg-white/60 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:bg-white/10">
                        Scout another
                    </button>
                    <a href="{{ route('register') }}" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        Build yours
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
