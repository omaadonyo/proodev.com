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
                    ['key' => 'social.github', 'label' => 'GitHub', 'icon' => 'github'],
                    ['key' => 'social.twitter', 'label' => 'X', 'icon' => 'twitter'],
                    ['key' => 'social.linkedin', 'label' => 'LinkedIn', 'icon' => 'linkedin'],
                    ['key' => 'social.youtube', 'label' => 'YouTube', 'icon' => 'youtube'],
                    ['key' => 'social.discord', 'label' => 'Discord', 'icon' => 'discord'],
                ])->map(fn ($item) => array_merge($item, ['url' => app(\App\Services\SiteSettings::class)->get($item['key'])]))->filter(fn ($item) => filled($item['url']))->values();
            @endphp
            @if ($socialLinks->isNotEmpty())
                <div class="mt-3 flex items-center justify-center gap-2">
                    @foreach ($socialLinks as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" title="{{ $social['label'] }}" class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition hover:bg-zinc-900 hover:text-white dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-white dark:hover:text-zinc-900">
                            @if ($social['icon'] === 'github')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                            @elseif ($social['icon'] === 'twitter')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            @elseif ($social['icon'] === 'linkedin')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg>
                            @elseif ($social['icon'] === 'youtube')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                            @elseif ($social['icon'] === 'discord')
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3847-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0281c2.0828 1.5268 4.0948 2.4592 6.0665 3.086a.0776.0776 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6264 3.9832-1.5596 6.0662-3.086a.077.077 0 00.0315-.0281c.0548-5.197-.4216-9.6846-1.6399-13.6604a.061.061 0 00-.032-.0277zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z"/></svg>
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
