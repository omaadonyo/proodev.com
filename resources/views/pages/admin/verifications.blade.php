<?php

use App\Actions\Verification\ApproveVerificationAction;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\VerificationRequest;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Verifications')] class extends Component
{
    use ExportsSelectedRows;
    use WithPagination;

    public string $status = 'all';

    public string $search = '';

    public function approve(int $id): void
    {
        app(ApproveVerificationAction::class)->handle(VerificationRequest::findOrFail($id), true);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: 'Verification approved.');
    }

    public function reject(int $id): void
    {
        app(ApproveVerificationAction::class)->handle(VerificationRequest::findOrFail($id), false);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'warning', text: 'Verification rejected.');
    }

    #[Computed]
    public function overview(): array
    {
        return [
            'pending' => VerificationRequest::where('status', 'pending')->count(),
            'approved' => VerificationRequest::where('status', 'approved')->count(),
            'rejected' => VerificationRequest::where('status', 'rejected')->count(),
            'total' => VerificationRequest::count(),
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
        return VerificationRequest::with('user')
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('company_name', 'like', "%{$this->search}%")
                        ->orWhere('company_domain', 'like', "%{$this->search}%")
                        ->orWhere('label', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$this->search}%"));
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
        $selected = VerificationRequest::with('user')->whereIn('id', $this->selectedIds)->latest()->get();

        $rows = $selected->map(fn (VerificationRequest $request) => [
            $request->user?->name ?? '-',
            $request->user?->email ?? '-',
            $request->type->label(),
            $request->label ?? '-',
            $request->company_name ? $request->company_name.($request->company_domain ? ' ('.$request->company_domain.')' : '') : '-',
            collect($request->evidence ?? [])->implode(', ') ?: '-',
            ucfirst($request->status),
            $request->created_at->toDateTimeString(),
        ])->all();

        return [
            ['Name', 'Email', 'Type', 'Label', 'Company', 'Evidence', 'Status', 'Requested'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected verifications';
    }

    protected function exportBasename(): string
    {
        return 'verifications';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Verifications</flux:heading>
        <flux:text>Approve or reject company and skill verification requests.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Pending</div>
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->overview['pending']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Approved</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['approved']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Rejected</div>
            <div class="text-2xl font-bold text-red-500">{{ number_format($this->overview['rejected']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
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
        <flux:input icon="magnifying-glass" type="search" placeholder="Search verifications..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
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
                    <th class="px-3 py-2.5 font-medium">User</th>
                    <th class="px-3 py-2.5 font-medium">Type</th>
                    <th class="px-3 py-2.5 font-medium">Label / Company</th>
                    <th class="px-3 py-2.5 font-medium">Evidence</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Requested</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $request)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($request->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $request->id }})" {{ in_array($request->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <flux:avatar :src="$request->user->avatarUrl()" :alt="$request->user->name" circle class="size-8" />
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $request->user->name }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $request->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 text-xs">{{ $request->type->label() }}</td>
                        <td class="px-3 py-2.5">
                            {{ $request->label ?? '-' }}
                            @if ($request->company_name)
                                <div class="text-xs text-zinc-500">{{ $request->company_name }}@if ($request->company_domain) · {{ $request->company_domain }}@endif</div>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-xs text-zinc-500">{{ collect($request->evidence ?? [])->implode(', ') ?: '-' }}</td>
                        <td class="px-3 py-2.5">
                            <flux:badge size="sm" inset="top bottom" :color="$this->statusColor($request->status)">
                                {{ $request->status }}
                            </flux:badge>
                        </td>
                        <td class="px-3 py-2.5 text-zinc-500">{{ $request->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-2.5">
                            @if ($request->isPending())
                                <div class="flex justify-end gap-1.5">
                                    <flux:button size="sm" variant="primary" wire:click="approve({{ $request->id }})">Approve</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="reject({{ $request->id }})">Reject</flux:button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No verification requests match your filters.
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
