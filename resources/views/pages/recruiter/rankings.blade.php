<?php

use App\Livewire\Concerns\InteractsWithTalentPools;
use App\Models\User;
use App\Services\Recruiter\CandidatePdfService;
use App\Services\Recruiter\RankingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Engineering Magnitude Rankings')] class extends Component
{
    use InteractsWithTalentPools;

    public ?string $area = null;

    public ?int $activeCandidateId = null;

    #[Computed]
    public function rankings()
    {
        return app(RankingService::class)->rankings($this->area);
    }

    #[Computed]
    public function areas(): array
    {
        return ['Backend Engineering', 'Frontend Engineering', 'API Engineering', 'Software Architecture', 'Data Engineering', 'DevOps', 'Security Engineering', 'Performance Engineering', 'Testing & Quality'];
    }

    #[Computed]
    public function activeEntry()
    {
        if ($this->activeCandidateId === null) {
            return null;
        }

        return collect($this->rankings)
            ->first(fn ($entry) => (int) $entry['developer']['id'] === $this->activeCandidateId);
    }

    #[Computed]
    public function candidate(): ?User
    {
        if ($this->activeCandidateId === null) {
            return null;
        }

        return User::with(['skills'])->find($this->activeCandidateId);
    }

    public function openCandidate(int $id): void
    {
        $this->activeCandidateId = $id;
    }

    public function exportCandidatePdf(): void
    {
        $candidate = $this->candidate;

        if (! $candidate) {
            return;
        }

        $pdf = app(CandidatePdfService::class)->forUser($candidate, auth()->user());
        $safeName = str_replace(' ', '-', strtolower($candidate->name));

        $this->dispatch('download', [
            'content' => base64_encode($pdf),
            'filename' => 'candidate-details-'.$safeName.'.pdf',
            'mime' => 'application/pdf',
            'base64' => true,
        ]);
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Engineering Magnitude Rankings</flux:heading>
        <flux:text>The strongest engineers in the network, ranked by an explainable 0-1000 evidence-backed score. Click any candidate for details and export.</flux:text>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <flux:button size="sm" variant="{{ $area === null ? 'primary' : 'ghost' }}" wire:click="$set('area', null)">
            All
        </flux:button>
        @foreach ($this->areas as $candidateArea)
            <flux:button size="sm" variant="{{ $area === $candidateArea ? 'primary' : 'ghost' }}" wire:click="$set('area', '{{ $candidateArea }}')">
                {{ $candidateArea }}
            </flux:button>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5">
        <div class="grid gap-0 divide-y divide-zinc-100 dark:divide-zinc-700">
            @forelse ($this->rankings as $entry)
                <div wire:key="rank-{{ $entry['developer']['id'] }}" class="group flex flex-wrap items-center gap-4 p-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-900">
                    <button
                        type="button"
                        wire:click="openCandidate({{ $entry['developer']['id'] }})"
                        @click="$flux.modal('candidate-details').open()"
                        class="flex min-w-0 flex-1 items-center gap-4 text-start"
                    >
                        <span @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold tabular-nums',
                            'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-sm shadow-amber-500/30' => $entry['rank'] === 1,
                            'bg-gradient-to-br from-zinc-300 to-zinc-400 text-white shadow-sm' => $entry['rank'] === 2,
                            'bg-gradient-to-br from-orange-300 to-amber-600 text-white shadow-sm' => $entry['rank'] === 3,
                            'bg-zinc-100 text-zinc-400 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-500 dark:ring-white/10' => $entry['rank'] > 3,
                        ])>
                            #{{ $entry['rank'] }}
                        </span>

                        <span class="relative shrink-0">
                            <flux:avatar :src="$entry['developer']['avatar']" :alt="$entry['developer']['name']" circle class="size-11" />
                            @if ($entry['label'] === 'Exceptional')
                                <span class="absolute -bottom-0.5 -right-0.5 rounded-full bg-emerald-500 p-0.5 text-white">
                                    <flux:icon name="check-badge" variant="micro" class="size-3" />
                                </span>
                            @endif
                        </span>

                        <span class="min-w-0">
                            <span class="flex items-center gap-2 font-semibold">
                                {{ $entry['developer']['name'] }}
                            </span>
                            <span class="block truncate text-sm text-zinc-500">{{ $entry['developer']['headline'] }}</span>
                            <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                                @if ($entry['developer']['location'])
                                    <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $entry['developer']['location'] }}</span>
                                @endif
                                @if ($entry['developer']['evidence_count'] > 0)
                                    <span>{{ $entry['developer']['evidence_count'] }} evidence sources</span>
                                @endif
                                @foreach ($entry['top_areas'] as $topArea)
                                    <span class="rounded-full bg-accent/10 px-2 py-0.5 font-medium text-accent">{{ $topArea }}</span>
                                @endforeach
                            </span>
                        </span>
                    </button>

                    <div class="w-40 shrink-0">
                        <div class="flex items-end justify-between">
                            <div class="text-2xl font-bold tabular-nums">{{ $entry['magnitude'] }}<span class="text-sm text-zinc-400">/1000</span></div>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                            <div class="h-full rounded-full bg-zinc-900 dark:bg-white" style="width: {{ min(100, $entry['magnitude'] / 10) }}%"></div>
                        </div>
                        <div class="mt-1 text-xs text-zinc-500">{{ $entry['label'] }} · top {{ $entry['percentile'] }}%</div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <x-save-to-pool :candidate-id="$entry['developer']['id']" :pools="$this->pools" />
                        <flux:button size="xs" variant="outline" :href="route('recruiter.candidates.show', $entry['developer']['id'])" wire:navigate>
                            View
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <flux:heading>No public candidates in this area yet.</flux:heading>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Candidate details modal --}}
    <flux:modal name="candidate-details" class="w-full max-w-lg overflow-hidden">
        @if ($this->activeEntry && $this->candidate)
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <flux:avatar :src="$this->candidate->avatarUrl()" :alt="$this->candidate->name" circle class="size-20" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg" class="truncate">{{ $this->candidate->name }}</flux:heading>
                            <x-verified-badge :user="$this->candidate" />
                        </div>
                        @if ($this->candidate->handle() || $this->candidate->location)
                            <div class="mt-0.5 truncate text-sm text-zinc-500">
                                @if ($this->candidate->handle()){{ '@'.$this->candidate->handle() }}@endif
                                @if ($this->candidate->handle() && $this->candidate->location) · @endif
                                @if ($this->candidate->location){{ $this->candidate->location }}@endif
                            </div>
                        @endif
                        @if ($this->candidate->headline)
                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $this->candidate->headline }}</div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900">
                        <div class="text-xl font-bold tabular-nums">{{ $this->activeEntry['magnitude'] }}</div>
                        <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Magnitude /1000</div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900">
                        <div class="text-xl font-bold tabular-nums">#{{ $this->activeEntry['rank'] }}</div>
                        <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Rank · top {{ $this->activeEntry['percentile'] }}%</div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900">
                        <div class="text-xl font-bold tabular-nums">{{ $this->candidate->evidence()->ready()->count() }}</div>
                        <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Evidence sources</div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-zinc-500">
                        <span>{{ $this->activeEntry['label'] }}</span>
                        <span class="tabular-nums">{{ number_format($this->candidate->reputation_score) }} reputation</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <div class="h-full rounded-full bg-zinc-900 dark:bg-white" style="width: {{ min(100, $this->activeEntry['magnitude'] / 10) }}%"></div>
                    </div>
                </div>

                @if ($this->candidate->skills->isNotEmpty())
                    <div class="mt-5">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Capabilities</div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($this->candidate->skills->take(8) as $skill)
                                <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">
                                    <x-tech-logo :name="$skill->name" class="size-3.5 shrink-0" />
                                    {{ $skill->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->activeEntry['top_areas'] !== [])
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($this->activeEntry['top_areas'] as $topArea)
                            <span class="rounded-full bg-accent/10 px-2.5 py-1 text-xs font-medium text-accent">{{ $topArea }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <flux:button variant="primary" wire:click="exportCandidatePdf">
                        <flux:icon name="document-arrow-down" variant="micro" />
                        Export details (PDF)
                    </flux:button>
                    <flux:button variant="outline" :href="route('recruiter.candidates.show', $this->candidate->id)" wire:navigate>
                        Full candidate report
                    </flux:button>
                    <flux:button variant="ghost" @click="$flux.modal('candidate-details').close()">Close</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
