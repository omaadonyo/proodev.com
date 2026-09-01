<?php

use App\Enums\EvidenceStatus;
use App\Models\Evidence;
use App\Models\RecruiterMatch;
use App\Models\TalentPool;
use App\Models\TalentPoolMember;
use App\Models\User;
use App\Services\Recruiter\AgencyWorkspaceService;
use App\Services\Recruiter\WorkspaceService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Agency Workspace')] class extends Component
{
    public ?int $activePoolId = null;

    public string $poolName = '';

    public bool $showCreatePool = false;

    public string $view = 'grid';

    public string $search = '';

    public function mount(): void
    {
        $default = app(AgencyWorkspaceService::class)->defaultPool(auth()->user());
        $this->activePoolId = (int) $default->id;
        $this->restorePoolState($this->activePoolId);
    }

    #[Computed]
    public function overview()
    {
        return app(AgencyWorkspaceService::class)->overview(auth()->user());
    }

    #[Computed]
    public function currentWorkspace()
    {
        return app(WorkspaceService::class)->current(auth()->user());
    }

    #[Computed]
    public function pools()
    {
        $workspace = app(WorkspaceService::class)->current(auth()->user());

        if ($workspace) {
            return TalentPool::where('workspace_id', $workspace->id)->withCount('members')->orderBy('name')->get();
        }

        return auth()->user()->talentPools()->withCount('members')->orderBy('name')->get();
    }

    #[Computed]
    public function activePool()
    {
        if (! $this->activePoolId) {
            return null;
        }

        return TalentPool::with(['members.candidate.skills'])->find($this->activePoolId);
    }

    public function selectPool(int $id): void
    {
        if ($this->activePoolId) {
            $this->persistPoolState((int) $this->activePoolId);
        }

        $this->activePoolId = $id;
        $this->restorePoolState($id);
    }

    public function createPool(): void
    {
        $this->validate(['poolName' => ['required', 'string', 'max:100']]);

        $pool = app(AgencyWorkspaceService::class)->createPool(auth()->user(), [
            'name' => $this->poolName,
            'kind' => 'collection',
            'is_shared' => true,
        ]);

        $this->activePoolId = (int) $pool->id;
        $this->poolName = '';
        $this->showCreatePool = false;
        $this->restorePoolState((int) $pool->id);
        $this->dispatch('toast', message: 'Talent pool created.', variant: 'success');
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['grid', 'list', 'detailed', 'avatars'], true) ? $view : 'grid';
        $this->persistPoolState((int) $this->activePoolId);
    }

    public function updatedSearch(): void
    {
        if ($this->activePoolId) {
            $this->persistPoolState((int) $this->activePoolId);
        }
    }

    /**
     * Load the saved view/search state for a pool from the user's preferences.
     */
    private function restorePoolState(int $poolId): void
    {
        $state = (auth()->user()->preferences['workspace_pool_state'][$poolId] ?? []);

        $this->view = in_array($state['view'] ?? null, ['grid', 'list', 'detailed', 'avatars'], true)
            ? $state['view']
            : 'grid';
        $this->search = (string) ($state['search'] ?? '');
    }

    /**
     * Remember the current view/search state for a pool across visits.
     */
    private function persistPoolState(int $poolId): void
    {
        $user = auth()->user();
        $preferences = $user->preferences ?? [];
        $preferences['workspace_pool_state'][$poolId] = [
            'view' => $this->view,
            'search' => $this->search,
        ];
        $user->forceFill(['preferences' => $preferences])->save();
    }

    public function setMemberStatus(int $memberId, string $status): void
    {
        $member = TalentPoolMember::find($memberId);
        if (! $member) {
            return;
        }

        $pool = $member->pool;

        $isMine = $pool->recruiter_id === auth()->id();
        $isShared = $pool->workspace_id !== null
            && app(WorkspaceService::class)->current(auth()->user())?->id === $pool->workspace_id;

        if (! $isMine && ! $isShared) {
            return;
        }

        $member->update(['status' => $status]);
        $this->dispatch('toast', message: 'Candidate status updated to '.ucfirst($status).'.', variant: 'success');
    }

    /**
     * Whether the recruiter has an active job match to badge candidates with.
     * The match context is shared from the evidence search page via the session.
     */
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
     * Share of the active job posting's keywords this candidate covers, 0-100.
     */
    public function matchPctFor(User $user): int
    {
        $ctx = RecruiterMatch::contextFor(auth()->user());

        if (! $ctx || ($ctx['skills'] ?? []) === []) {
            return 0;
        }

        $ownedSkills = $user->skills->pluck('slug')->map(fn ($slug) => (string) $slug)->all();

        $covered = 0;
        $total = 0;

        foreach ($ctx['skills'] as $skill) {
            $total++;
            if (in_array($skill, $ownedSkills, true)) {
                $covered++;
            }
        }

        if (! empty($ctx['include_technologies'])) {
            $ownedTechs = $this->technologyCoverage[$user->id] ?? [];

            foreach ($ctx['technologies'] ?? [] as $tech) {
                $total++;
                if (in_array(Str::lower((string) $tech), $ownedTechs, true)) {
                    $covered++;
                }
            }
        }

        return $total > 0 ? (int) round($covered / $total * 100) : 0;
    }

    /**
     * Lowercased evidence technologies for the pool's candidates, keyed by
     * user id, fetched in a single query (no per-row N+1).
     *
     * @return array<int, array<int, string>>
     */
    #[Computed]
    public function technologyCoverage(): array
    {
        if (! $this->activePool) {
            return [];
        }

        $ids = $this->activePool->members
            ->pluck('candidate_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === []) {
            return [];
        }

        return Evidence::query()
            ->where('status', EvidenceStatus::Ready)
            ->whereIn('user_id', $ids)
            ->with('analysis')
            ->get()
            ->groupBy('user_id')
            ->mapWithKeys(function ($items, $userId) {
                $techs = $items
                    ->flatMap(fn ($item) => $item->analysis?->technologies ?? [])
                    ->map(fn ($tech) => Str::lower((string) $tech))
                    ->unique()
                    ->values()
                    ->all();

                return [(int) $userId => $techs];
            })
            ->all();
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Agency Workspace</flux:heading>
            <flux:text>Manage talent pools, candidate statuses, interviews, and placements in one place.</flux:text>
            @if ($this->currentWorkspace)
                <flux:text class="mt-1 text-sm">
                    Working in <span class="font-semibold text-accent">{{ $this->currentWorkspace->name }}</span>
                    · <a href="{{ route('workspaces') }}" wire:navigate class="underline hover:text-accent">Switch workspace</a>
                </flux:text>
            @endif
        </div>
        <flux:button variant="primary" wire:click="$toggle('showCreatePool')">
            <flux:icon name="plus" variant="micro" />
            New pool
        </flux:button>
    </div>

    @if ($showCreatePool)
        <form wire:submit="createPool" class="flex items-end gap-3 rounded-xl border border-accent/40 bg-accent/5 p-4">
            <flux:field class="flex-1">
                <flux:label>Pool name</flux:label>
                <flux:input wire:model="poolName" placeholder="e.g. Q3 Backend Shortlist" />
            </flux:field>
            <flux:button type="submit" variant="primary">Create</flux:button>
        </form>
    @endif

    <div class="grid gap-6 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-1">
            <flux:heading size="sm">Talent pools</flux:heading>
            <div class="mt-3 grid gap-1">
                @forelse ($this->pools as $pool)
                    <button type="button" wire:click="selectPool({{ $pool->id }})"
                        class="flex items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition {{ $activePoolId === $pool->id ? 'bg-accent/10 font-semibold text-accent' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900' }}">
                        <span class="truncate">{{ $pool->name }}</span>
                        <span class="ml-2 shrink-0 text-xs text-zinc-400">{{ $pool->members_count }}</span>
                    </button>
                @empty
                    <flux:text class="text-sm">No pools yet.</flux:text>
                @endforelse
            </div>

            <flux:heading size="sm" class="mt-6">Pipeline</flux:heading>
            <div class="mt-2 grid gap-1.5 text-sm">
                @foreach (['saved' => 'Saved', 'shortlisted' => 'Shortlisted', 'contacted' => 'Contacted', 'interviewing' => 'Interviewing', 'offered' => 'Offered', 'placed' => 'Placed'] as $key => $label)
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500">{{ $label }}</span>
                        <span class="font-semibold">{{ $this->overview['status_counts'][$key] ?? 0 }}</span>
                    </div>
                @endforeach
            </div>

            <flux:heading size="sm" class="mt-6">Stats</flux:heading>
            <div class="mt-2 grid gap-1.5 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500">Active alerts</span>
                    <span class="font-semibold">{{ $this->overview['active_alerts'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500">Total candidates</span>
                    <span class="font-semibold">{{ $this->overview['total_candidates'] }}</span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:col-span-3">
            @if ($this->activePool)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <flux:heading size="sm">{{ $this->activePool->name }}</flux:heading>
                            @if ($this->activePool->description)
                                <flux:text class="text-sm">{{ $this->activePool->description }}</flux:text>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 rounded-xl bg-zinc-100 p-1.5 dark:bg-zinc-900">
                            @foreach (['grid' => ['squares-2x2', 'Grid'], 'list' => ['list-bullet', 'List'], 'detailed' => ['document-text', 'Detailed'], 'avatars' => ['user-circle', 'Avatars']] as $viewKey => [$viewIcon, $viewLabel])
                                <button
                                    type="button"
                                    wire:click="setView('{{ $viewKey }}')"
                                    title="{{ $viewLabel }} view"
                                    @class([
                                        'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition',
                                        'bg-white text-accent shadow-sm dark:bg-zinc-700' => $this->view === $viewKey,
                                        'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200' => $this->view !== $viewKey,
                                    ])
                                >
                                    <flux:icon :name="$viewIcon" variant="micro" class="size-4" />
                                    <span class="hidden sm:inline">{{ $viewLabel }}</span>
                                </button>
                            @endforeach
                        </div>
                        <flux:input wire:model.live.debounce.250ms="search" placeholder="Filter members…" class="w-44" clearable />
                    </div>

                    @php
                        $members = $this->activePool->members;
                        if (filled(trim($this->search))) {
                            $needle = strtolower(trim($this->search));
                            $members = $members->filter(fn ($member) => $member->candidate && (
                                str_contains(strtolower($member->candidate->name), $needle)
                                || str_contains(strtolower((string) $member->candidate->headline), $needle)
                                || str_contains(strtolower((string) $member->candidate->location), $needle)
                                || $member->candidate->skills->contains(fn ($skill) => str_contains(strtolower($skill->name), $needle))
                            ))->values();
                        }
                    @endphp

                    @if ($members->isEmpty())
                        <div class="mt-4 rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600">
                            <flux:text>No candidates in this pool. Save candidates from their intelligence report to add them here.</flux:text>
                        </div>
                    @elseif ($this->view === 'avatars')
                        <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-6 lg:grid-cols-8">
                            @foreach ($members as $member)
                                <a href="{{ route('recruiter.candidates.show', $member->candidate_id) }}" wire:navigate title="{{ $member->candidate->name }}"
                                    class="group flex flex-col items-center gap-1.5 rounded-lg p-2 transition hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                    <span class="relative">
                                        @php $memberPct = $this->matchPctFor($member->candidate); @endphp
                                        @if ($memberPct === 100 && $this->hasMatchBadges())
                                            <span class="block rounded-full p-[2.5px]" style="background: linear-gradient(135deg, #34d399, #14b8a6)">
                                                <flux:avatar :src="$member->candidate->avatarUrl()" :alt="$member->candidate->name" circle class="size-14 group-hover:ring-2 group-hover:ring-zinc-300" />
                                            </span>
                                        @else
                                            <flux:avatar :src="$member->candidate->avatarUrl()" :alt="$member->candidate->name" circle class="size-14 group-hover:ring-2 group-hover:ring-zinc-300" />
                                        @endif
                                        @if ($this->hasMatchBadges())
                                            <x-match-badge :pct="$memberPct" :metric="$this->matchMetric()" class="absolute -right-1 -top-1 ring-2 ring-white dark:ring-zinc-950" />
                                        @endif
                                        <span @class([
                                            'absolute -bottom-1 -right-1 flex size-4 items-center justify-center rounded-full ring-2 ring-white dark:ring-zinc-800',
                                            'bg-zinc-400' => $member->status === 'saved',
                                            'bg-sky-500' => $member->status === 'shortlisted' || $member->status === 'contacted',
                                            'bg-amber-500' => $member->status === 'interviewing',
                                            'bg-emerald-500' => in_array($member->status, ['offered', 'placed'], true),
                                            'bg-rose-500' => $member->status === 'rejected',
                                        ])></span>
                                    </span>
                                    <span class="w-full truncate text-center text-[10px] text-zinc-500">{{ $member->candidate->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    @elseif ($this->view === 'list')
                        <div class="mt-4 grid gap-2">
                            @foreach ($members as $member)
                                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-100 p-3 transition hover:border-accent dark:border-zinc-700">
                                    <a href="{{ route('recruiter.candidates.show', $member->candidate_id) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
                                        <flux:avatar :src="$member->candidate->avatarUrl()" :alt="$member->candidate->name" circle class="size-9" />
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <div class="truncate font-medium">{{ $member->candidate->name }}</div>
                                                <x-verified-badge :user="$member->candidate" compact />
                                                @if ($this->hasMatchBadges())
                                                    <x-match-badge :pct="$this->matchPctFor($member->candidate)" :metric="$this->matchMetric()" />
                                                @endif
                                            </div>
                                            <div class="truncate text-xs text-zinc-500">{{ $member->candidate->headline }}</div>
                                        </div>
                                    </a>
                                    <div class="hidden items-center gap-4 text-xs text-zinc-500 sm:flex">
                                        @if ($member->candidate->location)
                                            <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $member->candidate->location }}</span>
                                        @endif
                                        <span class="tabular-nums">{{ $member->candidate->levelTitle() }} · {{ number_format($member->candidate->experience_points) }} XP</span>
                                    </div>
                                    <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ Str::title($member->status) }}</span>
                                    <flux:dropdown>
                                        <flux:button size="xs" variant="ghost"><flux:icon name="ellipsis-vertical" variant="micro" /></flux:button>
                                        <flux:menu>
                                            @foreach (['saved', 'shortlisted', 'contacted', 'interviewing', 'offered', 'placed', 'rejected'] as $status)
                                                <flux:menu.item wire:click="setMemberStatus({{ $member->id }}, '{{ $status }}')" :selected="$member->status === $status">
                                                    {{ ucfirst($status) }}
                                                </flux:menu.item>
                                            @endforeach
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($this->view === 'detailed')
                        <div class="mt-4 grid gap-3">
                            @foreach ($members as $member)
                                <div class="rounded-xl border border-zinc-200 p-4 transition hover:border-accent dark:border-zinc-700">
                                    <div class="flex flex-wrap items-start gap-4">
                                        <a href="{{ route('recruiter.candidates.show', $member->candidate_id) }}" wire:navigate class="shrink-0">
                                            <flux:avatar :src="$member->candidate->avatarUrl()" :alt="$member->candidate->name" circle class="size-16" />
                                        </a>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="truncate text-lg font-semibold">{{ $member->candidate->name }}</div>
                                                <x-verified-badge :user="$member->candidate" />
                                                @if ($this->hasMatchBadges())
                                                    <x-match-badge :pct="$this->matchPctFor($member->candidate)" :metric="$this->matchMetric()" />
                                                @endif
                                            </div>
                                            @if ($member->candidate->headline)
                                                <div class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-300">{{ $member->candidate->headline }}</div>
                                            @endif
                                            @if ($member->candidate->bio)
                                                <p class="mt-2 line-clamp-2 text-sm text-zinc-500">{{ $member->candidate->bio }}</p>
                                            @endif
                                            @if ($member->candidate->skills->isNotEmpty())
                                                <div class="mt-3 flex flex-wrap gap-1.5">
                                                    @foreach ($member->candidate->skills->take(6) as $skill)
                                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">
                                                            <x-tech-logo :name="$skill->name" class="size-3.5 shrink-0" />
                                                            {{ $skill->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-zinc-500">
                                                @if ($member->candidate->location)
                                                    <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $member->candidate->location }}</span>
                                                @endif
                                                @if ($member->candidate->reputation_score > 0)
                                                    <span class="inline-flex items-center gap-1"><flux:icon name="shield-check" variant="micro" class="text-emerald-500" /> {{ number_format($member->candidate->reputation_score) }}</span>
                                                @endif
                                                <span class="tabular-nums">{{ $member->candidate->levelTitle() }} · {{ number_format($member->candidate->experience_points) }} XP</span>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 flex-col items-end gap-2">
                                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ Str::title($member->status) }}</span>
                                            <flux:dropdown>
                                                <flux:button size="xs" variant="ghost">
                                                    Change status <flux:icon name="chevron-down" variant="micro" />
                                                </flux:button>
                                                <flux:menu>
                                                    @foreach (['saved', 'shortlisted', 'contacted', 'interviewing', 'offered', 'placed', 'rejected'] as $status)
                                                        <flux:menu.item wire:click="setMemberStatus({{ $member->id }}, '{{ $status }}')" :selected="$member->status === $status">
                                                            {{ ucfirst($status) }}
                                                        </flux:menu.item>
                                                    @endforeach
                                                </flux:menu>
                                            </flux:dropdown>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($members as $member)
                                <div class="group relative rounded-xl border border-zinc-200 p-5 transition hover:border-accent dark:border-zinc-700">
                                    <a href="{{ route('recruiter.candidates.show', $member->candidate_id) }}" wire:navigate class="absolute inset-0 rounded-xl" aria-label="View {{ $member->candidate->name }}'s report"></a>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar :src="$member->candidate->avatarUrl()" :alt="$member->candidate->name" circle class="size-11" />
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <div class="truncate font-semibold group-hover:text-accent">{{ $member->candidate->name }}</div>
                                                <x-verified-badge :user="$member->candidate" compact />
                                                @if ($this->hasMatchBadges())
                                                    <x-match-badge :pct="$this->matchPctFor($member->candidate)" :metric="$this->matchMetric()" />
                                                @endif
                                            </div>
                                            <div class="truncate text-xs text-zinc-500">{{ $member->candidate->levelTitle() }} · {{ number_format($member->candidate->experience_points) }} XP</div>
                                        </div>
                                    </div>
                                    @if ($member->candidate->headline)
                                        <p class="mt-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $member->candidate->headline }}</p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                        @if ($member->candidate->location)
                                            <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $member->candidate->location }}</span>
                                        @endif
                                        @if ($member->candidate->reputation_score > 0)
                                            <span class="inline-flex items-center gap-1"><flux:icon name="shield-check" variant="micro" class="text-emerald-500" /> {{ $member->candidate->reputation_score }}</span>
                                        @endif
                                    </div>
                                    <div class="relative z-10 mt-3 flex items-center justify-between">
                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ Str::title($member->status) }}</span>
                                        <flux:dropdown>
                                            <flux:button size="xs" variant="ghost"><flux:icon name="ellipsis-vertical" variant="micro" /></flux:button>
                                            <flux:menu>
                                                @foreach (['saved', 'shortlisted', 'contacted', 'interviewing', 'offered', 'placed', 'rejected'] as $status)
                                                    <flux:menu.item wire:click="setMemberStatus({{ $member->id }}, '{{ $status }}')" :selected="$member->status === $status">
                                                        {{ ucfirst($status) }}
                                                    </flux:menu.item>
                                                @endforeach
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm">Scheduled interviews</flux:heading>
                    <div class="mt-3 grid gap-2">
                        @forelse ($this->overview['active_interviews'] as $interview)
                            <div class="flex items-center justify-between text-sm">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="font-medium">{{ $interview->candidate->name }}</div>
                                        <x-verified-badge :user="$interview->candidate" compact />
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $interview->scheduled_at?->format('M j, g:i A') }} - {{ $interview->mode ?? 'not set' }}</div>
                                </div>
                                <span class="rounded-full bg-accent/10 px-2 py-0.5 text-xs font-medium text-accent">{{ $interview->status }}</span>
                            </div>
                        @empty
                            <flux:text class="text-sm">No scheduled interviews yet.</flux:text>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm">Recent placements</flux:heading>
                    <div class="mt-3 grid gap-2">
                        @forelse ($this->overview['recent_placements'] as $placement)
                            <div class="flex items-center justify-between text-sm">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="font-medium">{{ $placement->candidate->name }}</div>
                                        <x-verified-badge :user="$placement->candidate" compact />
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $placement->role_title ?? 'new role' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $placement->company?->name ?? 'Client' }}</div>
                                </div>
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs dark:bg-zinc-900">{{ Str::title($placement->status) }}</span>
                            </div>
                        @empty
                            <flux:text class="text-sm">No placements yet.</flux:text>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
