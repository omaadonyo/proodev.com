<?php

use App\Enums\EvidenceStatus;
use App\Jobs\AnalyzeEvidenceJob;
use App\Models\Evidence;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Evidence')] class extends Component
{
    public Evidence $evidence;

    public function mount(Evidence $evidence): void
    {
        abort_unless($evidence->user_id === auth()->id(), 404);

        $this->evidence = $evidence;
    }

    public function reanalyze(): void
    {
        $this->evidence->update([
            'status' => EvidenceStatus::Pending,
            'error' => null,
            'ai_score' => null,
            'analyzed_at' => null,
        ]);

        AnalyzeEvidenceJob::dispatch($this->evidence->fresh());

        Flux::toast(variant: 'success', text: 'Re-analysis queued.');
    }

    #[Computed]
    public function vouches()
    {
        return $this->evidence->vouches()
            ->where('status', 'approved')
            ->with('voucher')
            ->latest()
            ->get();
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <flux:heading size="xl" class="truncate">{{ $this->evidence->title }}</flux:heading>
                    <span class="shrink-0 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $this->evidence->type->label() }}</span>
                </div>
                <div class="mt-1 truncate text-sm text-zinc-500">
                    <a href="{{ $this->evidence->url }}" target="_blank" rel="noopener" class="text-accent hover:underline">
                        {{ $this->evidence->url }}
                    </a>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <flux:button variant="ghost" size="sm" wire:click="reanalyze({{ $this->evidence->id }})" wire:confirm="Re-analyze this evidence?">
                    <flux:icon name="arrow-path" variant="micro" />
                    Re-analyze
                </flux:button>
                <flux:button variant="primary" size="sm" :href="route('devid', auth()->user()->handle())" wire:navigate>
                    <flux:icon name="arrow-left" variant="micro" />
                    Back to DevID
                </flux:button>
            </div>
        </div>

        @if ($this->evidence->status->value === 'ready' && $this->evidence->analysis)
            @php($analysis = $this->evidence->analysis)

            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-950/80">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-white/10">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="sparkles" variant="micro" class="text-accent" />
                        AI Analysis Report
                    </span>
                    <div class="flex items-center gap-3">
                        @if ($this->evidence->ai_score !== null)
                            <span class="text-sm font-bold tabular-nums text-accent">{{ $this->evidence->ai_score }}/100</span>
                        @endif
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium capitalize text-zinc-500">
                            <flux:icon name="{{ match ($analysis->complexity) {
                                'advanced' => 'arrow-trending-up',
                                'complex' => 'signal',
                                default => 'bars-3',
                            } }}" variant="micro" />
                            {{ $analysis->complexity }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-5 p-5">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Summary</div>
                        <x-markdown :text="$analysis->summary" class="mt-1.5" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        @if ($analysis->technologies)
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Technologies Detected</div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($analysis->technologies as $tech)
                                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">{{ $tech }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($analysis->engineering_areas)
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Engineering Areas</div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($analysis->engineering_areas as $area)
                                        <span class="rounded-md bg-accent/10 px-2 py-1 text-xs font-medium text-accent">{{ $area }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($analysis->architecture_observations)
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Architecture Observations</div>
                            <x-markdown :text="$analysis->architecture_observations" class="mt-1.5" />
                        </div>
                    @endif

                    @if ($analysis->skills)
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Skills Identified</div>
                            <div class="mt-2 grid gap-2">
                                @foreach ($analysis->skills as $skill)
                                    <div class="flex items-center gap-3">
                                        <div class="w-32 shrink-0 text-xs font-medium text-zinc-700 dark:text-zinc-200">{{ $skill['name'] ?? '' }}</div>
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                                            <div class="h-full rounded-full bg-zinc-900 dark:bg-white" style="width: {{ $skill['confidence'] ?? 0 }}%"></div>
                                        </div>
                                        <div class="w-10 shrink-0 text-right text-xs tabular-nums text-zinc-500">{{ $skill['confidence'] ?? 0 }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($analysis->references)
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">References</div>
                            <div class="mt-2 grid gap-2">
                                @foreach ($analysis->references as $reference)
                                    <div class="rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2 dark:border-zinc-700/60 dark:bg-zinc-900/60">
                                        <div class="flex items-start gap-2 text-xs">
                                            <flux:icon name="link" variant="micro" class="mt-0.5 shrink-0 text-accent" />
                                            <div class="min-w-0">
                                                <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ $reference['claim'] ?? '' }}</div>
                                                <div class="mt-0.5 text-zinc-500">{{ $reference['reference'] ?? '' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
                @if ($this->evidence->status->value === 'failed')
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-rose-400/10 text-rose-500">
                        <flux:icon name="x-circle" />
                    </div>
                    <flux:heading>Analysis failed</flux:heading>
                    <p class="mt-2 text-sm text-zinc-500">{{ $this->evidence->error ?: 'We could not process this evidence source.' }}</p>
                    <flux:button variant="primary" size="sm" class="mt-4" wire:click="reanalyze({{ $this->evidence->id }})">
                        Try again
                    </flux:button>
                @else
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-amber-400/10 text-amber-500">
                        <flux:icon name="clock" />
                    </div>
                    <flux:heading>Analysis in progress</flux:heading>
                    <flux:text class="mt-2">We are extracting and analyzing this source. Refresh in a moment.</flux:text>
                @endif
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            @if ($this->evidence->analysis?->highlights)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm">Highlights</flux:heading>
                    <div class="mt-3 grid gap-2">
                        @foreach ($this->evidence->analysis->highlights as $highlight)
                            <div class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                <flux:icon name="check" variant="mini" class="mt-0.5 shrink-0 text-emerald-500" />
                                <span>{{ $highlight }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->evidence->analysis?->strengths)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm">Potential Strength Areas</flux:heading>
                    <div class="mt-3 grid gap-2">
                        @foreach ($this->evidence->analysis->strengths as $strength)
                            <div class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                <flux:icon name="arrow-trending-up" variant="mini" class="mt-0.5 shrink-0 text-accent" />
                                <span>{{ $strength }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if ($this->evidence->project_id)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Linked Project</flux:heading>
                <a href="{{ route('projects.show', $this->evidence->project_id) }}" wire:navigate class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-accent hover:underline">
                    <flux:icon name="folder-git-2" variant="micro" />
                    {{ $this->evidence->project->title ?? 'View project' }}
                </a>
            </div>
        @endif
    </div>
</div>
