@props(['user', 'compact' => false])

@if($compact)
    <div
        class="group hidden items-center gap-2 rounded-full border border-zinc-200 bg-white py-0.5 pe-2 ps-0.5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 md:flex"
        x-data="{ open: false }"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
    >
        <a
            href="{{ route('devid', $user->handle()) }}"
            wire:navigate
            class="flex items-center gap-1.5 rounded-full ps-1 transition-colors hover:opacity-90"
        >
            <div class="relative">
                <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-7 ring-2 ring-white dark:ring-zinc-800" />
                @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $user->isOnline())
                    <span class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
                @endif
            </div>
        </a>

        <div class="relative">
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open"
                aria-haspopup="true"
                title="2-Hour Streak"
                class="flex items-center gap-1 rounded-full px-1.5 py-1 text-center transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
            >
                <flux:icon name="fire" variant="mini" class="size-3.5 text-amber-500" />
                <span class="text-xs font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $user->isVerified() ? $user->streak_count : $user->two_hour_streak_count }}</span>
                <flux:icon name="chevron-down" variant="micro" class="size-3 text-zinc-400 transition-transform" ::class="open ? 'rotate-180' : ''" />
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute end-0 top-full z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl bg-white shadow-xl shadow-zinc-900/10 dark:bg-zinc-900"
                style="overflow-x: hidden;"
            >
                <livewire:two-hour-streak-widget :key="'streak-dropdown-compact-'.$user->id" />
            </div>
        </div>
    </div>
@else
    <div
        class="group hidden items-center gap-3 rounded-full border border-zinc-200 bg-white py-1 pe-1 ps-1 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 md:flex"
        x-data="{ open: false }"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
    >
        <a
            href="{{ route('devid', $user->handle()) }}"
            wire:navigate
            class="flex items-center gap-3 rounded-full ps-1 transition-colors hover:opacity-90"
        >
            <div class="relative">
                <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="ring-2 ring-white dark:ring-zinc-800" />
                @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $user->isOnline())
                    <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
                @endif
            </div>

            <div class="hidden min-w-0 lg:block">
                <div class="flex items-center gap-1.5">
                    <div class="truncate text-sm font-semibold leading-tight text-zinc-900 group-hover:text-zinc-700 dark:text-zinc-100 dark:group-hover:text-zinc-300">
                        {{ $user->name }}
                    </div>
                    <x-verified-badge :user="$user" compact />
                </div>
                <div class="truncate text-xs leading-tight text-zinc-400">{{ '@'.$user->handle() }}</div>
            </div>
        </a>

        <div class="hidden h-8 w-px bg-zinc-200 dark:bg-zinc-700 lg:block"></div>

        <div class="flex items-center gap-3 pe-3">
            <a href="{{ route('devid', $user->handle()) }}" wire:navigate class="hidden text-center sm:block hover:opacity-80 transition-opacity">
                <div class="text-sm font-bold leading-tight text-zinc-900 dark:text-zinc-100">{{ $user->level() }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-400">Lvl</div>
            </a>

            <div class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open"
                    aria-haspopup="true"
                    title="2-Hour Streak, click to view progress"
                    class="flex items-center gap-1 rounded-full px-2 py-1 text-center transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
                >
                    <flux:icon name="fire" variant="mini" class="size-4 text-amber-500" />
                    <div>
                        <div class="text-sm font-bold leading-tight text-zinc-900 dark:text-zinc-100">{{ $user->isVerified() ? $user->streak_count : $user->two_hour_streak_count }}</div>
                        <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-400">{{ $user->isVerified() ? "Streak" : "2H Streak" }}</div>
                    </div>
                    <flux:icon name="chevron-down" variant="micro" class="size-3 text-zinc-400 transition-transform" ::class="open ? 'rotate-180' : ''" />
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute end-0 top-full z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl bg-white shadow-xl shadow-zinc-900/10 dark:bg-zinc-900"
                    style="overflow-x: hidden;"
                >
                    <livewire:two-hour-streak-widget :key="'streak-dropdown-'.$user->id" />
                </div>
            </div>
        </div>
    </div>
@endif
