<div class="w-full">
    @if ($phase === 'input')
        <form wire:submit="begin" class="rounded-xl bg-zinc-100 p-[calc(var(--spacing)*2)] dark:bg-white/5">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <flux:icon name="magnifying-glass" variant="micro" />
                </div>
                <div class="flex-1">
                    <flux:input
                        wire:model="url"
                        type="url"
                        placeholder="Paste a GitHub profile, repo or project URL to scout it live…"
                        class="border-none bg-transparent shadow-none focus:ring-0"
                    />
                </div>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Scout
                </flux:button>
            </div>
            <flux:error name="url" class="mt-2" />
            @if ($error)
                <p class="mt-2 text-left text-xs text-red-500">{{ $error }}</p>
            @endif
            <p class="mt-2 text-left text-[11px] text-zinc-400">
                Profiles scan every public repository. Repos, projects, journal and magnitude build live as evidence is found.
            </p>
            @if (! auth()->user()->github_url)
                <div
                    x-data="{ open: localStorage.getItem('scoutPlagiarismNotice') !== 'dismissed' }"
                    x-cloak
                    x-show="open"
                    class="relative mt-3 rounded-lg border border-amber-300/40 bg-amber-50 p-3 text-left text-xs text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200"
                >
                    <button
                        type="button"
                        @click="open = false; localStorage.setItem('scoutPlagiarismNotice', 'dismissed')"
                        aria-label="Dismiss notice"
                        class="absolute top-2 right-2 rounded-md p-1 text-amber-500 transition hover:bg-amber-100 hover:text-amber-700 dark:hover:bg-amber-400/10 dark:hover:text-amber-200"
                    >
                        <flux:icon name="x-mark" variant="micro" class="size-3.5" />
                    </button>
                    <div class="flex items-start gap-2 pe-5">
                        <flux:icon name="exclamation-triangle" variant="micro" class="mt-0.5 shrink-0" />
                        <div>
                            <div class="text-[11px] font-semibold">Claiming work that isn't yours is treated as plagiarism</div>
                            <p class="mt-1 text-[11px] leading-relaxed">
                                You haven't linked a GitHub account, so ownership can't be verified. If you scout a repository that another ProoDev user already claimed, or one you can't show as your own, it will be flagged and removed.
                                <a href="{{ route('profile.edit') }}" wire:navigate class="font-semibold text-amber-900 underline underline-offset-2 hover:no-underline dark:text-amber-100">Link your GitHub in settings</a> to verify yourself.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    @elseif ($phase === 'scouting')
        <div wire:poll.600ms="tick" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_280px]">
            {{-- Terminal --}}
            <div class="flex h-full max-h-[560px] flex-col overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950 shadow-xl">
                <div class="flex shrink-0 items-center gap-1.5 border-b border-zinc-800/80 px-3 py-2">
                    <span class="size-2.5 rounded-full bg-rose-500/80"></span>
                    <span class="size-2.5 rounded-full bg-amber-500/80"></span>
                    <span class="size-2.5 rounded-full bg-emerald-500/80"></span>
                    <span class="ms-2 font-mono text-xs text-zinc-500">proodev · scout</span>
                    <span class="ms-auto font-mono text-xs tabular-nums text-zinc-600">{{ $this->progress }}%</span>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3 font-mono text-[12.5px] leading-6">
                    {{-- Section checklist --}}
                    <div class="mb-3 grid gap-1 border-b border-zinc-800/60 pb-3">
                        @foreach ($this->sections as $section)
                            @php
                                $done = in_array($section['key'], $this->completed, true);
                                $active = ! $done && ($this->plan[$this->step]['kind'] ?? null) === $section['key'];
                            @endphp
                            <div class="flex items-center gap-2 text-xs">
                                @if ($done)
                                    <span class="text-emerald-400">✔</span>
                                    <span class="text-zinc-500 line-through decoration-zinc-700">{{ $section['label'] }}</span>
                                @elseif ($active)
                                    <span class="text-cyan-400">{{ $this->spinner }}</span>
                                    <span class="text-zinc-200">{{ $section['label'] }}</span>
                                @else
                                    <span class="text-zinc-700">—</span>
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
                            @elseif ($entry['kind'] === 'dim')
                                <span class="w-4 shrink-0"></span>
                                <span class="truncate text-zinc-600">{{ $entry['text'] }}</span>
                            @else
                                <span class="shrink-0 text-cyan-400">{{ $this->spinner }}</span>
                                <span class="flex-1 truncate text-zinc-400">{{ $entry['text'] }}</span>
                            @endif

                            @if ($entry['meta'])
                                <span class="shrink-0 tabular-nums text-zinc-600">{{ $entry['meta'] }}</span>
                            @endif
                        </div>
                    @endforeach

                    <div class="flex items-center gap-2 text-emerald-400">
                        <span class="shrink-0">{{ $this->spinner }}</span>
                        <span>{{ $this->currentTask ?? 'processing' }}…</span>
                    </div>
                </div>
            </div>

            {{-- Live passport build --}}
            <div class="flex h-full max-h-[560px] flex-col overflow-hidden rounded-xl bg-zinc-100 shadow-lg dark:bg-white/5">
                <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-3 py-2 dark:border-white/10">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="check-badge" variant="micro" class="text-emerald-500" />
                        DevID build
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        live
                    </span>
                </div>

                <div class="grid min-h-0 flex-1 gap-3 overflow-y-auto p-3">
                    {{-- Profile --}}
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            @if ($this->passport['profile']['avatar'] ?? null)
                                <img src="{{ $this->passport['profile']['avatar'] }}" alt="" class="size-11 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-white/10" />
                            @else
                                <div class="flex size-11 items-center justify-center rounded-full bg-black text-sm font-bold text-white ring-2 ring-zinc-200 dark:bg-white dark:text-black dark:ring-zinc-800">
                                    {{ auth()->user()->initials() }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $this->passport['profile']['name'] ?? auth()->user()->name }}
                            </div>
                            @if ($this->passport['profile']['handle'] ?? null)
                                <div class="truncate text-xs text-zinc-500">{{ '@'.$this->passport['profile']['handle'] }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-zinc-200 dark:bg-white/10">
                        @foreach ([
                            ['label' => 'Sources', 'value' => $this->passport['stats']['sources']],
                            ['label' => 'Evidence', 'value' => $this->passport['stats']['evidence']],
                            ['label' => 'Projects', 'value' => $this->passport['stats']['projects']],
                            ['label' => 'Journal', 'value' => $this->passport['stats']['journal']],
                        ] as $stat)
                            <div class="bg-zinc-50 px-1 py-2 text-center dark:bg-zinc-900">
                                <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">{{ $stat['value'] }}</div>
                                <div class="mt-0.5 text-[9px] uppercase tracking-wide text-zinc-500">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Level --}}
                    <div class="rounded-lg bg-zinc-100 p-2.5 dark:bg-white/5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $this->levelSnapshot['title'] }}</span>
                            <span class="tabular-nums text-zinc-500">Lv {{ $this->levelSnapshot['current'] }} · {{ number_format($this->xp) }} XP</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                            <div class="h-full rounded-full bg-accent transition-all duration-500" style="width: {{ $this->levelSnapshot['progress'] }}%"></div>
                        </div>
                    </div>

                    {{-- Skills --}}
                    @if ($this->passport['skills'] !== [])
                        <div>
                            <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Capabilities</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($this->passport['skills'] as $skill)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">
                                        <x-tech-logo :name="$skill" class="size-3.5 shrink-0" />
                                        {{ $skill }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Magnitude factors --}}
                    <div>
                        <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Engineering Magnitude</div>
                        @foreach ($this->passport['factors'] as $factor)
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="w-24 shrink-0 truncate text-[11px] text-zinc-500">{{ $factor['label'] }}</div>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                                    <div class="h-full rounded-full bg-zinc-900 transition-all duration-500 dark:bg-white" style="width: {{ ($factor['points'] / max(1, $factor['max'])) * 100 }}%"></div>
                                </div>
                                <div class="w-8 shrink-0 text-right text-[10px] tabular-nums text-zinc-500">{{ $factor['points'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- All scouted records --}}
                    <div class="grid gap-1.5">
                        @foreach ($this->passport['evidence'] as $item)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400" />
                                <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                <span class="shrink-0 text-emerald-500">queued</span>
                            </div>
                        @endforeach
                        @foreach ($this->passport['projects'] as $item)
                            <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                <flux:icon name="folder" variant="micro" class="shrink-0 text-accent" />
                                <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                <span class="shrink-0 text-emerald-500">published</span>
                            </div>
                        @endforeach
                        @foreach ($this->passport['journal'] as $item)
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
    @elseif ($phase === 'done')
        <div class="overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-white/10">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    <flux:icon name="check" variant="micro" />
                    Scout complete
                </span>
                @if ($summary)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-2.5 py-1 text-xs font-semibold tabular-nums text-accent">
                        <flux:icon name="sparkles" variant="micro" />
                        +{{ number_format($summary['xp']) }} XP
                    </span>
                @endif
            </div>

            <div class="grid gap-4 p-4">
                <div class="flex items-center gap-3">
                    @if ($this->passport['profile']['avatar'] ?? null)
                        <img src="{{ $this->passport['profile']['avatar'] }}" alt="" class="size-12 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-white/10" />
                    @else
                        <div class="flex size-12 items-center justify-center rounded-full bg-black text-sm font-bold text-white ring-2 ring-zinc-200 dark:bg-white dark:text-black dark:ring-zinc-800">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $this->passport['profile']['name'] ?? auth()->user()->name }}</div>
                        @if ($this->passport['profile']['handle'] ?? null)
                            <div class="truncate text-xs text-zinc-500">{{ '@'.$this->passport['profile']['handle'] }}</div>
                        @endif
                    </div>
                    @if ($this->passport['level'])
                        <div class="ms-auto text-end">
                            <div class="text-lg font-bold text-zinc-900 dark:text-white">{{ $this->passport['level']['title'] }}</div>
                            <div class="text-xs text-zinc-500">Lv {{ $this->passport['level']['current'] }}</div>
                        </div>
                    @endif
                </div>

                @if ($summary)
                    <div class="grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-zinc-200 dark:bg-white/10">
                        <div class="bg-zinc-50 px-1 py-2.5 text-center dark:bg-zinc-900">
                            <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($summary['sources']) }}</div>
                            <div class="mt-0.5 text-[9px] uppercase tracking-wide text-zinc-500">Sources</div>
                        </div>
                        <div class="bg-zinc-50 px-1 py-2.5 text-center dark:bg-zinc-900">
                            <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($summary['evidence']) }}</div>
                            <div class="mt-0.5 text-[9px] uppercase tracking-wide text-zinc-500">Evidence</div>
                        </div>
                        <div class="bg-zinc-50 px-1 py-2.5 text-center dark:bg-zinc-900">
                            <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($summary['projects']) }}</div>
                            <div class="mt-0.5 text-[9px] uppercase tracking-wide text-zinc-500">Projects</div>
                        </div>
                        <div class="bg-zinc-50 px-1 py-2.5 text-center dark:bg-zinc-900">
                            <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($summary['journal']) }}</div>
                            <div class="mt-0.5 text-[9px] uppercase tracking-wide text-zinc-500">Journal</div>
                        </div>
                    </div>
                @endif

                @if ($this->passport['magnitude'])
                    <div class="flex items-center gap-4 rounded-lg border border-accent/20 bg-accent/5 p-3">
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Engineering Magnitude</div>
                            <div class="mt-1 text-xl font-bold tabular-nums text-accent">{{ number_format($this->passport['magnitude']['total']) }}<span class="text-xs font-semibold text-zinc-500">/1000</span></div>
                        </div>
                        <a href="{{ route('devid', auth()->user()->handle()) }}" wire:navigate class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-full bg-zinc-900 px-3.5 text-xs font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            View DevID
                            <flux:icon name="arrow-right" variant="micro" />
                        </a>
                    </div>
                @endif

                {{-- Every record scouted --}}
                @if ($this->passport['evidence'] !== [])
                    <div>
                        <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Evidence: {{ count($this->passport['evidence']) }}</div>
                        <div class="grid max-h-56 gap-1 overflow-y-auto">
                            @foreach ($this->passport['evidence'] as $item)
                                <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                    <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400" />
                                    <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->passport['projects'] !== [])
                    <div>
                        <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Projects published: {{ count($this->passport['projects']) }}</div>
                        <div class="grid max-h-56 gap-1 overflow-y-auto">
                            @foreach ($this->passport['projects'] as $item)
                                <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                    <flux:icon name="folder" variant="micro" class="shrink-0 text-accent" />
                                    <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->passport['journal'] !== [])
                    <div>
                        <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Journal entries: {{ count($this->passport['journal']) }}</div>
                        <div class="grid max-h-56 gap-1 overflow-y-auto">
                            @foreach ($this->passport['journal'] as $item)
                                <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-2 py-1.5 text-xs dark:bg-zinc-900/70">
                                    <flux:icon name="book-open" variant="micro" class="shrink-0 text-amber-500" />
                                    <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-300">{{ $item }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                <p class="text-left text-xs text-zinc-500">Evidence queued for AI analysis. Your feed updates as it lands.</p>
                <button type="button" wire:click="restart" class="shrink-0 text-xs font-medium text-accent hover:underline">
                    Scout another
                </button>
            </div>
        </div>
    @endif
</div>
