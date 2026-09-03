<?php

use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\Ad;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ads')] class extends Component
{
    use ExportsSelectedRows;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'title' => '',
        'image_url' => '',
        'target_url' => '',
        'is_active' => true,
        'sort_order' => 0,
        'starts_at' => null,
        'ends_at' => null,
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $ad = Ad::findOrFail($id);

        $this->editingId = $id;
        $this->form = [
            'title' => $ad->title,
            'image_url' => (string) $ad->image_url,
            'target_url' => (string) $ad->target_url,
            'is_active' => (bool) $ad->is_active,
            'sort_order' => (int) $ad->sort_order,
            'starts_at' => $ad->starts_at?->toDateString(),
            'ends_at' => $ad->ends_at?->toDateString(),
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:120'],
            'form.image_url' => ['nullable', 'string', 'url', 'max:2048'],
            'form.target_url' => ['nullable', 'string', 'url', 'max:2048'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.starts_at' => ['nullable', 'date'],
            'form.ends_at' => ['nullable', 'date', 'after_or_equal:form.starts_at'],
        ]);

        Ad::updateOrCreate(
            ['id' => $this->editingId],
            [
                'title' => $validated['form']['title'],
                'image_url' => $validated['form']['image_url'] ?: null,
                'target_url' => $validated['form']['target_url'] ?: null,
                'is_active' => $validated['form']['is_active'],
                'sort_order' => (int) $validated['form']['sort_order'],
                'starts_at' => $validated['form']['starts_at'] ?: null,
                'ends_at' => $validated['form']['ends_at'] ?: null,
            ],
        );

        $this->showForm = false;
        unset($this->rows);

        Flux::toast(variant: 'success', text: $this->editingId ? 'Ad updated.' : 'Ad created.');
    }

    public function toggle(int $id): void
    {
        $ad = Ad::findOrFail($id);
        $ad->update(['is_active' => ! $ad->is_active]);

        unset($this->rows);

        Flux::toast(variant: 'success', text: $ad->is_active ? 'Ad activated.' : 'Ad deactivated.');
    }

    public function delete(int $id): void
    {
        Ad::findOrFail($id)->delete();

        unset($this->rows);

        Flux::toast(variant: 'success', text: 'Ad deleted.');
    }

    private function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'image_url' => '',
            'target_url' => '',
            'is_active' => true,
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ];
        $this->resetErrorBag();
    }

    #[Computed]
    public function overview(): array
    {
        return [
            'total' => Ad::count(),
            'active' => Ad::where('is_active', true)->count(),
            'inactive' => Ad::where('is_active', false)->count(),
        ];
    }

    #[Computed]
    public function rows()
    {
        return Ad::query()
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->selectedIds = [];
    }

    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $selected = Ad::whereIn('id', $this->selectedIds)->orderBy('sort_order')->get();

        $rows = $selected->map(fn (Ad $ad) => [
            $ad->title,
            $ad->target_url ?? '',
            $ad->starts_at?->toDateString() ?? 'Anytime',
            $ad->ends_at?->toDateString() ?? '∞',
            (string) $ad->sort_order,
            $ad->is_active ? 'Active' : 'Inactive',
            $ad->created_at->toDateTimeString(),
        ])->all();

        return [
            ['Ad', 'Target URL', 'Starts', 'Ends', 'Sort', 'Status', 'Created'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected ads';
    }

    protected function exportBasename(): string
    {
        return 'ads';
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Ads</flux:heading>
            <flux:text>Manage the promotional banner shown on the home feed right panel.</flux:text>
        </div>
        <flux:button variant="primary" wire:click="create">
            <flux:icon name="plus" variant="micro" />
            Add ad
        </flux:button>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Total ads</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['total']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Active</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['active']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Inactive</div>
            <div class="text-2xl font-bold text-zinc-500">{{ number_format($this->overview['inactive']) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:input icon="magnifying-glass" type="search" placeholder="Search ads..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
        @if (count($this->selectedIds) > 0)
            <span class="text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
            <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-zinc-100 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-white/10 dark:text-zinc-200 dark:hover:bg-white/15">
                <flux:icon name="document-arrow-down" variant="micro" />
                PDF
            </button>
            <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-zinc-100 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-white/10 dark:text-zinc-200 dark:hover:bg-white/15">
                <flux:icon name="table-cells" variant="micro" />
                Excel
            </button>
        @endif
    </div>

    <div class="overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                    <th class="w-8 px-3 py-2.5 font-medium">
                        <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->rows->count() && $this->rows->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                    </th>
                    <th class="px-3 py-2.5 font-medium">Ad</th>
                    <th class="px-3 py-2.5 font-medium">Target URL</th>
                    <th class="px-3 py-2.5 font-medium">Run window</th>
                    <th class="px-3 py-2.5 font-medium">Sort</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $ad)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($ad->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $ad->id }})" {{ in_array($ad->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                @if ($ad->image_url)
                                    <img src="{{ $ad->image_url }}" alt="{{ $ad->title }}" class="size-9 shrink-0 rounded object-cover" loading="lazy" />
                                @else
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                                        <flux:icon name="megaphone" variant="mini" class="size-4" />
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $ad->title }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $ad->created_at->toDateString() }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="max-w-xs truncate px-3 py-2.5 text-xs text-zinc-500">{{ $ad->target_url ?? '-' }}</td>
                        <td class="px-3 py-2.5">
                            @php($status = $ad->runStatus())
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs tabular-nums text-zinc-500">
                                    {{ $ad->starts_at?->toDateString() ?? 'Anytime' }}
                                    →
                                    {{ $ad->ends_at?->toDateString() ?? '∞' }}
                                </span>
                                @if ($status === 'upcoming')
                                    <flux:badge size="sm" inset="top bottom" color="sky">Upcoming</flux:badge>
                                @elseif ($status === 'ended')
                                    <flux:badge size="sm" inset="top bottom" color="zinc">Ended</flux:badge>
                                @elseif ($status === 'running')
                                    <flux:badge size="sm" inset="top bottom" color="emerald">Running</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2.5 tabular-nums">{{ $ad->sort_order }}</td>
                        <td class="px-3 py-2.5">
                            <flux:badge size="sm" inset="top bottom" :color="$ad->is_active ? 'emerald' : 'zinc'">
                                {{ $ad->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-end gap-1.5">
                                <flux:button size="sm" variant="subtle" wire:click="toggle({{ $ad->id }})">
                                    {{ $ad->is_active ? 'Deactivate' : 'Activate' }}
                                </flux:button>
                                <flux:button size="sm" variant="subtle" wire:click="edit({{ $ad->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $ad->id }})">Delete</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No ads yet. Add one to start showing it on the feed.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="ad-form" wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="grid gap-4">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit ad' : 'Add ad' }}</flux:heading>
                <flux:text>Shown as a banner on the home feed right panel.</flux:text>
            </div>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="form.title" placeholder="e.g. Sponsor, Hire top engineers" />
                <flux:error name="form.title" />
            </flux:field>

            <flux:field>
                <flux:label>Image URL</flux:label>
                <flux:input wire:model="form.image_url" placeholder="https://… (optional)" />
                <flux:error name="form.image_url" />
            </flux:field>

            <flux:field>
                <flux:label>Target URL</flux:label>
                <flux:input wire:model="form.target_url" placeholder="https://… (optional)" />
                <flux:error name="form.target_url" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Runs from</flux:label>
                    <flux:input type="date" wire:model="form.starts_at" />
                    <flux:error name="form.starts_at" />
                </flux:field>
                <flux:field>
                    <flux:label>Runs until</flux:label>
                    <flux:input type="date" wire:model="form.ends_at" />
                    <flux:error name="form.ends_at" />
                </flux:field>
            </div>

            <flux:text class="-mt-1 text-[11px] text-zinc-400">Leave both empty to run indefinitely. The ad only shows between these dates.</flux:text>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Sort order</flux:label>
                    <flux:input type="number" min="0" wire:model="form.sort_order" />
                    <flux:error name="form.sort_order" />
                </flux:field>
                <flux:switch wire:model="form.is_active" label="Active" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="subtle" @click="$flux.modal('ad-form').close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save ad</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
