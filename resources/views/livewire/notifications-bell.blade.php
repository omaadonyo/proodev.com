<div
    wire:key="notifications-bell"
    class="relative"
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
        title="Notifications"
        class="relative flex size-8 items-center justify-center rounded-full border border-zinc-200 bg-white/70 text-zinc-500 shadow-sm backdrop-blur transition-colors duration-200 hover:bg-white hover:text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
    >
        <flux:icon name="bell" variant="mini" />
        @if ($this->unreadCount > 0)
            <span class="absolute -end-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-semibold text-white">
                {{ min(99, $this->unreadCount) }}
            </span>
        @endif
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
        class="absolute end-0 top-full z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl shadow-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 dark:border-white/10">
            <span class="text-sm font-semibold text-zinc-900 dark:text-white">Notifications</span>
            <div class="flex items-center gap-3">
                @if ($this->recent->isNotEmpty())
                    <button
                        type="button"
                        wire:click="clearAll"
                        class="text-xs font-medium text-zinc-500 transition hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-white"
                    >
                        Clear all
                    </button>
                @endif
                @if ($this->unreadCount > 0)
                    <button
                        type="button"
                        wire:click="markAllAsRead"
                        class="text-xs font-medium text-accent hover:underline"
                    >
                        Mark all read
                    </button>
                @endif
            </div>
        </div>

        <div class="grid max-h-96 overflow-y-auto">
            @forelse ($this->recent as $notification)
                <a
                    href="{{ route('notifications') }}"
                    wire:navigate
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="flex items-start gap-3 px-4 py-3 transition hover:bg-zinc-50 dark:hover:bg-zinc-950/50"
                >
                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                        <flux:icon :name="$notification->data['icon'] ?? 'bell'" variant="mini" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $notification->data['title'] ?? 'Notification' }}
                        </span>
                        <span class="block truncate text-xs text-zinc-500">{{ $notification->data['body'] ?? '' }}</span>
                    </span>
                    <span class="flex shrink-0 flex-col items-end gap-1">
                        @if (is_null($notification->read_at))
                            <span class="size-2 rounded-full bg-accent"></span>
                        @endif
                        <span class="text-[10px] text-zinc-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <flux:icon name="bell-slash" class="mx-auto size-6 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-2 text-sm text-zinc-500">No notifications yet.</p>
                    <p class="mt-1 text-xs text-zinc-400">Your activity will land here.</p>
                </div>
            @endforelse
        </div>

        <a
            href="{{ route('notifications') }}"
            wire:navigate
            class="flex items-center justify-center gap-1 border-t border-zinc-100 px-4 py-2.5 text-xs font-semibold text-accent transition hover:bg-zinc-50 hover:underline dark:border-white/10 dark:hover:bg-zinc-950/50"
        >
            View all notifications
            <flux:icon name="arrow-right" variant="micro" />
        </a>
    </div>
</div>
