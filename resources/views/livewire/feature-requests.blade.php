<div x-data="{ open: false }" class="fixed bottom-8 right-5 z-50 sm:bottom-10 sm:right-6">
    {{-- Slide-up panel --}}
    <div
        x-show="open"
        style="display: none;"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        role="dialog"
        aria-label="{{ __('Feature requests and suggestions') }}"
        class="absolute bottom-[5.5rem] right-0 flex max-h-[min(38rem,calc(100vh-8rem))] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl bg-zinc-100 shadow-2xl shadow-zinc-900/20 dark:bg-zinc-900 dark:ring-1 dark:ring-white/10"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-full bg-amber-400/15 text-amber-500">
                    <flux:icon name="light-bulb" variant="mini" class="size-4" />
                </span>
                <div>
                    <div class="text-sm font-semibold leading-tight text-zinc-900 dark:text-white">{{ __('Feature Requests') }}</div>
                    <div class="text-[11px] leading-tight text-zinc-400">{{ __('Suggest & vote on what we build next') }}</div>
                </div>
            </div>
            <button
                type="button"
                @click="open = false"
                class="flex size-7 items-center justify-center rounded-full text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                aria-label="{{ __('Close') }}"
            >
                <flux:icon name="x-mark" variant="micro" class="size-4" />
            </button>
        </div>

        @if (session('feature-request-status'))
            <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('feature-request-status') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="flex gap-1.5 px-4 pt-3">
            @foreach (['approved' => __('Open'), 'pending' => __('Review'), 'built' => __('Shipped')] as $key => $label)
                <button
                    wire:click="setFilter('{{ $key }}')"
                    wire:key="fr-tab-{{ $key }}"
                    @class([
                        'inline-flex h-7 items-center rounded-full px-3 text-xs font-medium transition-colors',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $this->filter === $key,
                        'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => $this->filter !== $key,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach

            <button
                type="button"
                wire:click="toggleComposer"
                class="ms-auto inline-flex h-7 items-center gap-1 rounded-full border border-dashed border-zinc-300 px-2.5 text-xs font-semibold text-accent transition hover:border-accent/50 hover:bg-accent/5 dark:border-zinc-600"
            >
                <flux:icon name="plus" variant="micro" class="size-3" />
                {{ __('Suggest') }}
            </button>
        </div>

        {{-- Composer --}}
        @if ($showComposer)
            <form wire:submit="save" class="grid gap-2 border-b border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                <div>
                    <input
                        type="text"
                        wire:model="title"
                        placeholder="{{ __('One-line summary of your idea…') }}"
                        maxlength="120"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    />
                    @error('title') <p class="mt-1 text-[11px] font-medium text-red-500">{{ $message }}</p> @enderror
                </div>
                <textarea
                    wire:model="description"
                    rows="2"
                    maxlength="500"
                    placeholder="{{ __('Add more detail (optional)…') }}"
                    class="w-full resize-none rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                ></textarea>
                @error('description') <p class="text-[11px] font-medium text-red-500">{{ $message }}</p> @enderror
                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="toggleComposer" class="h-8 rounded-full px-3 text-xs font-semibold text-zinc-500 transition hover:bg-zinc-200/70 dark:hover:bg-zinc-800">
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex h-8 items-center gap-1.5 rounded-full bg-zinc-900 px-4 text-xs font-semibold text-white transition hover:bg-zinc-700 disabled:opacity-50 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        {{ __('Submit idea') }}
                    </button>
                </div>
            </form>
        @endif

        {{-- List --}}
        <div class="grid flex-1 content-start gap-2 overflow-y-auto p-3" wire:loading.class="opacity-60 pointer-events-none" wire:target="vote, setFilter, save, approve, buildFeature">
            @forelse ($this->featureRequests as $feature)
                <div
                    wire:key="fr-card-{{ $feature->id }}-{{ $feature->voted_by_me ? 'v' : 'n' }}-{{ $feature->votes }}"
                    class="rounded-xl p-3 transition @if ($feature->status === 'built') border border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-900/10 @else bg-zinc-100 dark:bg-white/5 @endif"
                >
                    <div class="flex gap-3">
                        {{-- Vote button --}}
                        @unless ($feature->status === 'built')
                            <button
                                type="button"
                                wire:click="vote({{ $feature->id }})"
                                title="{{ $feature->voted_by_me ? __('Remove your vote') : __('Upvote this suggestion') }}"
                                @class([
                                    'flex h-11 w-10 shrink-0 flex-col items-center justify-center gap-0.5 rounded-lg border text-sm font-bold tabular-nums transition',
                                    'border-accent/40 bg-accent/10 text-accent' => $feature->voted_by_me,
                                    'border-zinc-200 bg-zinc-50 text-zinc-600 hover:border-accent/40 hover:text-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => ! $feature->voted_by_me,
                                ])
                            >
                                <flux:icon name="chevron-up" variant="micro" class="size-3" />
                                {{ number_format($feature->votes) }}
                            </button>
                        @else
                            <div class="flex h-11 w-10 shrink-0 flex-col items-center justify-center rounded-lg border border-emerald-200 bg-emerald-100 text-sm font-bold tabular-nums text-emerald-600 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                <flux:icon name="check" variant="micro" class="size-3" />
                                {{ number_format($feature->votes) }}
                            </div>
                        @endunless

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold leading-snug text-zinc-900 dark:text-white">{{ $feature->title }}</div>
                            @if ($feature->description)
                                <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $feature->description }}</p>
                            @endif

                            @if ($feature->status === 'built')
                                <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ __('Shipped') }}{{ $feature->built_at ? ' · '.$feature->built_at->diffForHumans() : '' }}
                                </span>
                            @elseif ($feature->admin_target > 0)
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200/80 dark:bg-zinc-700">
                                        <div
                                            class="h-full rounded-full bg-zinc-900 transition-all duration-500 dark:bg-white"
                                            style="width: {{ min(100, round(($feature->votes / max($feature->admin_target, 1)) * 100)) }}%"
                                        ></div>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-semibold tabular-nums text-zinc-400">{{ number_format($feature->votes) }}/{{ number_format($feature->admin_target) }}</span>
                                </div>
                            @endif

                            @if ($feature->status === 'approved' && $feature->admin_target > 0 && $feature->votes >= $feature->admin_target)
                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-violet-500/10 px-2 py-0.5 text-[10px] font-semibold text-violet-600 dark:text-violet-400">
                                    {{ __('Target reached, queued for build') }}
                                </span>
                            @endif

                            {{-- Admin controls --}}
                            @if (auth()->user()?->isAdmin())
                                @if ($feature->status === 'pending')
                                    <div class="mt-2 flex items-center gap-1.5">
                                        <input
                                            type="number"
                                            min="1"
                                            wire:model="targets.{{ $feature->id }}"
                                            placeholder="Target"
                                            class="h-6 w-16 rounded-md border border-zinc-200 bg-white px-1.5 text-[11px] tabular-nums text-zinc-700 focus:border-accent focus:outline-none dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200"
                                        />
                                        <button
                                            type="button"
                                            wire:click="approve({{ $feature->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="approve({{ $feature->id }})"
                                            class="inline-flex h-6 items-center rounded-md border border-emerald-500/50 px-2 text-[10px] font-semibold text-emerald-600 transition hover:bg-emerald-500/10 disabled:opacity-50 dark:border-emerald-400/50 dark:text-emerald-400"
                                        >
                                            Approve
                                        </button>
                                    </div>
                                @elseif ($feature->status === 'approved' && $feature->votes >= $feature->admin_target)
                                    <button
                                        type="button"
                                        wire:click="buildFeature({{ $feature->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="buildFeature({{ $feature->id }})"
                                        class="mt-2 inline-flex h-6 items-center rounded-md border border-emerald-500 px-2 text-[10px] font-semibold text-emerald-600 transition hover:bg-emerald-500/10 disabled:opacity-50 dark:border-emerald-400 dark:text-emerald-400"
                                    >
                                        Mark as built
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center">
                    <span class="mx-auto mb-2 flex size-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                        <flux:icon name="light-bulb" class="size-5" />
                    </span>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        {{ match ($this->filter) {
                            'built' => __('No shipped features yet.'),
                            'pending' => __('Nothing awaiting review.'),
                            default => __('No open requests yet, suggest one!'),
                        } }}
                    </p>
                </div>
            @endforelse

            @if ($this->featureRequests->hasMorePages())
                <button
                    type="button"
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    wire:target="loadMore"
                    class="mt-1 flex h-8 items-center justify-center rounded-full border border-zinc-200 text-xs font-semibold text-zinc-600 transition hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >
                    {{ __('Load more') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Toggle bubble --}}
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open ? 'true' : 'false'"
        aria-label="{{ __('Toggle feature requests') }}"
        class="relative flex size-14 items-center justify-center rounded-full bg-zinc-900 text-white shadow-xl shadow-zinc-900/25 ring-1 ring-black/10 transition duration-150 hover:scale-105 active:scale-95 dark:bg-white dark:text-zinc-900"
    >
        <flux:icon name="light-bulb" x-show="!open" class="size-6" />
        <flux:icon name="x-mark" x-show="open" x-cloak class="size-5" />

        @php $badgeCount = $this->counts['approved']; @endphp
        @if ($badgeCount > 0)
            <span
                x-show="!open"
                class="absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full border-2 border-white bg-red-500 px-1 text-[11px] font-bold tabular-nums text-white dark:border-zinc-950"
            >
                {{ $badgeCount > 99 ? '99+' : $badgeCount }}
            </span>
        @endif
    </button>
</div>
