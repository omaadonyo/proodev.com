<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Verified Developers')] class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function developers()
    {
        return User::query()
            ->where('is_verified', true)
            ->where('public_passport', true)
            ->when($this->search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('username', 'like', "%{$this->search}%")
                ->orWhere('headline', 'like', "%{$this->search}%")))
            ->orderByDesc('reputation_score')
            ->paginate(24);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="grid gap-1">
                <flux:heading size="xl">Verified Developers</flux:heading>
                <flux:text>
                    Engineers who completed identity verification — real people behind real work. Every profile below
                    carries a verified badge and a public DevID backed by evidence.
                </flux:text>
            </div>
            <div class="w-full sm:w-72">
                <flux:input icon="magnifying-glass" type="search" placeholder="Search verified developers..." wire:model.live.debounce.300ms="search" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->developers as $developer)
                <a href="{{ route('devid', $developer->handle()) }}" wire:navigate class="group flex flex-col items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-5 text-center transition hover:-translate-y-0.5 hover:border-accent/50 hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-accent/40">
                    <div class="relative">
                        <flux:avatar :src="$developer->avatarUrl()" :alt="$developer->name" circle class="size-16 ring-2 ring-white dark:ring-zinc-800" />
                        <span class="absolute -bottom-0.5 -end-0.5 flex size-5 items-center justify-center rounded-full bg-white dark:bg-zinc-900">
                            <flux:icon name="check-badge" variant="solid" class="size-4 text-emerald-500" />
                        </span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="truncate text-sm font-semibold text-zinc-900 group-hover:text-accent dark:text-white">{{ $developer->name }}</span>
                            <x-verified-badge :user="$developer" compact />
                        </div>
                        <div class="truncate text-xs text-zinc-500">{{ '@'.$developer->handle() }}</div>
                        @if ($developer->headline)
                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-zinc-500">{{ $developer->headline }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 text-[11px] text-zinc-400">
                        <span title="Reputation score">{{ number_format($developer->reputation_score) }} rep</span>
                        <span>·</span>
                        <span>Lv {{ $developer->level() }}</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                    <flux:icon name="check-badge" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="sm" class="mt-3">No verified developers yet</flux:heading>
                    <flux:text class="mt-1">Be the first — complete verification to appear here.</flux:text>
                    @auth
                        @unless (auth()->user()->is_verified)
                            <flux:button variant="primary" size="sm" href="{{ route('verify') }}" wire:navigate class="mt-3">
                                Get Verified
                            </flux:button>
                        @endunless
                    @endauth
                </div>
            @endforelse
        </div>

        {{ $this->developers->links() }}
    </div>
</div>