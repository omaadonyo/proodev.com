<div wire:poll.60s class="overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5">
    <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-4 py-3 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <span class="flex size-7 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <flux:icon name="fire" variant="mini" class="size-4" />
            </span>
            <div>
                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Chat Streak</div>
                <div class="text-xs text-zinc-500">1 free chat · 5 min · Verify for unlimited</div>
            </div>
        </div>
        @if (auth()->user()?->isVerified())
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white">
                <flux:icon name="check-badge" variant="micro" class="size-3" />
                Verified
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-900 px-2.5 py-1 text-xs font-bold text-white dark:bg-white dark:text-zinc-900">
                <flux:icon name="bolt" variant="micro" class="size-3 text-amber-400" />
                {{ $this->snapshot['streak'] ?: 0 }} streak
            </span>
        @endif
    </div>

    <div class="p-4">
        @if (auth()->user()?->isVerified())
            <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/30">
                <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                    <flux:icon name="check-circle" variant="mini" class="size-4" />
                    Unlimited chat unlocked
                </div>
                <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Your verified badge gives you unlimited connections. Streaks no longer limit you. Start any chat from Top Engineers.</p>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs">
                <span class="text-zinc-500">Daily streak: <span class="font-semibold text-zinc-900 dark:text-white">{{ auth()->user()->streak_count }} days</span></span>
                <a href="{{ route('verify') }}" wire:navigate class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">Manage verification</a>
            </div>
        @else
            @if ($this->snapshot['streak'] == 0)
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                        <flux:icon name="fire" variant="mini" class="size-4 text-zinc-400" />
                        0 streak, earn after 2 hours
                    </div>
                    <p class="mt-1 text-xs text-zinc-500">Stay active on ProoDev for 2 hours to earn your first chat streak. Then connect with one verified engineer.</p>
                </div>
                <div class="mt-3">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Progress to 1 streak</span>
                        <span class="tabular-nums font-medium">{{ $this->snapshot['progress'] }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <div class="h-full rounded-full bg-gradient-to-r from-zinc-400 to-zinc-600 transition-all duration-500" style="width: {{ $this->snapshot['progress'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $this->snapshot['minutes_until_next'] }}m left</span> until your streak is ready. Keep browsing, it auto-earns.
                    </p>
                </div>
            @elseif ($this->snapshot['can_chat'])
                <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/20">
                    <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                        <flux:icon name="sparkles" variant="mini" class="size-4" />
                        Streak ready, 1 chat available!
                    </div>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Your 2-hour streak is complete. Connect with one verified engineer now.</p>
                </div>
                <div class="mt-3">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Chat streak</span>
                        <span class="tabular-nums font-medium text-emerald-600">Ready ?</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                    </div>
                    <p class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">Ready to chat. Streak will be consumed after first message.</p>
                </div>
            @elseif ($this->snapshot['has_active'])
                @php $expires = $this->snapshot['expires_at']; $minsLeft = $this->snapshot['minutes_until_next']; @endphp
                <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/20">
                    <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                        <flux:icon name="clock" variant="mini" class="size-4" />
                        Chat active, {{ $minsLeft }}m left
                    </div>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">
                        Your free chat is live
                        @if ($expires)
                            until {{ $expires->diffForHumans() }}
                        @endif.
                        After that, verify to continue.
                    </p>
                </div>
                <div class="mt-3">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Streak expires in</span>
                        <span class="tabular-nums font-medium">{{ $minsLeft }}m</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ 100 - $this->snapshot['progress'] }}%"></div>
                    </div>
                </div>
            @else
                @php $expires = $this->snapshot['expires_at']; @endphp
                <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-950/20">
                    <div class="flex items-center gap-2 text-sm font-semibold text-amber-800 dark:text-amber-300">
                        <flux:icon name="clock" variant="mini" class="size-4" />
                        Streak expired
                    </div>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                        Your 1 free chat streak was used
                        @if ($expires)
                            and expired {{ $expires->diffForHumans() }}
                        @endif.
                        Verify to unlock unlimited chat.
                    </p>
                </div>
                <div class="mt-3">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Streak status</span>
                        <span class="tabular-nums font-medium text-amber-600">Expired</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <div class="h-full rounded-full bg-amber-500" style="width: 100%"></div>
                    </div>
                </div>
            @endif
            <a href="{{ route('verify') }}" wire:navigate class="mt-4 flex w-full items-center justify-center gap-1.5 rounded-full bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <flux:icon name="shield-check" variant="micro" class="size-4" />
                Get verified, unlock full chat
            </a>
            <p class="mt-2 text-center text-[11px] text-zinc-400">Streaks don't add XP, they gate chat. Earn 1 streak per 2 hours, 1 chat per streak. Verify once, chat forever.</p>
        @endif
    </div>
</div>
