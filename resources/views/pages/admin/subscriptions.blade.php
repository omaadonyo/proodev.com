<?php

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\Company;
use App\Models\Payment;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Subscriptions')] class extends Component {
    use ExportsSelectedRows;

    public string $search = '';
    public string $planFilter = 'all';

    public function markPaid(int $id): void
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== PaymentStatus::Pending) {
            Flux::toast(variant: 'warning', text: 'Only pending payments can be confirmed.');

            return;
        }

        app(\App\Services\BillingService::class)->markPaid($payment, auth()->user());

        unset($this->summary, $this->companies, $this->payments);

        Flux::toast(variant: 'success', text: 'Payment confirmed and subscription activated.');
    }

    public function cancel(int $id): void
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== PaymentStatus::Pending) {
            Flux::toast(variant: 'warning', text: 'Only pending payments can be cancelled.');

            return;
        }

        $payment->update(['status' => PaymentStatus::Cancelled]);

        unset($this->summary, $this->companies, $this->payments);

        Flux::toast(variant: 'warning', text: 'Payment cancelled.');
    }

    #[Computed]
    public function summary(): array
    {
        $paid = Payment::where('purpose', PaymentPurpose::Subscription)
            ->where('status', PaymentStatus::Paid);

        return [
            'active_companies' => Company::where('status', CompanyStatus::Approved)
                ->where('plan', '!=', CompanyPlan::Trial)
                ->count(),
            'pending' => Payment::where('purpose', PaymentPurpose::Subscription)
                ->where('status', PaymentStatus::Pending)
                ->count(),
            'confirmed' => (clone $paid)->count(),
            'lifetime_revenue' => (float) $paid->sum('amount'),
            'mrr' => (float) Company::where('status', CompanyStatus::Approved)
                ->where('plan', '!=', CompanyPlan::Trial)
                ->get()
                ->sum(fn (Company $company) => $company->plan->monthlyPrice()),
        ];
    }

    #[Computed]
    public function companies()
    {
        return Company::query()
            ->with(['owner', 'members'])
            ->where('status', CompanyStatus::Approved)
            ->where('plan', '!=', CompanyPlan::Trial)
            ->when($this->planFilter !== 'all', fn ($query) => $query->where('plan', $this->planFilter))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', '%'.trim($this->search).'%')
                    ->orWhereHas('owner', fn ($query) => $query
                        ->where('name', 'like', '%'.trim($this->search).'%')
                        ->orWhere('email', 'like', '%'.trim($this->search).'%'))
                    ->orWhere('slug', 'like', '%'.trim($this->search).'%');
            }))
            ->latest()
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function payments()
    {
        return Payment::with(['user', 'company'])
            ->where('purpose', PaymentPurpose::Subscription)
            ->when(trim($this->search) !== '', fn ($query) => $query->whereHas('company', fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%')))
            ->latest()
            ->limit(100)
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->selectedIds = [];
    }

    public function updatedPlanFilter(): void
    {
        $this->selectedIds = [];
    }

    protected function selectableIds(): array
    {
        return $this->companies->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $selected = Company::with(['owner'])
            ->whereIn('id', $this->selectedIds)
            ->orderByDesc('created_at')
            ->get();

        $rows = $selected->map(fn (Company $company) => [
            $company->name,
            $company->plan->label(),
            $company->plan->monthlyPrice() > 0 ? '$'.number_format((float) $company->plan->monthlyPrice(), 2).'/mo' : '-',
            $company->owner?->name ?? '-',
            $company->owner?->email ?? '-',
            (string) $company->members()->count(),
            $this->nextBilling($company)?->toDateString() ?? '-',
        ])->all();

        return [
            ['Company', 'Plan', 'Price', 'Owner', 'Owner email', 'Members', 'Next billing'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected company subscriptions';
    }

    protected function exportBasename(): string
    {
        return 'subscriptions';
    }

    public function nextBilling(Company $company): ?Carbon
    {
        $latest = $company->payments()
            ->where('purpose', PaymentPurpose::Subscription)
            ->where('status', PaymentStatus::Paid)
            ->whereNotNull('paid_at')
            ->latest('paid_at')
            ->value('paid_at');

        return $latest ? Carbon::parse($latest)->addMonth() : null;
    }

    public function planOptions(): array
    {
        return [
            'all' => 'All plans',
            CompanyPlan::Recruiter->value => CompanyPlan::Recruiter->label(),
            CompanyPlan::Intelligence->value => CompanyPlan::Intelligence->label(),
        ];
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Subscriptions</flux:heading>
        <flux:text>Manage company plans, lookup subscription billing and confirm renewals.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold">{{ $this->summary['active_companies'] }}</div>
            <div class="text-xs text-zinc-500">Active subscriptions</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold">{{ $this->summary['pending'] }}</div>
            <div class="text-xs text-zinc-500">Pending payments</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold">{{ $this->summary['confirmed'] }}</div>
            <div class="text-xs text-zinc-500">Confirmed payments</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold tabular-nums">${{ number_format($this->summary['mrr'], 0) }}</div>
            <div class="text-xs text-zinc-500">Monthly recurring revenue</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold tabular-nums">${{ number_format($this->summary['lifetime_revenue'], 2) }}</div>
            <div class="text-xs text-zinc-500">Lifetime subscription revenue</div>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="sm">Company subscriptions</flux:heading>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
                    @foreach ($this->planOptions() as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('planFilter', '{{ $value }}')"
                            class="rounded-md px-2.5 py-1 text-xs font-medium {{ $this->planFilter === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <div class="w-64">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search company, owner or email…" />
                </div>
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
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="w-8 px-3 py-2.5 font-medium">
                            <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->companies->count() && $this->companies->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </th>
                        <th class="px-3 py-2.5 font-medium">Company</th>
                        <th class="px-3 py-2.5 font-medium">Plan</th>
                        <th class="px-3 py-2.5 font-medium">Price</th>
                        <th class="px-3 py-2.5 font-medium">Owner</th>
                        <th class="px-3 py-2.5 font-medium">Members</th>
                        <th class="px-3 py-2.5 font-medium">Next billing</th>
                        <th class="px-3 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->companies as $company)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($company->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                            <td class="px-3 py-2.5">
                                <input type="checkbox" wire:click="toggleSelect({{ $company->id }})" {{ in_array($company->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium">{{ $company->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $company->industry ?: 'Company' }} · {{ $company->openJobsCount() }} open jobs</div>
                            </td>
                            <td class="px-3 py-2.5">
                                <flux:badge size="sm" inset="top bottom" color="{{ $company->plan === \App\Enums\CompanyPlan::Intelligence ? 'indigo' : 'emerald' }}">
                                    {{ $company->plan->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-3 py-2.5 tabular-nums">
                                @if ($company->plan->monthlyPrice() > 0)
                                    ${{ $company->plan->monthlyPrice() }}/mo
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <div>{{ $company->owner?->name ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">{{ $company->owner?->email }}</div>
                            </td>
                            <td class="px-3 py-2.5 tabular-nums">{{ $company->members()->count() }}</td>
                            <td class="px-3 py-2.5 text-zinc-500">
                                {{ $this->nextBilling($company)?->toDateString() ?? '-' }}
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-end gap-1.5">
                                    <flux:button size="sm" variant="subtle" :href="route('companies.show', $company)" target="_blank">
                                        View
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No subscriptions match your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="sm">Subscription payments</flux:heading>
            <flux:text>Invoices and renewals, with pending ones actionable.</flux:text>
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="px-3 py-2.5 font-medium">Payment</th>
                        <th class="px-3 py-2.5 font-medium">Company</th>
                        <th class="px-3 py-2.5 font-medium">Payer</th>
                        <th class="px-3 py-2.5 font-medium">Amount</th>
                        <th class="px-3 py-2.5 font-medium">Status</th>
                        <th class="px-3 py-2.5 font-medium">Date</th>
                        <th class="px-3 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->payments as $payment)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="px-3 py-2.5 font-mono text-xs">#{{ $payment->id }}</td>
                            <td class="px-3 py-2.5">{{ $payment->company?->name ?? '-' }}</td>
                            <td class="px-3 py-2.5">{{ $payment->user?->name ?? '-' }}</td>
                            <td class="px-3 py-2.5 tabular-nums">
                                {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}
                            </td>
                            <td class="px-3 py-2.5">
                                <flux:badge size="sm" inset="top bottom" color="{{ $payment->status === \App\Enums\PaymentStatus::Paid ? 'emerald' : ($payment->status === \App\Enums\PaymentStatus::Pending ? 'amber' : 'zinc') }}">
                                    {{ $payment->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $payment->paid_at?->toDateString() ?? $payment->created_at->toDateString() }}</td>
                            <td class="px-3 py-2.5">
                                @if ($payment->status === \App\Enums\PaymentStatus::Pending)
                                    <div class="flex justify-end gap-1.5">
                                        <flux:button size="sm" variant="primary" wire:click="markPaid({{ $payment->id }})">Confirm</flux:button>
                                        <flux:button size="sm" variant="subtle" wire:click="cancel({{ $payment->id }})">Cancel</flux:button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No subscription payments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
