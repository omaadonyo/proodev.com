<?php

use App\Actions\Vouches\ApproveVouchAction;
use App\Enums\VouchStatus;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\Vouch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Vouches')] class extends Component
{
    use ExportsSelectedRows;
    use WithPagination;

    public string $status = 'all';

    public string $search = '';

    public function approve(int $id): void
    {
        app(ApproveVouchAction::class)->handle(Vouch::findOrFail($id), true);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: 'Vouch approved and applied.');
    }

    public function reject(int $id): void
    {
        app(ApproveVouchAction::class)->handle(Vouch::findOrFail($id), false);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'warning', text: 'Vouch rejected; credit returned.');
    }

    #[Computed]
    public function overview(): array
    {
        return [
            'pending' => Vouch::where('status', VouchStatus::Pending)->count(),
            'approved' => Vouch::where('status', VouchStatus::Approved)->count(),
            'rejected' => Vouch::where('status', VouchStatus::Rejected)->count(),
            'total' => Vouch::count(),
        ];
    }

    public function updatedStatus(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
    }

    #[Computed]
    public function rows()
    {
        return Vouch::with(['voucher', 'vouchee', 'skill'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('message', 'like', "%{$this->search}%")
                        ->orWhereHas('voucher', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('vouchee', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('skill', fn ($query) => $query->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderByRaw('case when status = "pending" then 0 else 1 end')
            ->latest('created_at')
            ->paginate(25);
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'approved' => 'emerald',
            'pending' => 'amber',
            default => 'red',
        };
    }

    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $selected = Vouch::with(['voucher', 'vouchee', 'skill'])
            ->whereIn('id', $this->selectedIds)
            ->latest()
            ->get();

        $rows = $selected->map(fn (Vouch $vouch) => [
            $vouch->voucher?->name ?? '—',
            $vouch->voucher?->email ?? '—',
            $vouch->vouchee?->name ?? '—',
            $vouch->vouchee?->email ?? '—',
            $vouch->type->label(),
            $vouch->skill?->name ?? '—',
            $vouch->message ?? '',
            $vouch->status->label(),
            $vouch->created_at->toDateTimeString(),
        ])->all();

        return [
            ['Voucher', 'Voucher email', 'Vouchee', 'Vouchee email', 'Type', 'Skill', 'Message', 'Status', 'Date'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected vouches';
    }

    protected function exportBasename(): string
    {
        return 'vouches';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Vouches</flux:heading>
        <flux:text>Review pending vouches before they affect reputation.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Pending</div>
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->overview['pending']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Approved</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['approved']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Rejected</div>
            <div class="text-2xl font-bold text-red-500">{{ number_format($this->overview['rejected']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Total</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['total']) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach ([
                'all' => 'All',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ] as $value => $label)
                <button type="button" wire:click="$set('status', '{{ $value }}')" class="rounded-md px-2.5 py-1 text-xs font-medium {{ $this->status === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <flux:input icon="magnifying-glass" type="search" placeholder="Search vouches..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
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
                    <th class="px-3 py-2.5 font-medium">Voucher</th>
                    <th class="px-3 py-2.5 font-medium">Vouchee</th>
                    <th class="px-3 py-2.5 font-medium">Type</th>
                    <th class="px-3 py-2.5 font-medium">Skill</th>
                    <th class="px-3 py-2.5 font-medium">Message</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Date</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $vouch)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($vouch->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $vouch->id }})" {{ in_array($vouch->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <flux:avatar :src="$vouch->voucher->avatarUrl()" :alt="$vouch->voucher->name" circle class="size-7" />
                                <span class="font-medium">{{ $vouch->voucher->name }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <flux:avatar :src="$vouch->vouchee->avatarUrl()" :alt="$vouch->vouchee->name" circle class="size-7" />
                                <span class="font-medium">{{ $vouch->vouchee->name }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 text-xs">{{ $vouch->type->label() }}</td>
                        <td class="px-3 py-2.5 text-xs">{{ $vouch->skill?->name ?? '—' }}</td>
                        <td class="max-w-xs truncate px-3 py-2.5 text-xs text-zinc-500">"{{ $vouch->message }}"</td>
                        <td class="px-3 py-2.5">
                            <flux:badge size="sm" inset="top bottom" :color="$this->statusColor($vouch->status->value)">
                                {{ $vouch->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-3 py-2.5 text-zinc-500">{{ $vouch->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-2.5">
                            @if ($vouch->status === \App\Enums\VouchStatus::Pending)
                                <div class="flex justify-end gap-1.5">
                                    <flux:button size="sm" variant="primary" wire:click="approve({{ $vouch->id }})">Approve</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="reject({{ $vouch->id }})">Reject</flux:button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No vouches match your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($this->rows->hasPages())
        <div class="mt-4">
            {{ $this->rows->links() }}
        </div>
    @endif
</div>
