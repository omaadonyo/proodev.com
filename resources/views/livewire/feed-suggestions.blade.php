<div x-data="{ open: false }" class="contents">
    {{-- Chat-style popup --}}
    <section
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="fixed bottom-[4.75rem] end-5 z-40 flex max-h-[min(32rem,70vh)] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl shadow-zinc-900/20 sm:end-6 dark:border-zinc-700/80 dark:bg-zinc-900"
        role="dialog"
        aria-label="Product suggestions"
    >
        {{-- Header --}}
        <header class="flex shrink-0 items-center justify-between gap-3 bg-zinc-950 px-4 py-3 dark:bg-black">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15">
                    <flux:icon name="light-bulb" variant="solid" class="size-4 text-amber-400" />
                </span>
                <div>
                    <div class="text-sm font-semibold text-white">Product suggestions</div>
                    <div class="flex items-center gap-1.5 text-[11px] text-zinc-400">
                        <span class="inline-block size-1.5 rounded-full bg-emerald-500"></span>
                        {{ $this->requestsCount }} open request{{ ($this->requestsCount ?? 0) === 1 ? '' : 's' }} · vote on what we build next
                    </div>
                </div>
            </div>
            <button
                type="button"
                x-on:click="open = false"
                class="inline-flex size-7 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-white/10 hover:text-white"
                aria-label="Close suggestions"
            >
                <flux:icon name="x-mark" variant="micro" />
            </button>
        </header>

        {{-- Requests list --}}
        <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-3 py-3">
            @forelse ($this->requests as $request)
                @php($voted = $request->hasVoted(auth()->id()))
                @php($remaining = max(0, $request->target_votes - $request->votes_count))
                @php($progress = min(100, (int) round(($request->votes_count / max(1, $request->target_votes)) * 100)))
                <article class="rounded-xl border border-zinc-200 p-3 transition hover:border-zinc-300 dark:border-zinc-700/80 dark:hover:border-zinc-600">
                    <h4 class="text-[13px] font-medium leading-snug text-zinc-900 dark:text-zinc-100">{{ $request->title }}</h4>

                    <div class="mt-2 flex items-center justify-between gap-2">
                        <span class="text-[10px] tabular-nums text-zinc-500 dark:text-zinc-400">
                            @if ($remaining > 0)
                                {{ $request->votes_count }} / {{ $request->target_votes }} · {{ number_format($remaining) }} more needed
                            @else
                                {{ $request->votes_count }} / {{ $request->target_votes }} · goal reached
                            @endif
                        </span>
                    </div>

                    <div class="mt-2 flex items-center gap-2.5">
                        <button
                            type="button"
                            wire:click="vote({{ $request->id }})"
                            @class([
                                'inline-flex shrink-0 items-center gap-1 rounded-lg border px-2 py-1 text-[11px] font-semibold transition',
                                'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20' => $voted,
                                'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-transparent dark:text-zinc-300 dark:hover:bg-white/5' => ! $voted,
                            ])
                        >
                            <flux:icon name="hand-thumb-up" :variant="$voted ? 'solid' : 'micro'" class="size-3" />
                            {{ $voted ? 'Voted' : 'Vote' }}
                            <span class="tabular-nums opacity-60">{{ $request->votes_count }}</span>
                        </button>

                        <div class="h-1 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800" title="{{ $progress }}% of the target">
                            <div class="h-full rounded-full bg-zinc-900 transition-all duration-500 dark:bg-white" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                </article>
            @empty
                <p class="px-2 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">No open suggestions right now.<br />Have an idea? Submit one below.</p>
            @endforelse

            @if ($this->included->isNotEmpty())
                <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/50 p-3 dark:border-emerald-500/20 dark:bg-emerald-500/5">
                    <div class="mb-1.5 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">
                        <flux:icon name="check-circle" variant="micro" class="size-3" />
                        Shipped
                    </div>
                    @foreach ($this->included as $done)
                        <div class="py-0.5 text-[11px] text-zinc-600 dark:text-zinc-300">{{ $done->title }}</div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Submit form --}}
        <form wire:submit="submit" class="shrink-0 border-t border-zinc-100 p-3 dark:border-zinc-700/80">
            <div class="flex items-center gap-2">
                <flux:input size="sm" wire:model="title" placeholder="Suggest a feature…" class="flex-1" wire:keydown.enter.prevent />
                <flux:button type="submit" size="sm" variant="primary" class="shrink-0 !px-3" wire:loading.attr="disabled">
                    <flux:icon name="paper-airplane" variant="micro" />
                </flux:button>
            </div>
            @error('title') <flux:error size="xs" name="title" class="mt-1" /> @enderror
        </form>
    </section>

    {{-- Floating bubble --}}
    <button
        type="button"
        x-show="!open"
        x-cloak
        x-on:click="open = true"
        title="Product suggestions"
        class="fixed bottom-5 end-5 z-40 inline-flex size-12 items-center justify-center rounded-full bg-zinc-950 text-white shadow-lg shadow-zinc-900/30 ring-1 ring-white/10 transition-all duration-200 hover:scale-105 hover:bg-zinc-800 sm:end-6 dark:bg-white dark:text-zinc-900 dark:ring-zinc-950/10 dark:hover:bg-zinc-100"
    >
        <flux:icon name="chat-bubble-bottom-center-text" variant="solid" class="size-5" />
        @if (($this->requestsCount ?? 0) > 0)
            <span class="absolute -end-0.5 -top-0.5 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-emerald-500 px-1 py-0.5 text-[10px] font-bold tabular-nums text-white ring-2 ring-white dark:ring-zinc-900">
                {{ $this->requestsCount }}
            </span>
        @endif
    </button>
</div>
