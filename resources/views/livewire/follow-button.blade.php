<div>
    @auth
        @if (auth()->id() !== $userId)
            <button
                type="button"
                wire:click="toggle"
                wire:loading.attr="disabled"
                @click.stop
                class="inline-flex h-7 items-center gap-1 rounded-full px-3 text-xs font-semibold transition {{ $isFollowing ? 'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900' : 'border border-zinc-200 bg-white text-zinc-700 hover:border-zinc-900 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}"
                title="{{ $isFollowing ? 'Unfollow '.$target->name : 'Follow '.$target->name }}"
            >
                <flux:icon :name="$isFollowing ? 'check' : 'plus'" variant="micro" class="size-3" />
                {{ $isFollowing ? 'Following' : 'Follow' }}
            </button>
        @endif
    @else
        <a href="{{ route('login') }}" class="inline-flex h-7 items-center gap-1 rounded-full border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-700 hover:border-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
            <flux:icon name="plus" variant="micro" class="size-3" />
            Follow
        </a>
    @endauth
</div>
