<?php

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notifications')] class extends Component {
    public string $filter = 'all';
    public int $perPage = 25;

    public function setFilter(string $filter): void
    {
        $this->filter = $filter === 'unread' ? 'unread' : 'all';
        $this->perPage = 25;
        unset($this->notifications);
    }

    public function loadMore(): void
    {
        $this->perPage += 25;
        unset($this->notifications);
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()
            ->where('id', $id)
            ->get()
            ->each->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->each->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return auth()->user()->notifications()->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        $query = auth()->user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        }

        return $query->latest()->limit($this->perPage)->get();
    }

    #[Computed]
    public function hasMore(): bool
    {
        $count = auth()->user()->notifications()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->count();

        return $this->notifications->count() < $count;
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Notifications</flux:heading>
            <flux:text>Everything that's happened around your work.</flux:text>
        </div>
        @if ($this->unreadCount > 0)
            <flux:button variant="subtle" wire:click="markAllAsRead">
                <flux:icon name="check-badge" variant="micro" />
                Mark all as read
            </flux:button>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach (['all' => 'All', 'unread' => 'Unread'] as $value => $label)
                <button
                    type="button"
                    wire:click="setFilter('{{ $value }}')"
                    @class([
                        'rounded px-2.5 py-1 text-xs font-medium',
                        'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' => $this->filter === $value,
                        'text-zinc-500' => $this->filter !== $value,
                    ])
                >
                    {{ $label }}
                    @if ($value === 'all' && $this->totalCount > 0)
                        <span class="text-zinc-400">· {{ number_format($this->totalCount) }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-1">
        @forelse ($this->notifications as $notification)
            @php($url = $notification->data['url'] ?? null)
            <div
                wire:key="notification-{{ $notification->id }}"
                @class([
                    'flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition dark:border-zinc-700 dark:bg-zinc-800',
                    'border-accent/20 bg-accent/5 dark:border-accent/20 dark:bg-accent/5' => is_null($notification->read_at),
                ])
            >
                <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <flux:icon :name="$notification->data['icon'] ?? 'bell'" variant="mini" />
                </div>

                <div class="grid min-w-0 flex-1 gap-0.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ $notification->data['title'] ?? 'Notification' }}
                        </div>
                        <span class="shrink-0 text-xs text-zinc-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $notification->data['body'] ?? '' }}</div>
                    <div class="mt-1 flex items-center gap-3">
                        @if ($url)
                            <a
                                href="{{ $url }}"
                                wire:navigate
                                wire:click="markAsRead('{{ $notification->id }}')"
                                class="text-xs font-semibold text-accent hover:underline"
                            >
                                View details
                            </a>
                        @endif
                        @if (is_null($notification->read_at))
                            <button
                                type="button"
                                wire:click="markAsRead('{{ $notification->id }}')"
                                class="text-xs font-medium text-zinc-400 hover:text-zinc-600 hover:underline dark:hover:text-zinc-300"
                            >
                                Mark as read
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-600">
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                    <flux:icon name="bell-slash" class="text-zinc-400" />
                </div>
                <flux:heading>No notifications</flux:heading>
                <flux:text>Keep shipping. This is where your activity will land.</flux:text>
            </div>
        @endforelse
    </div>

    @if ($this->hasMore)
        <div class="flex justify-center">
            <flux:button variant="subtle" wire:click="loadMore" wire:loading.attr="disabled">
                <flux:icon name="arrow-down" variant="micro" />
                Load more
            </flux:button>
        </div>
    @endif
</div>
