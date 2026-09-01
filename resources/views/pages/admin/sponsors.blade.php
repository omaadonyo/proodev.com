<?php

use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\Sponsor;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sponsors')] class extends Component
{
    use ExportsSelectedRows;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'logo_url' => '',
        'website_url' => '',
        'tagline' => '',
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
        $sponsor = Sponsor::findOrFail($id);

        $this->editingId = $id;
        $this->form = [
            'name' => $sponsor->name,
            'logo_url' => (string) $sponsor->logo_url,
            'website_url' => (string) $sponsor->website_url,
            'tagline' => (string) $sponsor->tagline,
            'is_active' => (bool) $sponsor->is_active,
            'sort_order' => (int) $sponsor->sort_order,
            'starts_at' => $sponsor->starts_at?->toDateString(),
            'ends_at' => $sponsor->ends_at?->toDateString(),
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.logo_url' => ['nullable', 'string', 'url', 'max:2048'],
            'form.website_url' => ['nullable', 'string', 'url', 'max:2048'],
            'form.tagline' => ['nullable', 'string', 'max:255'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.starts_at' => ['nullable', 'date'],
            'form.ends_at' => ['nullable', 'date', 'after_or_equal:form.starts_at'],
        ]);

        Sponsor::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $validated['form']['name'],
                'logo_url' => $validated['form']['logo_url'] ?: null,
                'website_url' => $validated['form']['website_url'] ?: null,
                'tagline' => $validated['form']['tagline'] ?: null,
                'is_active' => $validated['form']['is_active'],
                'sort_order' => (int) $validated['form']['sort_order'],
                'starts_at' => $validated['form']['starts_at'] ?: null,
                'ends_at' => $validated['form']['ends_at'] ?: null,
            ],
        );

        $this->showForm = false;
        unset($this->rows);

        Flux::toast(variant: 'success', text: $this->editingId ? 'Sponsor updated.' : 'Sponsor created.');
    }

    public function toggle(int $id): void
    {
        $sponsor = Sponsor::findOrFail($id);
        $sponsor->update(['is_active' => ! $sponsor->is_active]);

        unset($this->rows);

        Flux::toast(variant: 'success', text: $sponsor->is_active ? 'Sponsor activated.' : 'Sponsor deactivated.');
    }

    public function delete(int $id): void
    {
        Sponsor::findOrFail($id)->delete();

        unset($this->rows);

        Flux::toast(variant: 'success', text: 'Sponsor deleted.');
    }

    private function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'logo_url' => '',
            'website_url' => '',
            'tagline' => '',
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
            'total' => Sponsor::count(),
            'active' => Sponsor::where('is_active', true)->count(),
            'inactive' => Sponsor::where('is_active', false)->count(),
        ];
    }

    #[Computed]
    public function rows()
    {
        return Sponsor::query()
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
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
        $selected = Sponsor::whereIn('id', $this->selectedIds)->orderBy('sort_order')->get();

        $rows = $selected->map(fn (Sponsor $sponsor) => [
            $sponsor->name,
            $sponsor->tagline ?? '',
            $sponsor->website_url ?? '',
            $sponsor->starts_at?->toDateString() ?? 'Anytime',
            $sponsor->ends_at?->toDateString() ?? '-∞',
            (string) $sponsor->sort_order,
            $sponsor->is_active ? 'Active' : 'Inactive',
            $sponsor->created_at->toDateTimeString(),
        ])->all();

        return [
            ['Sponsor', 'Tagline', 'Website', 'Starts', 'Ends', 'Sort', 'Status', 'Created'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected sponsors';
    }

    protected function exportBasename(): string
    {
        return 'sponsors';
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Sponsors</flux:heading>
            <flux:text>Manage the sponsors shown on the home feed right panel.</flux:text>
        </div>
        <flux:button variant="primary" wire:click="create">
            <flux:icon name="plus" variant="micro" />
            Add sponsor
        </flux:button>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Total sponsors</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['total']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Active</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['active']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Inactive</div>
            <div class="text-2xl font-bold text-zinc-500">{{ number_format($this->overview['inactive']) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:input icon="magnifying-glass" type="search" placeholder="Search sponsors..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
        @if (count($this->selectedIds) > 0)
            <span class="text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
            <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                <flux:icon name="document-arrow-down" variant="micro" />
                PDF
            </button>
            <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
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
                    <th class="px-3 py-2.5 font-medium">Sponsor</th>
                    <th class="px-3 py-2.5 font-medium">Tagline</th>
                    <th class="px-3 py-2.5 font-medium">Run window</th>
                    <th class="px-3 py-2.5 font-medium">Sort</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $sponsor)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($sponsor->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $sponsor->id }})" {{ in_array($sponsor->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                @if ($sponsor->logo_url)
                                    <img src="{{ $sponsor->logo_url }}" alt="{{ $sponsor->name }}" class="size-9 shrink-0 rounded-lg object-cover" loading="lazy" />
                                @else
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-xs font-bold text-accent">
                                        {{ \Illuminate\Support\Str::initials($sponsor->name) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $sponsor->name }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $sponsor->created_at->toDateString() }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="max-w-xs truncate px-3 py-2.5 text-xs text-zinc-500">{{ $sponsor->tagline ?? '-' }}</td>
                        <td class="px-3 py-2.5">
                            @php($status = $sponsor->runStatus())
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs tabular-nums text-zinc-500">
                                    {{ $sponsor->starts_at?->toDateString() ?? 'Anytime' }}
                                    →
                                    {{ $sponsor->ends_at?->toDateString() ?? '∞' }}
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
                        <td class="px-3 py-2.5 tabular-nums">{{ $sponsor->sort_order }}</td>
                        <td class="px-3 py-2.5">
                            <flux:badge size="sm" inset="top bottom" :color="$sponsor->is_active ? 'emerald' : 'zinc'">
                                {{ $sponsor->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-end gap-1.5">
                                <flux:button size="sm" variant="subtle" wire:click="toggle({{ $sponsor->id }})">
                                    {{ $sponsor->is_active ? 'Deactivate' : 'Activate' }}
                                </flux:button>
                                <flux:button size="sm" variant="subtle" wire:click="edit({{ $sponsor->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $sponsor->id }})">Delete</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No sponsors yet. Add one to show it on the feed.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="sponsor-form" wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="grid gap-4">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit sponsor' : 'Add sponsor' }}</flux:heading>
                <flux:text>Shown in the "Our Sponsors" card on the home feed.</flux:text>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="form.name" placeholder="e.g. Acme Corp" />
                <flux:error name="form.name" />
            </flux:field>

            <flux:field>
                <flux:label>Logo URL</flux:label>
                <flux:input wire:model="form.logo_url" placeholder="https://… (optional)" />
                <flux:error name="form.logo_url" />
            </flux:field>

            <flux:field>
                <flux:label>Website URL</flux:label>
                <flux:input wire:model="form.website_url" placeholder="https://… (optional)" />
                <flux:error name="form.website_url" />
            </flux:field>

            <flux:field>
                <flux:label>Tagline</flux:label>
                <flux:input wire:model="form.tagline" placeholder="Short description (optional)" />
                <flux:error name="form.tagline" />
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

            <flux:text class="-mt-1 text-[11px] text-zinc-400">Leave both empty to run indefinitely. The sponsor only shows between these dates.</flux:text>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Sort order</flux:label>
                    <flux:input type="number" min="0" wire:model="form.sort_order" />
                    <flux:error name="form.sort_order" />
                </flux:field>
                <flux:switch wire:model="form.is_active" label="Active" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="subtle" @click="$flux.modal('sponsor-form').close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save sponsor</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
