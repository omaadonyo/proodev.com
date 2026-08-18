<aside class="sticky top-16 hidden min-w-0 w-72 shrink-0 self-start xl:block" wire:poll.15s>
    <div class="grid min-w-0 gap-3">
        <div class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-zinc-700">
                <flux:heading size="sm">Advertisement</flux:heading>
                <flux:icon name="megaphone" variant="mini" class="size-4 text-amber-500" />
            </div>
            <div class="grid gap-2 p-3">
                @forelse ($this->ads as $ad)
                    <a
                        href="{{ $ad->target_url ?: '#' }}"
                        target="_blank"
                        rel="noopener noreferrer sponsored"
                        class="group overflow-hidden rounded-lg border border-zinc-200 transition hover:border-accent/40 dark:border-zinc-700"
                    >
                        @if ($ad->image_url)
                            <img src="{{ $ad->image_url }}" alt="{{ $ad->title }}" class="aspect-[4/3] w-full object-cover" loading="lazy" />
                            <div class="px-3 py-2 text-xs font-semibold text-zinc-700 group-hover:text-accent dark:text-zinc-200">{{ $ad->title }}</div>
                        @else
                            <div class="flex min-h-24 flex-col items-center justify-center gap-1 px-3 py-4 text-center">
                                <div class="text-sm font-semibold text-zinc-900 group-hover:text-accent dark:text-white">{{ $ad->title }}</div>
                                @if ($ad->target_url)
                                    <span class="text-[11px] uppercase tracking-wider text-zinc-400">Sponsored</span>
                                @endif
                            </div>
                        @endif
                    </a>
                @empty
                    <div class="relative flex min-h-32 flex-col items-center justify-center gap-2 overflow-hidden rounded-lg border border-dashed border-zinc-300 bg-gradient-to-br from-violet-50 via-white to-cyan-50 px-4 py-6 text-center dark:border-zinc-600 dark:from-zinc-900 dark:via-zinc-800 dark:to-zinc-900">
                        <div class="flex size-10 items-center justify-center rounded-full bg-accent/10 text-accent">
                            <flux:icon name="sparkles" variant="mini" class="size-5" />
                        </div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">Your ad here</div>
                        <p class="max-w-[16rem] text-xs text-zinc-500 dark:text-zinc-400">
                            Get your product in front of verified engineers building in public.
                        </p>
                        <button
                            type="button"
                            @click="$flux.modal('become-sponsor').show()"
                            class="mt-1 inline-flex h-8 items-center gap-1.5 rounded-full bg-zinc-900 px-4 text-xs font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            Advertise with ProoDev
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="ticker-group min-w-0 cursor-pointer overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800" @click="$flux.modal('top-engineers').show()" role="button" tabindex="0" aria-label="Open the top 100 engineers leaderboard">
            <div class="flex items-center justify-between gap-2 px-4 pt-4">
                <div class="flex items-center gap-2">
                    <flux:heading size="sm">Top Engineers</flux:heading>
                    <flux:icon name="trophy" variant="mini" class="size-4 text-amber-500" />
                </div>
                <span class="text-xs font-semibold text-accent">View all</span>
            </div>

            <div class="ticker-fade mt-2 h-72 overflow-hidden px-2">
                <div class="ticker-track grid gap-1">
                    @foreach ([0, 1] as $copy)
                        <div class="grid gap-1" aria-hidden="{{ $copy === 1 ? 'true' : 'false' }}">
                            @foreach ($this->topEngineers as $index => $engineer)
                                <div class="flex items-center gap-2.5 rounded-lg p-1.5">
                                    <span @class([
                                        'flex size-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold tabular-nums',
                                        'bg-amber-400/20 text-amber-600 dark:text-amber-400' => $index === 0,
                                        'bg-zinc-200/70 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300' => $index !== 0,
                                    ])>
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="relative shrink-0">
                                        <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle class="size-8" />
                                        @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $engineer->isOnline())
                                            <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <div class="truncate text-sm font-medium">{{ $engineer->name }}</div>
                                            <x-verified-badge :user="$engineer" compact />
                                        </div>
                                        <div class="truncate text-xs text-zinc-500">{{ $engineer->levelTitle() }} · Lv {{ $engineer->level() }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ number_format($engineer->reputation_score) }}</div>
                                        <div class="text-[10px] uppercase tracking-wider text-zinc-400">rep</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 border-t border-zinc-100 px-4 py-2.5 dark:border-zinc-700">
                <span class="text-xs text-zinc-400">Scrolls to reveal more</span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-accent">
                    Top 100
                    <flux:icon name="arrow-right" variant="micro" class="size-3.5" />
                </span>
            </div>
        </div>

        <flux:modal name="top-engineers" class="w-full max-w-lg">
            <div class="flex items-center gap-2.5">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-400/15 text-amber-500">
                    <flux:icon name="trophy" />
                </div>
                <div>
                    <flux:heading size="lg">Top 100 engineers</flux:heading>
                    <flux:text>Ranked by reputation across the community.</flux:text>
                </div>
            </div>

            <div class="mt-3 max-h-[26rem] overflow-y-auto">
                <div class="grid gap-0.5">
                    @forelse ($this->topHundred as $index => $engineer)
                        <div
                            role="button"
                            tabindex="0"
                            @click="$flux.modal('leaderboard-{{ $engineer->id }}').show()"
                            @keydown.enter="$flux.modal('leaderboard-{{ $engineer->id }}').show()"
                            @keydown.space.prevent="$flux.modal('leaderboard-{{ $engineer->id }}').show()"
                            class="flex cursor-pointer items-center gap-2 rounded-lg px-1.5 py-1 transition hover:bg-zinc-50 dark:hover:bg-zinc-900"
                        >
                            <span @class([
                                'flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold tabular-nums',
                                'bg-amber-400/20 text-amber-600 dark:text-amber-400' => $index < 3,
                                'bg-zinc-200/70 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300' => $index >= 3,
                            ])>
                                {{ $index + 1 }}
                            </span>
                            <div class="relative shrink-0">
                                <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle class="size-8" />
                                @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $engineer->isOnline())
                                    <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <div class="truncate text-sm font-medium">{{ $engineer->name }}</div>
                                    <x-verified-badge :user="$engineer" compact />
                                </div>
                                <div class="truncate text-xs text-zinc-500">{{ $engineer->levelTitle() }} · Lv {{ $engineer->level() }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ number_format($engineer->reputation_score) }}</div>
                                <div class="text-[10px] uppercase tracking-wider text-zinc-400">rep</div>
                            </div>
                            @auth
                                @if (auth()->user()->isVerified() && auth()->id() !== $engineer->id)
                                    @php $alreadyChatting = $this->chatPeerIds->contains($engineer->id); @endphp
                                    <button
                                        type="button"
                                        wire:click="connect({{ $engineer->id }})"
                                        wire:loading.attr="disabled"
                                        @click.stop
                                        title="{{ $alreadyChatting ? 'You already chat with '.$engineer->name : 'Send a message' }}"
                                        class="relative inline-flex size-7 shrink-0 items-center justify-center rounded-md bg-zinc-900 text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                                    >
                                        <flux:icon
                                            name="chat-bubble-oval-left-ellipsis"
                                            :variant="$alreadyChatting ? 'solid' : 'micro'"
                                            class="size-3.5 {{ $alreadyChatting ? 'text-emerald-500' : '' }}"
                                        />
                                        @if ($alreadyChatting)
                                            <span class="absolute -top-0.5 -right-0.5 size-2 rounded-full border border-white bg-emerald-500 dark:border-zinc-900" title="Existing conversation"></span>
                                        @endif
                                    </button>
                                @endif
                            @endauth
                        </div>
                        <x-passport-flyout :user="$engineer" :name="'leaderboard-'.$engineer->id" :modal-only="true" />
                    @empty
                        <p class="px-3 py-6 text-center text-sm text-zinc-500">No ranked engineers yet.</p>
                    @endforelse
                </div>
            </div>
        </flux:modal>

        <div class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-zinc-700">
                <flux:heading size="sm">Our Sponsors</flux:heading>
                <flux:icon name="hand-raised" variant="mini" class="size-4 text-amber-500" />
            </div>
            <div class="grid gap-0.5 p-1.5">
                @forelse ($this->sponsors as $sponsor)
                    <a
                        href="{{ $sponsor->website_url ?: '#' }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-2.5 rounded-lg px-1.5 py-1.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-900"
                    >
                        <div class="relative shrink-0">
                            @if ($sponsor->logo_url)
                                <img src="{{ $sponsor->logo_url }}" alt="{{ $sponsor->name }}" class="size-8 rounded-lg object-cover" loading="lazy" />
                            @else
                                <div class="flex size-8 items-center justify-center rounded-lg bg-accent/10 text-xs font-bold text-accent">
                                    {{ \Illuminate\Support\Str::initials($sponsor->name) }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium">{{ $sponsor->name }}</div>
                            @if ($sponsor->tagline)
                                <div class="truncate text-xs text-zinc-500">{{ $sponsor->tagline }}</div>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="px-3 py-4 text-sm text-zinc-500">No sponsors yet.</p>
                @endforelse
            </div>
            <button
                type="button"
                @click="$flux.modal('become-sponsor').show()"
                class="flex w-full items-center justify-center gap-1.5 border-t border-zinc-100 px-3 py-2.5 text-xs font-semibold text-accent transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900"
            >
                <flux:icon name="sparkles" variant="micro" class="size-3.5" />
                Become a sponsor
            </button>
        </div>

        <flux:modal variant="flyout" name="become-sponsor" class="w-full max-w-md">
            <div class="grid gap-4">
                <div>
                    <flux:heading size="lg">Become a sponsor</flux:heading>
                    <flux:text>Get your brand in front of a growing engineering community.</flux:text>
                </div>

                <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-600">
                    <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-full bg-amber-400/10 text-amber-500">
                        <flux:icon name="hand-raised" />
                    </div>
                    <div class="text-sm font-medium">Support ProoDev</div>
                    <p class="mt-1 text-xs text-zinc-500">
                        Sponsorships keep the community free. Reach out to become one.
                    </p>
                </div>

                <a
                    href="mailto:sales@proodev.com?subject={{ rawurlencode('Sponsorship enquiry — ProoDev') }}"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-accent px-4 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    <flux:icon name="envelope" variant="micro" class="size-4" />
                    sales@proodev.com
                </a>
            </div>
        </flux:modal>

        @if (\App\Support\FeatureFlags::publicPresenceEnabled())
            <div class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Online now</flux:heading>
                <div class="mt-3 grid gap-2">
                    @forelse ($this->onlineUsers as $online)
                        <div
                            role="button"
                            tabindex="0"
                            @click="$flux.modal('online-{{ $online->id }}').show()"
                            @keydown.enter="$flux.modal('online-{{ $online->id }}').show()"
                            @keydown.space.prevent="$flux.modal('online-{{ $online->id }}').show()"
                            class="flex cursor-pointer items-center gap-2 rounded-lg p-1.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-900"
                        >
                            <div class="relative shrink-0">
                                <flux:avatar :src="$online->avatarUrl()" :alt="$online->name" circle class="size-7" />
                                <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <div class="truncate text-sm font-medium">{{ $online->name }}</div>
                                    <x-verified-badge :user="$online" compact />
                                </div>
                                <div class="text-xs text-zinc-500">{{ $online->levelTitle() }}</div>
                            </div>
                            @auth
                                @if (auth()->user()->isVerified() && auth()->id() !== $online->id)
                                    <button
                                        type="button"
                                        wire:click="connect({{ $online->id }})"
                                        wire:loading.attr="disabled"
                                        @click.stop
                                        title="Send a message"
                                        class="inline-flex size-7 shrink-0 items-center justify-center rounded-md bg-zinc-900 text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                                    >
                                        <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" class="size-3.5" />
                                    </button>
                                @endif
                            @endauth
                        </div>
                        <x-passport-flyout :user="$online" :name="'online-'.$online->id" :modal-only="true" />
                    @empty
                        <span class="text-sm text-zinc-500">No one is online right now.</span>
                    @endforelse
                </div>
            </div>
        @endif

    </div>
</aside>
