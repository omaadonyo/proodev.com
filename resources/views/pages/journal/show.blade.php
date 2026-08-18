<?php

use App\Models\JournalEntry;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Journal Entry')] class extends Component
{
    public JournalEntry $entry;

    public function mount(JournalEntry $entry): void
    {
        $this->authorize('view', $entry);

        $this->entry = $entry;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->entry);

        $this->entry->delete();

        Flux::toast(variant: 'success', text: 'Entry deleted.');

        $this->redirectRoute('journal.index', navigate: true);
    }
}
?>

<div class="mx-auto grid max-w-3xl gap-6">
    <div>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl">{{ $this->entry->title ?: 'Untitled entry' }}</flux:heading>
                    <flux:badge :color="$this->entry->visibility->value === 'public' ? 'emerald' : ($this->entry->visibility->value === 'team' ? 'sky' : 'zinc')" inset="top bottom">
                        {{ ucfirst($this->entry->visibility->value) }}
                    </flux:badge>
                </div>
                <flux:text>{{ $this->entry->created_at->diffForHumans() }}</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="danger" wire:click="delete" wire:confirm="Delete this entry?">Delete</flux:button>
            </div>
        </div>

        <x-markdown :text="$this->entry->content" class="mt-5" />
    </div>

    @if ($this->entry->ai_processed && filled($this->entry->structured_content))
        @php $structured = $this->entry->structured_content; @endphp
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-6 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <div class="flex items-center gap-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                <flux:icon name="sparkles" variant="micro" />
                AI Structured Log
            </div>

            @if (filled($structured['summary'] ?? null))
                <x-markdown :text="$structured['summary']" class="mt-3" />
            @endif

            @if (filled($structured['highlights'] ?? null))
                <div class="mt-4 grid gap-2">
                    @foreach (array_slice($structured['highlights'], 0, 5) as $highlight)
                        <div class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-indigo-500"></span>
                            <span>{{ $highlight }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (filled($structured['categories'] ?? null) || filled($structured['tags'] ?? null))
                <div class="mt-4 flex flex-wrap gap-1.5">
                    @foreach (array_merge($structured['categories'] ?? [], $structured['tags'] ?? []) as $label)
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium dark:bg-zinc-800">{{ ucfirst($label) }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="flex justify-between">
        <flux:button variant="ghost" href="{{ route('journal.index') }}" wire:navigate>← Back to journal</flux:button>
    </div>
</div>
