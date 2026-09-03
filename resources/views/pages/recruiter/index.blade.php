<?php

use App\Models\User;
use App\Services\EngineeringMagnitudeService;
use App\Services\Recruiter\AgencyWorkspaceService;
use App\Services\Recruiter\RankingService;
use App\Services\Recruiter\RecruiterAccessService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recruiter Intelligence Hub')] class extends Component {
    #[Computed]
    public function overview()
    {
        return app(AgencyWorkspaceService::class)->overview(auth()->user());
    }

    #[Computed]
    public function topCandidates()
    {
        return app(RankingService::class)->rankings(limit: 8);
    }

    #[Computed]
    public function marketPulse()
    {
        $total = User::visibleToPublic()->where('public_passport', true)->count();
        $withEvidence = User::visibleToPublic()->where('public_passport', true)->whereHas('evidence', fn ($q) => $q->ready())->count();
        $verified = User::visibleToPublic()->where('public_passport', true)
            ->where(fn ($q) => $q->where('is_verified', true)->orWhereHas('verificationRequests', fn ($r) => $r->where('status', 'approved')))
            ->count();

        return [
            'total' => $total,
            'with_evidence' => $withEvidence,
            'verified' => $verified,
        ];
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Recruiter Intelligence Hub</flux:heading>
            <flux:text>Evidence-backed talent intelligence. LinkedIn shows claims, ProoDev shows proof.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button :href="route('recruiter.search')" wire:navigate variant="primary">
                <flux:icon name="magnifying-glass" variant="micro" />
                Find talent
            </flux:button>
            <flux:button :href="route('recruiter.compare')" wire:navigate>
                <flux:icon name="scale" variant="micro" />
                Compare
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
            <div class="flex items-center gap-2 text-sm font-medium text-zinc-500">
                <flux:icon name="users" variant="micro" />
                Public engineers
            </div>
            <div class="mt-2 text-3xl font-bold">{{ number_format($this->marketPulse['total']) }}</div>
        </div>
        <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
            <div class="flex items-center gap-2 text-sm font-medium text-zinc-500">
                <flux:icon name="document-text" variant="micro" />
                With analyzed evidence
            </div>
            <div class="mt-2 text-3xl font-bold">{{ number_format($this->marketPulse['with_evidence']) }}</div>
        </div>
        <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
            <div class="flex items-center gap-2 text-sm font-medium text-zinc-500">
                <flux:icon name="check-badge" variant="micro" />
                Verified engineers
            </div>
            <div class="mt-2 text-3xl font-bold">{{ number_format($this->marketPulse['verified']) }}</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
            <flux:heading size="sm">Top candidates by Engineering Magnitude</flux:heading>
            <flux:text class="mt-1 text-sm">Every point is tied to analyzed evidence.</flux:text>

            <div class="mt-4 grid gap-3">
                @forelse ($this->topCandidates as $entry)
                    <a href="{{ route('recruiter.candidates.show', $entry['developer']['id']) }}" wire:navigate class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 transition hover:border-accent dark:border-zinc-700">
                        <span class="w-6 text-center text-sm font-semibold text-zinc-400">{{ $entry['rank'] }}</span>
                        <flux:avatar :src="$entry['developer']['avatar']" :alt="$entry['developer']['name']" circle class="size-9" />
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium">{{ $entry['developer']['name'] }}</div>
                            <div class="truncate text-xs text-zinc-500">{{ $entry['developer']['headline'] }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">{{ $entry['magnitude'] }}<span class="text-xs text-zinc-400">/1000</span></div>
                            <div class="text-xs text-zinc-500">{{ $entry['label'] }}</div>
                        </div>
                    </a>
                @empty
                    <flux:text>No public candidates yet.</flux:text>
                @endforelse
            </div>
        </div>

        <div class="grid gap-6">
            <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                <flux:heading size="sm">Your pipeline</flux:heading>
                <div class="mt-3 grid gap-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Candidates in pools</span>
                        <span class="font-semibold">{{ $this->overview['total_candidates'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Active talent alerts</span>
                        <span class="font-semibold">{{ $this->overview['active_alerts'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Scheduled interviews</span>
                        <span class="font-semibold">{{ $this->overview['active_interviews']->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Open placements</span>
                        <span class="font-semibold">{{ $this->overview['recent_placements']->where('status', 'in_progress')->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Talent pools</flux:heading>
                    <flux:button size="xs" :href="route('recruiter.workspace')" wire:navigate>Manage</flux:button>
                </div>
                <div class="mt-3 grid gap-2">
                    @forelse ($this->overview['pools']->take(5) as $pool)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium">{{ $pool->name }}</span>
                            <span class="text-zinc-500">{{ $pool->members_count }} candidates</span>
                        </div>
                    @empty
                        <flux:text class="text-sm">Create your first talent pool in the workspace.</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
