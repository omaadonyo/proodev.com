<?php

use App\Enums\PaymentStatus;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Billing')] class extends Component
{
    use ExportsSelectedRows;

    public string $status = 'all';

    public string $search = '';

    #[Computed]
    public function payments()
    {
        $ownedCompanies = auth()->user()->companiesOwned()->pluck('id');

        return Payment::with(['user', 'company'])
            ->where(function ($query) use ($ownedCompanies) {
                $query->where('user_id', auth()->id())
                    ->orWhereIn('company_id', $ownedCompanies);
            })
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('id', 'like', '%'.trim($this->search).'%')
                        ->orWhere('reference', 'like', '%'.trim($this->search).'%')
                        ->orWhere('gateway_reference', 'like', '%'.trim($this->search).'%');
                });
            })
            ->orderByRaw('case when status = "pending" then 0 else 1 end')
            ->latest('paid_at')
            ->latest('created_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function overview(): array
    {
        $payments = Payment::query()
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhereIn('company_id', auth()->user()->companiesOwned()->pluck('id'));
            });

        $paid = (clone $payments)->where('status', PaymentStatus::Paid);

        return [
            'total' => (float) (clone $paid)->sum('amount'),
            'count' => (clone $paid)->count(),
            'pending' => (clone $payments)->where('status', PaymentStatus::Pending)->count(),
            'month' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'currency' => (string) config('billing.currency', 'USD'),
        ];
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'emerald',
            'pending' => 'amber',
            'refunded' => 'sky',
            default => 'zinc',
        };
    }

    protected function selectableIds(): array
    {
        return $this->payments->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $payments = Payment::with(['user', 'company'])
            ->whereIn('id', $this->selectedIds)
            ->orderByRaw('case when status = "pending" then 0 else 1 end')
            ->latest('paid_at')
            ->get();

        $rows = $payments->map(fn (Payment $p) => [
            $p->invoiceNumber(),
            $p->purpose->label(),
            $p->payment_method?->label() ?? 'Manual',
            number_format((float) $p->amount, 2, '.', ''),
            $p->currency,
            $p->status->label(),
            ($p->paid_at ?? $p->created_at)->toDateTimeString(),
        ])->all();

        return [
            ['Invoice', 'Purpose', 'Method', 'Amount', 'Currency', 'Status', 'Date'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected transactions';
    }

    protected function exportBasename(): string
    {
        return 'billing';
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Billing history</flux:heading>
            <flux:text>Every payment across verification, credits, auto-scan and subscriptions — download any invoice as a PDF or email yourself a copy.</flux:text>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Total paid</div>
                <div class="text-2xl font-bold tabular-nums">{{ number_format($this->overview['total'], 2) }} {{ $this->overview['currency'] }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Invoices</div>
                <div class="text-2xl font-bold tabular-nums">{{ number_format($this->overview['count']) }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Spent this month</div>
                <div class="text-2xl font-bold tabular-nums">{{ number_format($this->overview['month'], 2) }} {{ $this->overview['currency'] }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Awaiting confirmation</div>
                <div class="text-2xl font-bold tabular-nums {{ $this->overview['pending'] ? 'text-amber-500' : '' }}">{{ number_format($this->overview['pending']) }}</div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
                @foreach (['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'refunded' => 'Refunded', 'cancelled' => 'Cancelled'] as $value => $label)
                    <button type="button" wire:click="$set('status', '{{ $value }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->status === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:input icon="magnifying-glass" type="search" placeholder="Search reference or invoice…" wire:model.live.debounce.300ms="search" class="w-full sm:w-64" />
                <a href="{{ route('billing.export.csv') }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200" title="Export billing history as CSV">
                    <flux:icon name="arrow-down-tray" variant="micro" />
                    CSV
                </a>
                <a href="{{ route('billing.export.pdf') }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200" title="Export billing history as PDF">
                    <flux:icon name="document-arrow-down" variant="micro" />
                    PDF
                </a>
                @if (count($this->selectedIds) > 0)
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
                    <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="document-arrow-down" variant="micro" />
                        Selected PDF
                    </button>
                    <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="table-cells" variant="micro" />
                        Selected Excel
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="w-8 px-3 py-2.5 font-medium">
                            <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->payments->count() && $this->payments->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </th>
                        <th class="px-3 py-2.5 font-medium">Invoice</th>
                        <th class="px-3 py-2.5 font-medium">Purpose</th>
                        <th class="px-3 py-2.5 font-medium">Method</th>
                        <th class="px-3 py-2.5 font-medium">Amount</th>
                        <th class="px-3 py-2.5 font-medium">Status</th>
                        <th class="px-3 py-2.5 font-medium">Date</th>
                        <th class="px-3 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->payments as $payment)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($payment->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                            <td class="px-3 py-2.5">
                                <input type="checkbox" wire:click="toggleSelect({{ $payment->id }})" {{ in_array($payment->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                            </td>
                            <td class="px-3 py-2.5 font-mono text-xs">{{ $payment->invoiceNumber() }}</td>
                            <td class="px-3 py-2.5">
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $payment->purpose->label() }}</span>
                                <div class="mt-0.5 max-w-56 truncate text-xs text-zinc-500" title="{{ $payment->lineDescription() }}">{{ $payment->lineDescription() }}</div>
                            </td>
                            <td class="px-3 py-2.5">{{ $payment->payment_method?->label() ?? 'Manual' }}</td>
                            <td class="px-3 py-2.5 font-semibold tabular-nums">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                            <td class="px-3 py-2.5">
                                <flux:badge size="sm" inset="top bottom" :color="$this->statusColor($payment->status->value)">
                                    {{ $payment->status->label() }}
                                </flux:badge>
                                @if ($payment->status === \App\Enums\PaymentStatus::Pending && $payment->confirmedByCustomer())
                                    <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-400/10 px-2 py-0.5 text-[10px] font-medium text-amber-600 dark:text-amber-400" title="Submitted {{ $payment->customer_confirmed_at?->diffForHumans() }} — an admin is checking the transfer.">
                                        <flux:icon name="clock" variant="micro" />
                                        We're verifying your payment
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $payment->paid_at?->toDateString() ?? $payment->created_at->toDateString() }}</td>
                            <td class="px-3 py-2.5">
                                @if ($payment->status !== \App\Enums\PaymentStatus::Pending)
                                    <div class="flex justify-end gap-1.5">
                                        <flux:button size="xs" variant="ghost" :href="route('invoices.show', $payment)" target="_blank" title="Open printable invoice">
                                            <flux:icon name="arrow-down-tray" variant="micro" />
                                            Download
                                        </flux:button>
                                        <form method="POST" action="{{ route('invoices.email', $payment) }}" class="inline-flex">
                                            @csrf
                                            <flux:button size="xs" variant="ghost" type="submit" title="Email a copy of this invoice">
                                                <flux:icon name="envelope" variant="micro" />
                                                Email
                                            </flux:button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">Awaiting confirmation</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-10 text-center">
                                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                                    <flux:icon name="receipt-percent" />
                                </div>
                                <flux:heading>No payments yet</flux:heading>
                                <flux:text class="mt-1">Your invoices will appear here once you make a purchase — verification, credits, auto-scan or a company plan.</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->overview['pending'] > 0)
            <div class="rounded-xl border border-amber-300/40 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                <div class="font-semibold">You have {{ $this->overview['pending'] }} checkout{{ $this->overview['pending'] === 1 ? '' : 's' }} awaiting confirmation</div>
                <div class="mt-1 text-xs">Once an admin confirms the payment, the invoice becomes downloadable and your purchase activates.</div>
            </div>
        @endif

        <footer class="mt-10 border-t border-zinc-200 pt-5 text-center text-xs leading-relaxed text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
            <div class="font-semibold text-zinc-600 dark:text-zinc-300">
                {{ str_replace(['https://', 'http://'], '', config('billing.seller.website')) }} | {{ config('billing.seller.name') }}
            </div>
            {{ config('billing.seller.address') }}, {{ config('billing.seller.city') }} - {{ config('billing.seller.country') }}<br>
            Tel: {{ config('billing.seller.phone') }} · {{ config('billing.seller.email') }} · Tax ID {{ config('billing.seller.tax_id') }}
        </footer>
    </div>
</div>
