<?php

use App\Enums\EvidenceStatus;
use App\Models\RecruiterMatch;
use App\Models\TalentPool;
use App\Models\User;
use App\Services\Recruiter\AgencyWorkspaceService;
use App\Services\Recruiter\CandidateIntelligenceService;
use App\Services\Recruiter\RiskAssessmentService;
use App\Services\Recruiter\WorkspaceService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Candidate Intelligence Report')] class extends Component
{
    public User $user;

    public bool $fresh = false;

    public ?string $noteBody = null;

    public function mount(User $candidate): void
    {
        $this->user = $candidate;
    }

    #[Computed]
    public function report()
    {
        return app(CandidateIntelligenceService::class)->report($this->user, [
            'recruiter' => auth()->user(),
            'persist' => true,
            'fresh' => $this->fresh,
        ]);
    }

    #[Computed]
    public function risk()
    {
        return app(RiskAssessmentService::class)->assess($this->user, auth()->user());
    }

    #[Computed]
    public function pools()
    {
        $workspace = app(WorkspaceService::class)->current(auth()->user());

        if ($workspace) {
            return TalentPool::where('workspace_id', $workspace->id)->orderBy('name')->get();
        }

        return auth()->user()->talentPools()->orderBy('name')->get();
    }

    #[Computed]
    public function currentWorkspace()
    {
        return app(WorkspaceService::class)->current(auth()->user());
    }

    #[Computed]
    public function savedStatus(): ?string
    {
        $workspace = app(AgencyWorkspaceService::class);
        $pool = $workspace->defaultPool(auth()->user());

        return $pool->members()->where('candidate_id', $this->user->id)->value('status');
    }

    public function saveCandidate(string $status): void
    {
        app(AgencyWorkspaceService::class)->addCandidate(auth()->user(), $this->user, status: $status);
        $this->dispatch('toast', message: 'Candidate saved to your workspace.', variant: 'success');
    }

    public function addNote(): void
    {
        $this->validate(['noteBody' => ['required', 'string', 'max:2000']]);

        app(AgencyWorkspaceService::class)->addNote(auth()->user(), $this->user, $this->noteBody);
        $this->noteBody = null;
        $this->dispatch('toast', message: 'Note added.', variant: 'success');
    }

    /**
     * Share of the active job posting's keywords this candidate covers, 0-100.
     */
    public function matchPct(): int
    {
        $ctx = RecruiterMatch::contextFor(auth()->user());

        if (! $ctx || ($ctx['skills'] ?? []) === []) {
            return 0;
        }

        $ownedSkills = $this->user->skills->pluck('slug')->map(fn ($slug) => (string) $slug)->all();

        $covered = 0;
        $total = 0;

        foreach ($ctx['skills'] as $skill) {
            $total++;
            if (in_array($skill, $ownedSkills, true)) {
                $covered++;
            }
        }

        if (! empty($ctx['include_technologies'])) {
            $ownedTechs = $this->technologyCoverage();

            foreach ($ctx['technologies'] ?? [] as $tech) {
                $total++;
                if (in_array(Str::lower((string) $tech), $ownedTechs, true)) {
                    $covered++;
                }
            }
        }

        return $total > 0 ? (int) round($covered / $total * 100) : 0;
    }

    public function hasMatchBadges(): bool
    {
        return RecruiterMatch::contextFor(auth()->user()) !== null;
    }

    public function matchMetric(): string
    {
        $ctx = RecruiterMatch::contextFor(auth()->user());

        return empty($ctx['include_technologies'] ?? false) ? 'skills' : 'tech';
    }

    /**
     * @return array<int, string>
     */
    private function technologyCoverage(): array
    {
        return $this->user->evidence()
            ->where('status', EvidenceStatus::Ready)
            ->with('analysis')
            ->get()
            ->flatMap(fn ($item) => $item->analysis?->technologies ?? [])
            ->map(fn ($tech) => Str::lower((string) $tech))
            ->unique()
            ->values()
            ->all();
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            @if ($this->hasMatchBadges() && $this->matchPct() === 100)
                <span class="block rounded-full p-[2.5px]" style="background: linear-gradient(135deg, #34d399, #14b8a6)">
                    <flux:avatar :src="$this->report['developer']['avatar']" :alt="$this->report['developer']['name']" circle class="size-14" />
                </span>
            @else
                <flux:avatar :src="$this->report['developer']['avatar']" :alt="$this->report['developer']['name']" circle class="size-14" />
            @endif
            <div>
                <flux:heading size="xl">{{ $this->report['developer']['name'] }}</flux:heading>
                <flux:text>
                    @if ($this->report['developer']['headline'])
                        {{ $this->report['developer']['headline'] }} -
                    @endif
                    {{ $this->report['developer']['location'] ?? 'Location not listed' }}
                </flux:text>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                    @if ($this->hasMatchBadges())
                        <x-match-badge :pct="$this->matchPct()" :metric="$this->matchMetric()" />
                    @endif
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 font-medium dark:bg-zinc-900">
                        <flux:icon name="sparkles" variant="micro" class="text-accent" />
                        {{ $this->report['seniority'] }}
                    </span>
                    @if ($this->report['verification']['verified'])
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <flux:icon name="check-badge" variant="micro" />
                            Verified
                        </span>
                    @endif
                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                        {{ $this->report['confidence'] }}% assessment confidence
                    </span>
                </div>
                <x-social-links :user="$this->user" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="outline" wire:click="$dispatch('add-to-compare', { candidateId: {{ $this->user->id }} })">
                <flux:icon name="scale" variant="micro" />
                Compare
            </flux:button>
            <flux:dropdown>
                <flux:button variant="primary">Save candidate</flux:button>
                <flux:menu>
                    @foreach (['saved', 'shortlisted', 'contacted', 'interviewing', 'offered'] as $status)
                        <flux:menu.item wire:click="saveCandidate('{{ $status }}')" :selected="$this->savedStatus === $status">
                            {{ ucfirst($status) }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
            <flux:button :href="route('recruiter.exports', $this->user->id)" wire:navigate>
                <flux:icon name="arrow-down-tray" variant="micro" />
                Executive brief
            </flux:button>
            <a href="{{ route('passport', $this->report['developer']['handle']) }}" wire:navigate class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-black dark:text-zinc-200 dark:hover:bg-zinc-900">
                Public passport
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="grid gap-6 lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Executive summary</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $this->report['summary'] }}</p>
                <p class="mt-2 text-xs text-zinc-400">Generated by {{ $this->report['generated_by'] }} on {{ \Illuminate\Support\Carbon::parse($this->report['generated_at'])->format('M j, Y g:i A') }}.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Engineering Magnitude</flux:heading>
                    <span class="text-sm font-semibold">{{ $this->report['magnitude']['label'] }} - top {{ $this->report['magnitude']['percentile'] }}%</span>
                </div>
                <div class="mt-3 flex items-center gap-4">
                    <div class="text-4xl font-bold">{{ $this->report['magnitude']['total'] }}<span class="text-lg text-zinc-400">/1000</span></div>
                    <div class="h-3 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <div class="h-full rounded-full bg-gradient-to-r from-accent to-emerald-400 transition-all" style="width: {{ min(100, $this->report['magnitude']['total'] / 10) }}%"></div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3">
                    @foreach ($this->report['magnitude']['factors'] as $factor)
                        <div class="grid gap-1.5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ $factor['label'] }}</span>
                                <span class="text-zinc-500">{{ $factor['points'] }} / {{ $factor['max'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                                <div class="h-full rounded-full bg-accent/70" style="width: {{ $factor['max'] > 0 ? round($factor['points'] / $factor['max'] * 100) : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-zinc-500">{{ $factor['description'] }}</p>
                            @if ($factor['evidence'] !== [])
                                <div class="flex flex-wrap gap-1">
                                    @foreach (array_slice($factor['evidence'], 0, 3) as $item)
                                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] text-zinc-500 dark:bg-zinc-900">{{ $item }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm">Strengths</flux:heading>
                    <ul class="mt-3 grid gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        @forelse ($this->report['strengths'] as $strength)
                            <li class="flex gap-2"><flux:icon name="check-circle" variant="micro" class="mt-0.5 shrink-0 text-emerald-500" />{{ $strength }}</li>
                        @empty
                            <li class="text-zinc-400">No strengths derived yet.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm">Concerns</flux:heading>
                    <ul class="mt-3 grid gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        @forelse ($this->report['weaknesses'] as $weakness)
                            <li class="flex gap-2"><flux:icon name="exclamation-circle" variant="micro" class="mt-0.5 shrink-0 text-amber-500" />{{ $weakness }}</li>
                        @empty
                            <li class="text-zinc-400">No concerns identified.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Evidence library ({{ $this->report['evidence']['count'] }} sources)</flux:heading>
                <div class="mt-4 grid gap-4">
                    @forelse ($this->report['evidence']['top'] as $evidence)
                        <div class="rounded-lg border border-zinc-100 p-4 dark:border-zinc-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-medium">{{ $evidence['title'] }}</div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-zinc-500 dark:bg-zinc-900">{{ $evidence['type_label'] }}</span>
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-zinc-500 dark:bg-zinc-900">score {{ $evidence['ai_score'] }}</span>
                                    @if ($evidence['complexity'])
                                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-zinc-500 dark:bg-zinc-900">{{ $evidence['complexity'] }}</span>
                                    @endif
                                </div>
                            </div>
                            @if ($evidence['summary'])
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $evidence['summary'] }}</p>
                            @endif
                            @if ($evidence['technologies'] !== [])
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach (array_slice($evidence['technologies'], 0, 6) as $tech)
                                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium dark:bg-zinc-900">{{ $tech }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if ($evidence['url'])
                                <a href="{{ $evidence['url'] }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-accent hover:underline">
                                    <flux:icon name="arrow-top-right-on-square" variant="micro" /> View source
                                </a>
                            @endif
                        </div>
                    @empty
                        <flux:text>No analyzed evidence yet for this candidate.</flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Contact</flux:heading>
                @if ($this->user->canViewEmail(auth()->user()))
                    <div class="mt-3 grid gap-2.5 text-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                                <flux:icon name="envelope" variant="micro" />
                            </span>
                            <div class="min-w-0">
                                <a href="mailto:{{ $this->user->email }}" class="truncate font-medium text-accent hover:underline">{{ $this->user->email }}</a>
                                <div class="text-xs text-zinc-500">Email · shared with hiring teams</div>
                            </div>
                        </div>
                        @if ($this->user->github_url)
                            <a href="{{ $this->user->github_url }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-900">
                                    <flux:icon name="code-bracket" variant="micro" />
                                </span>
                                GitHub
                                <flux:icon name="arrow-top-right-on-square" variant="micro" class="ms-auto text-zinc-400" />
                            </a>
                        @endif
                        @if ($this->user->linkedin_url)
                            <a href="{{ $this->user->linkedin_url }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-900">
                                    <flux:icon name="briefcase" variant="micro" />
                                </span>
                                LinkedIn
                                <flux:icon name="arrow-top-right-on-square" variant="micro" class="ms-auto text-zinc-400" />
                            </a>
                        @endif
                        @if ($this->user->website_url)
                            <a href="{{ $this->user->website_url }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-900">
                                    <flux:icon name="globe-alt" variant="micro" />
                                </span>
                                Website
                                <flux:icon name="arrow-top-right-on-square" variant="micro" class="ms-auto text-zinc-400" />
                            </a>
                        @endif
                    </div>
                @else
                    <flux:text class="mt-2 text-sm">
                        Contact details are shared with verified recruiters and hiring companies only.
                    </flux:text>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Hiring risk</flux:heading>
                <div class="mt-3 flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold
                        {{ $this->risk['overall_risk'] === 'low' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : ($this->risk['overall_risk'] === 'medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300') }}">
                        {{ ucfirst($this->risk['overall_risk']) }} risk
                    </span>
                    <span class="text-sm text-zinc-500">{{ $this->risk['risk_score'] }} risk points</span>
                </div>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $this->risk['recommendation'] }}</p>
                <div class="mt-3 grid gap-2">
                    @foreach ($this->risk['risks'] as $risk)
                        <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">{{ $risk['title'] }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $risk['level'] === 'high' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : ($risk['level'] === 'medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900') }}">{{ $risk['level'] }}</span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">{{ $risk['detail'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Suggested roles</flux:heading>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @forelse ($this->report['suggested_roles'] as $role)
                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium dark:bg-zinc-900">{{ $role }}</span>
                    @empty
                        <flux:text class="text-sm">None derived yet.</flux:text>
                    @endforelse
                </div>

                <flux:heading size="sm" class="mt-5">Top technologies</flux:heading>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse (array_slice($this->report['evidence']['technologies'], 0, 12) as $tech)
                        <span class="rounded-full border border-zinc-200 px-2.5 py-0.5 text-xs dark:border-zinc-700">{{ $tech }}</span>
                    @empty
                        <flux:text class="text-sm">None detected.</flux:text>
                    @endforelse
                </div>

                <flux:heading size="sm" class="mt-5">Engineering areas</flux:heading>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse (array_slice($this->report['evidence']['engineering_areas'], 0, 8) as $area)
                        <span class="rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-medium text-accent">{{ $area }}</span>
                    @empty
                        <flux:text class="text-sm">None detected.</flux:text>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Community signals</flux:heading>
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><div class="font-semibold text-lg">{{ $this->report['community']['vouches'] }}</div><div class="text-zinc-500">vouches</div></div>
                    <div><div class="font-semibold text-lg">{{ $this->report['community']['projects_shipped'] }}</div><div class="text-zinc-500">projects</div></div>
                    <div><div class="font-semibold text-lg">{{ $this->report['community']['achievements'] }}</div><div class="text-zinc-500">achievements</div></div>
                    <div><div class="font-semibold text-lg">{{ $this->report['community']['streak'] }}</div><div class="text-zinc-500">day streak</div></div>
                </div>

                @if ($this->report['verification']['verified_skills'] !== [])
                    <flux:heading size="sm" class="mt-5">Verified skills</flux:heading>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($this->report['verification']['verified_skills'] as $skill)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <flux:icon name="check" variant="micro" /> {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Internal note</flux:heading>
                <form wire:submit="addNote" class="mt-3 grid gap-2">
                    <flux:textarea wire:model="noteBody" placeholder="Shared note for your team..." rows="3" />
                    <flux:button type="submit" size="sm" variant="primary">Add note</flux:button>
                </form>
            </div>
        </div>
    </div>

    <livewire:compare-tray :candidate-id="$this->user->id" />
</div>
