<?php

use App\Models\FeatureRequest;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Feature Requests')] class extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public ?int $editingId = null;

    public int $targetVotes = 50;

    public function approve(int $id): void
    {
        FeatureRequest::findOrFail($id)->approve();

        unset($this->requests);

        Flux::toast(variant: 'success', text: 'Request approved — it is now visible and votable.');
    }

    public function include(int $id): void
    {
        FeatureRequest::findOrFail($id)->markIncluded();

        unset($this->requests);

        Flux::toast(variant: 'success', text: 'Marked as developed and included.');
    }

    public function reject(int $id): void
    {
        FeatureRequest::where('status', '!=', FeatureRequest::STATUS_INCLUDED)->findOrFail($id)->update([
            'status' => FeatureRequest::STATUS_PENDING,
        ]);

        unset($this->requests);

        Flux::toast(variant: 'warning', text: 'Moved back to pending — hidden from the suggestion box.');
    }

    public function saveTarget(): void
    {
        $validated = $this->validate([
            'targetVotes' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        FeatureRequest::findOrFail($this->editingId)->update([
            'target_votes' => $validated['targetVotes'],
        ]);

        // A lowered target may already be reached.
        $request = FeatureRequest::find($this->editingId);

        if ($request && $request->status === FeatureRequest::STATUS_APPROVED && $request->hasReachedTarget()) {
            $request->markIncluded();
        }

        $this->editingId = null;

        unset($this->requests);

        Flux::toast(variant: 'success', text: 'Vote target updated.');
    }

    #[Computed]
    public function requests()
    {
        return FeatureRequest::query()
            ->withCount('votes')
            ->with('author')
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate(20);
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Feature requests</flux:heading>
        <flux:text>Approve community suggestions so they appear in the feed suggestion box. Set the vote target — when reached, the feature is marked as developed and included.</flux:text>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <x-searchable-select wire:model.live="statusFilter" size="sm" placeholder="All statuses" class="w-44">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="included">Included</option>
        </x-searchable-select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                    <th class="px-3 py-2.5 font-medium">Suggestion</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Votes</th>
                    <th class="px-3 py-2.5 font-medium">Target</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->requests as $request)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="px-3 py-2.5">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->title }}</div>
                            @if ($request->description)
                                <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ $request->description }}</div>
                            @endif
                            @if ($request->author)
                                <div class="mt-0.5 text-[11px] text-zinc-400">by {{ $request->author->name }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">
                            <flux:badge
                                size="sm"
                                inset="top bottom"
                                :color="$request->status === 'approved' ? 'sky' : ($request->status === 'included' ? 'emerald' : 'zinc')"
                            >
                                {{ ucfirst($request->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-3 py-2.5 tabular-nums">{{ number_format($request->votes_count) }}</td>
                        <td class="px-3 py-2.5">
                            @if ($this->editingId === $request->id)
                                <form wire:submit="saveTarget" class="flex items-center gap-1.5">
                                    <flux:input type="number" wire:model="targetVotes" class="w-24" />
                                    <flux:button size="xs" variant="primary" type="submit">Save</flux:button>
                                    <flux:button size="xs" variant="subtle" type="button" wire:click="$set('editingId', null)">Cancel</flux:button>
                                </form>
                            @else
                                <span class="tabular-nums">{{ number_format($request->target_votes) }}</span>
                                <flux:button size="xs" variant="subtle" wire:click="$set('editingId', {{ $request->id }}); $set('targetVotes', {{ $request->target_votes }})">Edit</flux:button>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-end gap-1.5">
                                @if ($request->status === 'pending')
                                    <flux:button size="sm" variant="primary" wire:click="approve({{ $request->id }})">Approve</flux:button>
                                @elseif ($request->status === 'approved')
                                    <flux:button size="sm" wire:click="include({{ $request->id }})">Mark included</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="reject({{ $request->id }})">Unpublish</flux:button>
                                @else
                                    <flux:button size="sm" variant="subtle" wire:click="reject({{ $request->id }})">Revert</flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-sm text-zinc-500">No feature requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->requests->links() }}
</div>
