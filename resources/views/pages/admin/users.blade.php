<?php

use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component
{
    use ExportsSelectedRows;
    use WithPagination;

    public string $status = 'all';

    public string $search = '';

    public function toggleAdmin(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast(variant: 'warning', text: "You can't remove your own admin access.");

            return;
        }

        $user = User::findOrFail($id);

        $user->update(['is_admin' => ! $user->is_admin]);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: 'Permissions updated.');
    }

    public function toggleSuspend(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast(variant: 'warning', text: "You can't suspend your own account.");

            return;
        }

        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            Flux::toast(variant: 'warning', text: 'Admins cannot be suspended.');

            return;
        }

        $user->isSuspended() ? $user->unsuspend() : $user->suspend();

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: $user->isSuspended() ? 'Account suspended.' : 'Account reinstated.');
    }

    public function toggleVerify(int $id): void
    {
        $user = User::findOrFail($id);

        $wasVerified = $user->isVerified();

        $user->update([
            'is_verified' => ! $wasVerified,
            'verified_at' => $wasVerified ? null : now(),
        ]);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: $wasVerified ? 'Verification removed.' : 'User verified.');
    }

    public function adjustCredits(int $id, string $field, int $delta): void
    {
        if (! in_array($field, ['credit_balance', 'vouch_credits'], true) || ! in_array($delta, [-1, 1], true)) {
            return;
        }

        $user = User::findOrFail($id);

        $step = $field === 'credit_balance' ? 10 : 1;
        $next = max(0, (int) $user->{$field} + ($delta * $step));

        $user->update([$field => $next]);

        unset($this->rows);

        Flux::toast(variant: 'success', text: ucfirst(str_replace('_', ' ', $field)).' set to '.$next.'.');
    }

    #[Computed]
    public function overview(): array
    {
        return [
            'total' => User::count(),
            'admins' => User::where('is_admin', true)->count(),
            'verified' => User::where('is_verified', true)->count(),
            'suspended' => User::whereNotNull('suspended_at')->count(),
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
        return User::query()
            ->when($this->status === 'admins', fn ($query) => $query->where('is_admin', true))
            ->when($this->status === 'verified', fn ($query) => $query->where('is_verified', true))
            ->when($this->status === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('email', 'like', '%'.trim($this->search).'%')
                ->orWhere('username', 'like', '%'.trim($this->search).'%')))
            ->latest()
            ->paginate(25);
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'admin' => 'red',
            'verified' => 'emerald',
            'suspended' => 'amber',
            default => 'zinc',
        };
    }

    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $selected = User::whereIn('id', $this->selectedIds)->latest()->get();

        $rows = $selected->map(fn (User $user) => [
            $user->name,
            $user->email,
            $user->handle(),
            $user->isAdmin() ? 'Admin' : $user->role->label(),
            'Lv '.$user->level().' '.$user->levelTitle(),
            (string) $user->reputation_score,
            (string) $user->credit_balance,
            (string) $user->vouch_credits,
            $user->isSuspended() ? 'Suspended' : ($user->isVerified() ? 'Verified' : 'Active'),
            $user->last_activity_at?->toDateTimeString() ?? 'Never active',
        ])->all();

        return [
            ['Name', 'Email', 'Handle', 'Role', 'Level', 'Reputation', 'Credits', 'Vouches', 'Status', 'Last active'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected users';
    }

    protected function exportBasename(): string
    {
        return 'users';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Users</flux:heading>
        <flux:text>Manage accounts, roles, verification, balances and access.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Total users</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['total']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->overview['verified']) }} verified</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Admins</div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($this->overview['admins']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Verified developers</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['verified']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Suspended</div>
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->overview['suspended']) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach ([
                'all' => 'All',
                'admins' => 'Admins',
                'verified' => 'Verified',
                'suspended' => 'Suspended',
            ] as $value => $label)
                <button type="button" wire:click="$set('status', '{{ $value }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->status === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <flux:input icon="magnifying-glass" type="search" placeholder="Search users..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
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
                    <th class="px-3 py-2.5 font-medium">Role</th>
                    <th class="px-3 py-2.5 font-medium">Level</th>
                    <th class="px-3 py-2.5 font-medium">Reputation</th>
                    <th class="px-3 py-2.5 font-medium">Credits</th>
                    <th class="px-3 py-2.5 font-medium">Vouches</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Last active</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $user)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($user->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $user->id }})" {{ in_array($user->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-8" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate font-medium">{{ $user->name }}</span>
                                        @if ($user->isVerified())
                                            <flux:icon name="check-badge" variant="micro" class="size-4 text-emerald-500" />
                                        @endif
                                    </div>
                                    <div class="truncate text-xs text-zinc-500">{{ $user->email }} · {{ $user->handle() }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            @if ($user->isAdmin())
                                <flux:badge size="sm" inset="top bottom" color="red">Admin</flux:badge>
                            @else
                                <span class="text-xs">{{ $user->role->label() }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="font-medium">Lv {{ $user->level() }}</span>
                            <span class="block text-xs text-zinc-500">{{ $user->levelTitle() }}</span>
                        </td>
                        <td class="px-3 py-2.5 tabular-nums">{{ number_format($user->reputation_score) }}</td>
                        <td class="px-3 py-2.5">
                            <div class="inline-flex items-center gap-1">
                                <flux:button size="sm" variant="subtle" wire:click="adjustCredits({{ $user->id }}, 'credit_balance', -1)" class="!px-1">−</flux:button>
                                <span class="min-w-8 text-center tabular-nums">{{ number_format($user->credit_balance) }}</span>
                                <flux:button size="sm" variant="subtle" wire:click="adjustCredits({{ $user->id }}, 'credit_balance', 1)" class="!px-1">+</flux:button>
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="inline-flex items-center gap-1">
                                <flux:button size="sm" variant="subtle" wire:click="adjustCredits({{ $user->id }}, 'vouch_credits', -1)" class="!px-1">−</flux:button>
                                <span class="min-w-8 text-center tabular-nums">{{ number_format($user->vouch_credits) }}</span>
                                <flux:button size="sm" variant="subtle" wire:click="adjustCredits({{ $user->id }}, 'vouch_credits', 1)" class="!px-1">+</flux:button>
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            @if ($user->isAdmin())
                                <flux:badge size="sm" inset="top bottom" color="red">Admin</flux:badge>
                            @elseif ($user->isSuspended())
                                <flux:badge size="sm" inset="top bottom" color="amber">Suspended</flux:badge>
                            @elseif ($user->isVerified())
                                <flux:badge size="sm" inset="top bottom" color="emerald">Verified</flux:badge>
                            @else
                                <flux:badge size="sm" inset="top bottom" color="zinc">Active</flux:badge>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-xs text-zinc-500">
                            @if ($user->isOnline())
                                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-600">
                                    <span class="size-2 rounded-full bg-emerald-500"></span>
                                    Online
                                </span>
                            @else
                                {{ $user->last_activity_at?->diffForHumans() ?? 'Never active' }}
                            @endif
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-end gap-1.5">
                                @if (! $user->isAdmin())
                                    <flux:button size="sm" variant="subtle" wire:click="toggleVerify({{ $user->id }})">
                                        {{ $user->isVerified() ? 'Unverify' : 'Verify' }}
                                    </flux:button>
                                @endif
                                @if (! $user->isAdmin())
                                    <flux:button size="sm" variant="subtle" wire:click="toggleSuspend({{ $user->id }})">
                                        {{ $user->isSuspended() ? 'Reinstate' : 'Suspend' }}
                                    </flux:button>
                                @endif
                                {{-- <flux:button size="sm" variant="subtle" wire:click="toggleAdmin({{ $user->id }})">
                                    {{ $user->is_admin ? 'Remove admin' : 'Make admin' }}
                                </flux:button> --}}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No users match your filters.
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
