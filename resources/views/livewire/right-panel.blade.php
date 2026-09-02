<aside class="sticky top-24 hidden min-w-0 w-72 shrink-0 self-start xl:block" wire:poll.15s>
    <div class="grid min-w-0 gap-3">

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
                            @if ($engineer->isVerified())
                                <div class="flex shrink-0 items-center gap-1" @click.stop>
                                    @auth
                                        @if (auth()->id() !== $engineer->id)
                                            @php $alreadyChatting = $this->chatPeerIds->contains($engineer->id); @endphp
                                            <button
                                                type="button"
                                                wire:click="connect({{ $engineer->id }})"
                                                wire:loading.attr="disabled"
                                                @click.stop
                                                title="{{ $alreadyChatting ? 'You already chat with '.$engineer->name : 'Chat with verified '.$engineer->name }}"
                                                class="relative inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white transition hover:bg-emerald-700 ring-1 ring-emerald-500/20"
                                            >
                                                <flux:icon
                                                    name="chat-bubble-oval-left-ellipsis"
                                                    :variant="$alreadyChatting ? 'solid' : 'micro'"
                                                    class="size-3.5 text-white"
                                                />
                                                @if (!$alreadyChatting)
                                                    <span class="absolute -top-0.5 -right-0.5 flex size-3 items-center justify-center rounded-full bg-white text-emerald-600 ring-1 ring-emerald-500/20"><flux:icon name="check" variant="micro" class="size-2" /></span>
                                                @endif
                                                @if ($alreadyChatting)
                                                    <span class="absolute -top-0.5 -right-0.5 size-2 rounded-full border border-white bg-emerald-500 dark:border-zinc-900" title="Existing conversation"></span>
                                                @endif
                                            </button>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" @click.stop class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-white hover:bg-zinc-700">
                                            <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" class="size-3.5" />
                                        </a>
                                    @endauth
                                </div>
                            @endif
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
                <flux:heading size="sm">Advertisement</flux:heading>
                <flux:icon name="megaphone" variant="mini" class="size-4 text-amber-500" />
            </div>
            <div class="p-2">
                @forelse ($this->ads as $ad)
                    <a
                        href="{{ $ad->target_url ?: '#' }}"
                        target="_blank"
                        rel="noopener noreferrer sponsored"
                        class="group block overflow-hidden rounded-lg border border-zinc-200 transition hover:border-accent/40 dark:border-zinc-700"
                    >
                        @if ($ad->image_url)
                            <img src="{{ $ad->image_url }}" alt="{{ $ad->title }}" class="w-full object-contain" style="max-height:140px" loading="lazy" />
                            <div class="px-3 py-2 text-xs font-semibold text-zinc-700 group-hover:text-accent dark:text-zinc-200">{{ $ad->title }}</div>
                        @else
                            <div class="flex min-h-20 flex-col items-center justify-center gap-1 px-3 py-4 text-center">
                                <div class="text-sm font-semibold text-zinc-900 group-hover:text-accent dark:text-white">{{ $ad->title }}</div>
                                @if ($ad->target_url)
                                    <span class="text-[11px] uppercase tracking-wider text-zinc-400">Sponsored</span>
                                @endif
                            </div>
                        @endif
                    </a>
                @empty
                    <div class="relative flex min-h-24 flex-col items-center justify-center gap-2 overflow-hidden rounded-lg border border-dashed border-zinc-300 bg-gradient-to-br from-violet-50 via-white to-cyan-50 px-4 py-5 text-center dark:border-zinc-600 dark:from-zinc-900 dark:via-zinc-800 dark:to-zinc-900">
                        <div class="flex size-9 items-center justify-center rounded-full bg-accent/10 text-accent">
                            <flux:icon name="sparkles" variant="mini" class="size-4" />
                        </div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">Your ad here</div>
                        <p class="max-w-[16rem] text-xs text-zinc-500 dark:text-zinc-400">
                            Get your product in front of verified engineers.
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

        <div class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-zinc-700">
                <flux:heading size="sm">Our Sponsors</flux:heading>
                <flux:icon name="hand-raised" variant="mini" class="size-4 text-amber-500" />
            </div>
            <div class="grid grid-cols-5 gap-2 p-2">
                @forelse ($this->sponsors as $sponsor)
                    <a
                        href="{{ $sponsor->website_url ?: '#' }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="{{ $sponsor->name }}"
                        class="group flex flex-col items-center gap-1 rounded-lg p-1.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-900"
                    >
                        @if ($sponsor->logo_url)
                            <img src="{{ $sponsor->logo_url }}" alt="{{ $sponsor->name }}" class="size-9 rounded-md object-contain bg-white p-0.5 shadow-sm ring-1 ring-zinc-200 dark:bg-white" loading="lazy" />
                        @else
                            <div class="flex size-9 items-center justify-center rounded-md bg-accent/10 text-[10px] font-bold text-accent ring-1 ring-zinc-200 dark:ring-zinc-700">
                                {{ \Illuminate\Support\Str::initials($sponsor->name) }}
                            </div>
                        @endif
                        <span class="w-full truncate text-center text-[9px] font-medium leading-tight text-zinc-600 group-hover:text-accent dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($sponsor->name, 10) }}</span>
                    </a>
                @empty
                    <p class="col-span-5 px-3 py-4 text-center text-xs text-zinc-500">No sponsors yet.</p>
                @endforelse
            </div>
            <button
                type="button"
                @click="$flux.modal('become-sponsor').show()"
                class="flex w-full items-center justify-center gap-1.5 border-t border-zinc-100 px-3 py-2 text-xs font-semibold text-accent transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900"
            >
                <flux:icon name="sparkles" variant="micro" class="size-3.5" />
                Become a sponsor
            </button>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs font-medium text-zinc-600 dark:text-zinc-300">© {{ date('Y') }} ProoDev. All rights reserved.</div>
            @php
                $socialLinks = collect([
                    ['key' => 'social.x', 'label' => 'X', 'icon' => 'x'],
                    ['key' => 'social.bsky', 'label' => 'Bluesky', 'icon' => 'bsky'],
                    ['key' => 'social.youtube', 'label' => 'YouTube', 'icon' => 'youtube'],
                    ['key' => 'social.tiktok', 'label' => 'TikTok', 'icon' => 'tiktok'],
                    ['key' => 'social.pinkary', 'label' => 'Pinkary', 'icon' => 'pinkary'],
                ])->map(function ($item) {
                    $url = app(\App\Services\SiteSettings::class)->get($item['key']);
                    // fallback: X also checks legacy twitter key
                    if ($item['key'] === 'social.x' && ! filled($url)) {
                        $url = app(\App\Services\SiteSettings::class)->get('social.twitter');
                    }
                    return array_merge($item, ['url' => $url]);
                })->filter(fn ($item) => filled($item['url']))->values();
            @endphp
            @if ($socialLinks->isNotEmpty())
                <div class="mt-3 flex items-center justify-center gap-2">
                    @foreach ($socialLinks as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" title="{{ $social['label'] }}" class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition hover:bg-zinc-900 hover:text-white dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-white dark:hover:text-zinc-900">
                            @if ($social['icon'] === 'x')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            @elseif ($social['icon'] === 'bsky')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5" aria-hidden="true"><path d="M5.202 2.857C7.954 4.922 10.913 9.11 12 11.358c1.087-2.247 4.046-6.436 6.798-8.501C20.783 1.366 24 .213 24 3.883c0 .732-.42 6.156-.667 7.037-.856 3.061-3.978 3.842-6.755 3.37 4.854.826 6.089 3.562 3.422 6.299-5.065 5.196-7.28-1.304-7.847-2.97-.104-.305-.152-.448-.153-.327 0-.121-.05.022-.153.327-.568 1.666-2.782 8.166-7.847 2.97-2.667-2.737-1.432-5.473 3.422-6.3-2.777.473-5.899-.308-6.755-3.369C.42 10.04 0 4.615 0 3.883c0-3.67 3.217-2.517 5.202-1.026m-6.3-3.2c1.2.9 2.8 2.2 2.8 4.2 0 1.1-.9 2-2 2-1.1 0-2-.9-2-2 0-1.4 1.1-2.9 2.3-4.1l-1.1-.1Zm12.6 0-1.1.1c1.1 1.2 2.3 2.7 2.3 4.1 0 1.1-.9 2-2 2-1.1 0-2-.9-2-2 0-2 1.6-3.3 2.8-4.2ZM12 7.2c-.4-.9-1.1-1.6-2.1-1.6-1.1 0-1.9.9-1.9 1.9 0 1 .7 1.9 1.6 2.7l2.4 1.8 2.4-1.8c.9-.8 1.6-1.7 1.6-2.7 0-1-.8-1.9-1.9-1.9-1 0-1.7.7-2.1 1.6Z"/></svg>
                            @elseif ($social['icon'] === 'youtube')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                            @elseif ($social['icon'] === 'tiktok')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.11V8.94a6.27 6.27 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.2 8.2 0 0 0 4.76 1.52V6.84a4.83 4.83 0 0 1-3.77-4.25z"/></svg>
                            @elseif ($social['icon'] === 'pinkary')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            @else
                                <flux:icon name="globe" variant="micro" class="size-3.5" />
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
            <div class="mt-2 text-[11px] text-zinc-400">Proof over claims. Every engineer backed by evidence.</div>
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
                    href="mailto:sales@proodev.com?subject={{ rawurlencode('Sponsorship enquiry, ProoDev') }}"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-accent px-4 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    <flux:icon name="envelope" variant="micro" class="size-4" />
                    sales@proodev.com
                </a>
            </div>
        </flux:modal>



    </div>
</aside>
