<?php

use App\Livewire\Concerns\ExportsSelectedRows;
use App\Mail\PlagiarismBanOverturnedMail;
use App\Models\PlagiarismStrike;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Plagiarism')] class extends Component
{
    use ExportsSelectedRows;
    use WithPagination;

    public string $filter = 'all';

    public string $search = '';

    public ?int $reviewing = null;

    public string $reviewNote = '';

    public function review(int $id): void
    {
        $this->reviewing = $id;
        $this->reviewNote = (string) PlagiarismStrike::find($id)?->review_note;
    }

    public function closeReview(): void
    {
        $this->reviewing = null;
        $this->reviewNote = '';
    }

    public function saveNote(int $id): void
    {
        $strike = PlagiarismStrike::findOrFail($id);

        $strike->update(['review_note' => trim($this->reviewNote) ?: null]);

        unset($this->rows);

        Flux::toast(variant: 'success', text: trim($this->reviewNote) ? 'Review note saved.' : 'Review note cleared.');
    }

    public function overturnBan(int $id): void
    {
        $strike = PlagiarismStrike::findOrFail($id);

        abort_unless($strike->isBan() && ! $strike->isOverturned(), 403);

        $strike->update([
            'overturned_at' => now(),
            'overturned_by' => auth()->id(),
            'review_note' => trim($this->reviewNote) ?: ($strike->review_note ?: null),
        ]);

        $offender = $strike->offender;

        if ($offender?->isSuspended()) {
            $offender->unsuspend();
        }

        if ($offender?->email) {
            Mail::to($offender)->send(new PlagiarismBanOverturnedMail($strike->fresh()));
        }

        $this->reviewing = null;
        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: 'Ban overturned — '.($offender?->name ?? 'the account').' reinstated and notified.');
    }

    public function reinstate(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless($user->isSuspended(), 403);

        $user->unsuspend();

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: $user->name.' reinstated.');
    }

    #[Computed]
    public function overview(): array
    {
        return [
            'total' => PlagiarismStrike::count(),
            'warnings' => PlagiarismStrike::where('action', 'warning')->count(),
            'bans' => PlagiarismStrike::where('action', 'banned')->whereNull('overturned_at')->count(),
            'overturned' => PlagiarismStrike::whereNotNull('overturned_at')->count(),
            'suspended' => User::whereNotNull('suspended_at')->where('is_admin', false)->count(),
        ];
    }

    public function updatedFilter(): void
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
        return PlagiarismStrike::query()
            ->with(['offender', 'owner', 'overturnedBy'])
            ->when($this->filter === 'warnings', fn ($query) => $query->where('action', 'warning'))
            ->when($this->filter === 'bans', fn ($query) => $query->where('action', 'banned')->whereNull('overturned_at'))
            ->when($this->filter === 'overturned', fn ($query) => $query->whereNotNull('overturned_at'))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('repo_url', 'like', '%'.trim($this->search).'%')
                ->orWhere('repo_owner', 'like', '%'.trim($this->search).'%')
                ->orWhere('repo_name', 'like', '%'.trim($this->search).'%')
                ->orWhereHas('offender', fn ($query) => $query
                    ->where('name', 'like', '%'.trim($this->search).'%')
                    ->orWhere('email', 'like', '%'.trim($this->search).'%'))))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function reviewingStrike(): ?PlagiarismStrike
    {
        return $this->reviewing
            ? PlagiarismStrike::with(['offender', 'owner', 'overturnedBy'])->find($this->reviewing)
            : null;
    }

    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $selected = PlagiarismStrike::with(['offender', 'owner'])
            ->whereIn('id', $this->selectedIds)
            ->latest()
            ->get();

        $rows = $selected->map(fn (PlagiarismStrike $strike) => [
            (string) $strike->strike_number,
            $strike->offender?->name ?? 'Deleted user',
            $strike->offender?->email ?? '—',
            $strike->repo_owner.'/'.$strike->repo_name,
            $strike->owner?->name ?? 'Not on platform',
            $strike->isOverturned() ? 'Overturned' : ($strike->isBan() ? 'Ban' : 'Warning'),
            $strike->offender?->isSuspended() ? 'Suspended' : 'Active',
            $strike->created_at->toDateTimeString(),
            (string) ($strike->review_note ?? '—'),
        ])->all();

        return [
            ['#', 'Offender', 'Offender email', 'Repository', 'Original owner', 'Action', 'Account', 'Reported', 'Note'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected plagiarism strikes';
    }

    protected function exportBasename(): string
    {
        return 'plagiarism-strikes';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Plagiarism</flux:heading>
        <flux:text>Review repository-copy strikes, overturn false-positive bans and reinstate suspended accounts.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Total strikes</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['total']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Warnings</div>
            <div class="text-2xl font-bold text-amber-600">{{ number_format($this->overview['warnings']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Active bans</div>
            <div class="text-2xl font-bold text-rose-600">{{ number_format($this->overview['bans']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Overturned</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['overturned']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Suspended accounts</div>
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->overview['suspended']) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach ([
                'all' => 'All',
                'warnings' => 'Warnings',
                'bans' => 'Active bans',
                'overturned' => 'Overturned',
            ] as $value => $label)
                <button type="button" wire:click="$set('filter', '{{ $value }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->filter === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <flux:input icon="magnifying-glass" type="search" placeholder="Search repo, owner or offender..." wire:model.live.debounce.300ms="search" class="w-full sm:w-80" />
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
                    <th class="px-3 py-2.5 font-medium">#</th>
                    <th class="px-3 py-2.5 font-medium">Offender</th>
                    <th class="px-3 py-2.5 font-medium">Repository</th>
                    <th class="px-3 py-2.5 font-medium">Original owner</th>
                    <th class="px-3 py-2.5 font-medium">Action</th>
                    <th class="px-3 py-2.5 font-medium">Account</th>
                    <th class="px-3 py-2.5 font-medium">Date</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $strike)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($strike->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $strike->id }})" {{ in_array($strike->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5 tabular-nums text-zinc-500">{{ $strike->strike_number }}</td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <flux:avatar :src="$strike->offender?->avatarUrl()" :alt="$strike->offender?->name ?? 'Deleted user'" circle class="size-8" />
                                <div class="min-w-0">
                                    <a href="{{ $strike->offender ? route('devid', $strike->offender->handle()) : '#' }}" wire:navigate class="truncate font-medium hover:underline">
                                        {{ $strike->offender?->name ?? 'Deleted user' }}
                                    </a>
                                    <div class="truncate text-xs text-zinc-500">{{ $strike->offender?->email ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-1.5 font-medium">
                                <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400" />
                                {{ $strike->repo_name }}
                            </div>
                            <a href="{{ $strike->repo_url }}" target="_blank" rel="noopener" class="block max-w-56 truncate text-xs text-zinc-500 hover:underline">
                                {{ $strike->repo_owner }}/{{ $strike->repo_name }}
                            </a>
                        </td>
                        <td class="px-3 py-2.5">
                            @if ($strike->owner)
                                <div class="flex items-center gap-2">
                                    <flux:avatar :src="$strike->owner->avatarUrl()" :alt="$strike->owner->name" circle class="size-6" />
                                    <a href="{{ route('devid', $strike->owner->handle()) }}" wire:navigate class="hover:underline">{{ $strike->owner->name }}</a>
                                </div>
                            @else
                                <span class="text-xs text-zinc-500">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-2">
                                @if ($strike->isOverturned())
                                    <flux:badge size="sm" inset="top bottom" color="emerald">Overturned</flux:badge>
                                @elseif ($strike->isBan())
                                    <flux:badge size="sm" inset="top bottom" color="rose">Ban</flux:badge>
                                @else
                                    <flux:badge size="sm" inset="top bottom" color="amber">Warning</flux:badge>
                                @endif
                                @if (filled($strike->review_note))
                                    <flux:tooltip :content="'Review note: '.Str::limit($strike->review_note, 160)" position="top">
                                        <flux:icon name="document-text" variant="micro" class="size-4 text-zinc-400" />
                                    </flux:tooltip>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            @if ($strike->offender?->isSuspended())
                                <flux:badge size="sm" inset="top bottom" color="rose">Suspended</flux:badge>
                            @else
                                <flux:badge size="sm" inset="top bottom" color="zinc">Active</flux:badge>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-xs text-zinc-500">{{ $strike->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-end gap-1.5">
                                <flux:button size="sm" variant="subtle" wire:click="review({{ $strike->id }})">
                                    Review
                                </flux:button>
                                @if ($strike->isBan() && ! $strike->isOverturned())
                                    <flux:button size="sm" variant="subtle" wire:click="overturnBan({{ $strike->id }})" wire:confirm="Overturn this ban and reinstate the account?">
                                        Overturn
                                    </flux:button>
                                @endif
                                @if ($strike->offender?->isSuspended() && ! ($strike->isBan() && ! $strike->isOverturned()))
                                    <flux:button size="sm" variant="subtle" wire:click="reinstate({{ $strike->offender->id }})" wire:confirm="Reinstate this account?">
                                        Reinstate
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No plagiarism strikes match your filters.
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

    <flux:modal name="strike-review" wire:model="reviewing" class="w-full max-w-lg overflow-hidden">
        @if ($this->reviewingStrike)
            @php $strike = $this->reviewingStrike; @endphp
            <div class="space-y-5">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <flux:avatar :src="$strike->offender?->avatarUrl()" :alt="$strike->offender?->name ?? 'Deleted user'" circle class="size-10" />
                        <div>
                            <div class="font-semibold">{{ $strike->offender?->name ?? 'Deleted user' }}</div>
                            <div class="text-xs text-zinc-500">{{ $strike->offender?->email ?? '—' }}</div>
                        </div>
                    </div>
                    @if ($strike->isOverturned())
                        <flux:badge color="emerald">Overturned</flux:badge>
                    @elseif ($strike->isBan())
                        <flux:badge color="rose">Ban #{{ $strike->strike_number }}</flux:badge>
                    @else
                        <flux:badge color="amber">Warning #{{ $strike->strike_number }}</flux:badge>
                    @endif
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Repository</div>
                    <div class="mt-1 font-semibold">{{ $strike->repo_owner }}/{{ $strike->repo_name }}</div>
                    <a href="{{ $strike->repo_url }}" target="_blank" rel="noopener" class="mt-0.5 block truncate text-xs text-accent hover:underline">{{ $strike->repo_url }}</a>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Reason</div>
                    <p class="mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $strike->reason }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Original owner</div>
                        @if ($strike->owner)
                            <a href="{{ route('devid', $strike->owner->handle()) }}" wire:navigate class="mt-1 block text-accent hover:underline">{{ $strike->owner->name }}</a>
                        @else
                            <div class="mt-1 text-zinc-500">Not on platform</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Reported</div>
                        <div class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $strike->created_at->toDayDateTimeString() }}</div>
                    </div>
                </div>

                @if ($strike->isOverturned())
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                        Overturned {{ $strike->overturned_at->diffForHumans() }}
                        @if ($strike->overturnedBy) by {{ $strike->overturnedBy->name }} @endif
                        — the account has been reinstated.
                    </div>
                @elseif ($strike->isBan())
                    <flux:button variant="danger" wire:click="overturnBan({{ $strike->id }})" wire:confirm="Overturn this ban and reinstate the account?" class="w-full">
                        <flux:icon name="arrow-path" variant="micro" />
                        Overturn ban & reinstate account
                    </flux:button>
                @endif

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Admin note</div>
                        <flux:button size="sm" variant="subtle" wire:click="saveNote({{ $strike->id }})">
                            Save note
                        </flux:button>
                    </div>
                    <flux:textarea wire:model="reviewNote" rows="3" placeholder="Record why this decision was made — visible to other admins reviewing this strike." class="mt-2 w-full" />
                    <p class="mt-1 text-xs text-zinc-500">The note is saved with the overturn, so the decision has an audit trail.</p>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close variant="ghost">Close</flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
