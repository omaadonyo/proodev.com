<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Engineering Journal')] class extends Component
{
    public string $visibility = '';

    #[Computed]
    public function entries()
    {
        return auth()->user()
            ->journalEntries()
            ->when($this->visibility, fn ($q) => $q->where('visibility', $this->visibility))
            ->orderByDesc('created_at')
            ->take(50)
            ->get();
    }

    public function setVisibility(string $visibility = ''): void
    {
        $this->visibility = $visibility;
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Engineering Journal</flux:heading>
            <flux:text>Your private engineering log. AI turns raw notes into structured portfolio evidence.</flux:text>
        </div>
        <flux:button variant="primary" href="{{ route('journal.create') }}" wire:navigate>
            <flux:icon name="plus" variant="micro" /> New entry
        </flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <flux:button :variant="$visibility === '' ? 'primary' : 'ghost'" size="sm" wire:click="setVisibility()">All</flux:button>
        @foreach (['private', 'team', 'public'] as $value)
            <flux:button :variant="$visibility === $value ? 'primary' : 'ghost'" size="sm" wire:click="setVisibility('{{ $value }}')">
                {{ ucfirst($value) }}
            </flux:button>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($this->entries as $entry)
            <a href="{{ route('journal.show', $entry) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between gap-2">
                    <flux:badge :color="$entry->visibility->value === 'public' ? 'emerald' : ($entry->visibility->value === 'team' ? 'sky' : 'zinc')" size="sm">
                        {{ ucfirst($entry->visibility->value) }}
                    </flux:badge>
                    @if ($entry->ai_processed)
                        <span class="inline-flex items-center gap-1 text-xs text-indigo-500">
                            <flux:icon name="sparkles" variant="micro" /> AI structured
                        </span>
                    @endif
                </div>

                <div class="mt-2 text-base font-semibold group-hover:text-accent">{{ $entry->title ?: 'Untitled entry' }}</div>

                @if ($entry->structured_content['summary'] ?? null)
                    <p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ \App\Support\Markdown::plain($entry->structured_content['summary']) }}</p>
                @else
                    <p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ \App\Support\Markdown::plain(Str::limit($entry->content, 140)) }}</p>
                @endif

                @if (filled($entry->structured_content['tags'] ?? null))
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach (array_slice($entry->structured_content['tags'], 0, 4) as $tag)
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3 text-xs text-zinc-400">{{ $entry->created_at->diffForHumans() }}</div>
            </a>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                <flux:heading>Your journal is empty</flux:heading>
                <flux:text>What did you build today? What bug took the longest to solve?</flux:text>
                <div class="mt-4">
                    <flux:button variant="primary" href="{{ route('journal.create') }}" wire:navigate>Write your first entry</flux:button>
                </div>
            </div>
        @endforelse
    </div>
</div>
