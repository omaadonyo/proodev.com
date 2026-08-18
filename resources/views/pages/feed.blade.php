<?php

use App\Enums\TimelineEventType;
use App\Services\FeedService;
use App\Services\ProfileCompletionService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Home')] class extends Component
{
    public ?string $filter = null;

    public int $perPage = 20;

    public string $layout = 'list';

    public const array LAYOUTS = ['list', 'grid', 'compact'];

    public function mount(): void
    {
        $this->layout = auth()->user()->feed_layout ?? 'list';
    }

    public function setLayout(string $layout): void
    {
        if (! in_array($layout, self::LAYOUTS, true)) {
            return;
        }

        $this->layout = $layout;
        auth()->user()->update(['feed_layout' => $layout]);
    }

    #[Computed]
    public function counts(): array
    {
        return app(FeedService::class)->counts();
    }

    #[Computed]
    public function profileCompletion(): int
    {
        return app(ProfileCompletionService::class)->percentage(auth()->user());
    }

    #[Computed]
    public function events()
    {
        $type = $this->filter ? TimelineEventType::tryFrom($this->filter) : null;

        return app(FeedService::class)->feed(auth()->user(), $type, $this->perPage);
    }

    public function setFilter(?string $filter = null): void
    {
        $this->filter = $filter ?: null;
        $this->perPage = 20;
        unset($this->events);
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
        unset($this->events);
    }

    #[On('echo:feed,feed-event')]
    public function refreshFeed(): void
    {
        unset($this->counts, $this->events);
    }
}
?>

<div class="mx-auto w-full max-w-7xl">
    <div class="grid min-w-0 items-start gap-4 xl:grid-cols-[minmax(0,1fr)_288px]">
    <div class="min-w-0">
        <div class="mx-auto w-full max-w-3xl">
        <div class="mb-6 grid gap-1">
            <flux:heading size="xl">
                @php($hour = now()->hour)
                {{ $hour < 12 ? 'Good morning — let’s build something great.' : ($hour < 18 ? 'Keep the momentum going.' : 'Wrapping up with a ship.') }}
            </flux:heading>
            <flux:text>
                Welcome back, <span class="font-semibold">{{ auth()->user()->name }}</span>. Here's what the engineering community is building.
            </flux:text>
        </div>

        @unless (auth()->user()->hasCompletedOnboarding() || $this->profileCompletion > 75)
            <div class="mb-5 overflow-hidden rounded-xl border border-accent/20 bg-white p-4 dark:border-white/10 dark:bg-zinc-800">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">Profile completion</div>
                            <span class="text-sm font-semibold tabular-nums text-accent">{{ $this->profileCompletion }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                            <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-cyan-400 transition-all" style="width: {{ $this->profileCompletion }}%"></div>
                        </div>
                    </div>
                    <a href="{{ route('onboarding') }}" wire:navigate class="inline-flex h-8 items-center gap-1.5 rounded-full bg-zinc-900 px-4 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <flux:icon name="sparkles" variant="micro" class="size-4" />
                        Complete onboarding
                    </a>
                </div>
            </div>
        @endunless

        <div class="mb-5">
            <livewire:scout-runner />
        </div>

        <div class="mb-5 flex flex-wrap items-center gap-2">
            <button
                wire:click="setFilter()"
                @class([
                    'inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-[13px] font-medium transition-colors',
                    'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200' => $filter === null,
                    'bg-zinc-200/80 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => $filter !== null,
                ])
            >
                All
            </button>
            @foreach ([
                'project-published' => ['Projects', 'folder-git-2'],
                'vouch-received' => ['Vouches', 'shield-check'],
                'badge-earned' => ['Badges', 'trophy'],
                'achievement-verified' => ['Verified', 'check-badge'],
            ] as $key => [$label, $icon])
                <button
                    wire:click="setFilter('{{ $key }}')"
                    @class([
                        'inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-[13px] font-medium transition-colors',
                        'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200' => $filter === $key,
                        'bg-zinc-200/80 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => $filter !== $key,
                    ])
                >
                    <flux:icon :name="$icon" variant="micro" />
                    {{ $label }}
                </button>
            @endforeach

            <div class="ms-auto inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-900">
                <button
                    wire:click="setLayout('list')"
                    :aria-pressed="$layout === 'list'"
                    title="List layout"
                    @class([
                        'flex size-7 items-center justify-center rounded-full transition-colors',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $layout === 'list',
                        'text-zinc-500 hover:bg-zinc-200 dark:text-zinc-400 dark:hover:bg-zinc-800' => $layout !== 'list',
                    ])
                >
                    <flux:icon name="list-bullet" variant="micro" />
                </button>
                <button
                    wire:click="setLayout('grid')"
                    :aria-pressed="$layout === 'grid'"
                    title="Grid layout"
                    @class([
                        'flex size-7 items-center justify-center rounded-full transition-colors',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $layout === 'grid',
                        'text-zinc-500 hover:bg-zinc-200 dark:text-zinc-400 dark:hover:bg-zinc-800' => $layout !== 'grid',
                    ])
                >
                    <flux:icon name="squares-2x2" variant="micro" />
                </button>
                <button
                    wire:click="setLayout('compact')"
                    :aria-pressed="$layout === 'compact'"
                    title="Compact layout"
                    @class([
                        'flex size-7 items-center justify-center rounded-full transition-colors',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $layout === 'compact',
                        'text-zinc-500 hover:bg-zinc-200 dark:text-zinc-400 dark:hover:bg-zinc-800' => $layout !== 'compact',
                    ])
                >
                    <flux:icon name="bars-3" variant="micro" />
                </button>
            </div>
        </div>

        <div wire:loading.class="opacity-60 pointer-events-none" wire:target="setFilter" class="transition-opacity {{ $layout === 'grid' ? 'grid items-stretch gap-4 md:grid-cols-2' : ($layout === 'compact' ? 'grid gap-2' : 'grid gap-4') }}">
            @forelse ($this->events as $event)
                <x-timeline-card :event="$event" :compact="$layout === 'grid'" :dense="$layout === 'compact'" />
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-600 {{ $layout === 'grid' ? 'md:col-span-2' : '' }}">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <flux:icon name="sparkles" />
                    </div>
                    <flux:heading>The feed is warming up</flux:heading>
                    <flux:text>Publish a project or add evidence to be the first spark.</flux:text>
                </div>
            @endforelse

            @if ($this->events->isNotEmpty())
                @if ($this->events->hasMorePages())
                    <div
                        wire:key="feed-sentinel-{{ $this->perPage }}"
                        wire:intersect.once.margin.200px="loadMore"
                        class="py-6 {{ $layout === 'grid' ? 'md:col-span-2' : '' }}"
                    >
                        <div class="hidden" wire:loading.class.remove="hidden" wire:target="loadMore">
                            <div class="{{ $layout === 'grid' ? 'grid items-stretch gap-4 md:grid-cols-2' : ($layout === 'compact' ? 'grid gap-2' : 'grid gap-4') }}">
                                <x-feed-card-skeleton :compact="$layout === 'grid'" :dense="$layout === 'compact'" />
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-center gap-2 py-6 text-sm text-zinc-400 {{ $layout === 'grid' ? 'md:col-span-2' : '' }}">
                        <flux:icon name="check" variant="mini" />
                        You're all caught up.
                    </div>
                @endif
            @endif
        </div>
        </div>
    </div>

    <livewire:right-panel />
    </div>
</div>
