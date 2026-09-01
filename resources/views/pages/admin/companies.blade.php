<?php

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\Payment;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Companies')] class extends Component
{
    use ExportsSelectedRows;
    use WithPagination;

    public string $status = 'all';

    public string $search = '';

    public function approve(int $id): void
    {
        $company = Company::findOrFail($id);

        $company->update([
            'status' => CompanyStatus::Approved,
            'approved_at' => $company->approved_at ?? now(),
        ]);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'success', text: 'Company approved.');
    }

    public function suspend(int $id): void
    {
        Company::findOrFail($id)->update(['status' => CompanyStatus::Suspended]);

        unset($this->rows, $this->overview);

        Flux::toast(variant: 'warning', text: 'Company suspended.');
    }

    public function grantJobPosts(int $id, int $credits): void
    {
        $company = Company::findOrFail($id);

        $company->grantJobPosts($credits);

        unset($this->rows);

        Flux::toast(variant: 'success', text: "Added {$credits} job post credit(s) to {$company->name}.");
    }

    public function planLabel(string $value): string
    {
        return CompanyPlan::tryFrom($value)?->label() ?? $value;
    }

    #[Computed]
    public function overview(): array
    {
        return [
            'total' => Company::count(),
            'pending' => Company::where('status', CompanyStatus::Pending)->count(),
            'approved' => Company::where('status', CompanyStatus::Approved)->count(),
            'suspended' => Company::where('status', CompanyStatus::Suspended)->count(),
            'jobs' => Job::count(),
            'applicants' => Application::count(),
            'revenue' => Payment::where('status', PaymentStatus::Paid)->sum('amount'),
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
        return Company::query()
            ->with('owner')
            ->withCount([
                'jobs',
                'jobs as open_jobs_count' => fn ($query) => $query->open(),
                'members',
                'applications',
            ])
            ->withSum('payments as lifetime_revenue', 'amount')
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('industry', 'like', "%{$this->search}%")
                        ->orWhere('location', 'like', "%{$this->search}%")
                        ->orWhereHas('owner', fn ($query) => $query->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest()
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
        $selected = Company::query()
            ->with('owner')
            ->withCount(['jobs', 'jobs as open_jobs_count' => fn ($query) => $query->open(), 'members', 'applications'])
            ->withSum('payments as lifetime_revenue', 'amount')
            ->whereIn('id', $this->selectedIds)
            ->latest()
            ->get();

        $rows = $selected->map(fn (Company $company) => [
            $company->name,
            $company->owner?->name ?? 'No owner',
            $company->industry ?? '-',
            $company->location ?? '-',
            $company->plan->label(),
            (string) $company->jobs_count,
            (string) ($company->open_jobs_count ?? 0),
            (string) ($company->applications_count ?? 0),
            (string) ($company->members_count ?? 0),
            number_format((float) ($company->lifetime_revenue ?? 0), 2, '.', ''),
            $company->status->label(),
            $company->created_at->toDateTimeString(),
        ])->all();

        return [
            ['Company', 'Owner', 'Industry', 'Location', 'Plan', 'Jobs', 'Open jobs', 'Applicants', 'Members', 'Revenue', 'Status', 'Joined'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected companies';
    }

    protected function exportBasename(): string
    {
        return 'companies';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Companies</flux:heading>
        <flux:text>Approve, review and monitor every recruiter company on the platform.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Total companies</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['total']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->overview['pending']) }} pending review</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Approved</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['approved']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->overview['suspended']) }} suspended</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Jobs &amp; applicants</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['jobs']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->overview['applicants']) }} total applicants</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Lifetime revenue</div>
            <div class="text-2xl font-bold tabular-nums">{{ number_format((float) $this->overview['revenue'], 2) }} {{ config('billing.currency', 'USD') }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach ([
                'all' => 'All',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'suspended' => 'Suspended',
            ] as $value => $label)
                <button type="button" wire:click="$set('status', '{{ $value }}')" class="rounded-md px-2.5 py-1 text-xs font-medium {{ $this->status === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <flux:input icon="magnifying-glass" type="search" placeholder="Search companies..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
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
                    <th class="px-3 py-2.5 font-medium">Company</th>
                    <th class="px-3 py-2.5 font-medium">Plan</th>
                    <th class="px-3 py-2.5 font-medium">Jobs</th>
                    <th class="px-3 py-2.5 font-medium">Applicants</th>
                    <th class="px-3 py-2.5 font-medium">Members</th>
                    <th class="px-3 py-2.5 font-medium">Revenue</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Joined</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $company)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($company->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $company->id }})" {{ in_array($company->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                <x-company-logo :company="$company" size="sm" />
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $company->name }}</div>
                                    <div class="truncate text-xs text-zinc-500">
                                        {{ $company->owner?->name ?? 'No owner' }}@if ($company->industry) · {{ $company->industry }}@endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 text-xs">{{ $company->plan->label() }}</td>
                        <td class="px-3 py-2.5">
                            {{ $company->jobs_count }}
                            <span class="text-xs text-zinc-400">({{ $company->open_jobs_count }} open)</span>
                            <div class="mt-0.5 text-[11px] text-zinc-500">
                                {{ $company->usedJobPosts() }}/{{ $company->jobPostCredits() }} credits
                            </div>
                        </td>
                        <td class="px-3 py-2.5">{{ $company->applications_count }}</td>
                        <td class="px-3 py-2.5">{{ $company->members_count }}</td>
                        <td class="px-3 py-2.5 font-semibold tabular-nums">
                            {{ number_format((float) ($company->lifetime_revenue ?? 0), 2) }} {{ config('billing.currency', 'USD') }}
                        </td>
                        <td class="px-3 py-2.5">
                            <flux:badge size="sm" inset="top bottom" :color="$this->statusColor($company->status->value)">
                                {{ $company->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-3 py-2.5 text-zinc-500">{{ $company->created_at->toDateString() }}</td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-end gap-1.5">
                                @if ($company->status === \App\Enums\CompanyStatus::Pending)
                                    <flux:button size="sm" variant="primary" wire:click="approve({{ $company->id }})">Approve</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="suspend({{ $company->id }})">Reject</flux:button>
                                @elseif ($company->status === \App\Enums\CompanyStatus::Approved)
                                    <flux:button size="sm" variant="subtle" wire:click="suspend({{ $company->id }})">Suspend</flux:button>
                                @else
                                    <flux:button size="sm" variant="primary" wire:click="approve({{ $company->id }})">Re-approve</flux:button>
                                @endif
                                <flux:button size="sm" variant="subtle" wire:click="grantJobPosts({{ $company->id }}, 1)" title="Add 1 job post credit">+1 credit</flux:button>
                                <flux:button size="sm" variant="subtle" wire:click="grantJobPosts({{ $company->id }}, 3)" title="Add 3 job post credits">+3 credits</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No companies match your filters.
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
