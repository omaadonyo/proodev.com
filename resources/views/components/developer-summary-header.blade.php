@props(['user'])

<a
    href="{{ route('passport', $user->handle()) }}"
    wire:navigate
    class="group hidden items-center gap-3 rounded-full border border-zinc-200 bg-white py-1 pe-4 ps-1 shadow-sm transition-colors hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 md:flex"
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

    <div class="hidden h-8 w-px bg-zinc-200 dark:bg-zinc-700 lg:block"></div>

    <div class="flex items-center gap-3">
        <div class="hidden text-center sm:block">
            <div class="text-sm font-bold leading-tight text-zinc-900 dark:text-zinc-100">{{ $user->level() }}</div>
            <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-400">Lvl</div>
        </div>

        <div class="flex items-center gap-1 text-center">
            <flux:icon name="fire" variant="mini" class="size-4 text-accent" />
            <div>
                <div class="text-sm font-bold leading-tight text-zinc-900 dark:text-zinc-100">{{ $user->streak_count }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-400">Streak</div>
            </div>
        </div>
    </div>
</a>
