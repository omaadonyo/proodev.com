<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\BillingService;
use App\Support\BillingCurrency;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Payments')] class extends Component
{
    use WithPagination;

    public string $status = 'all';

    public string $search = '';

    public array $selectedIds = [];

    public string $currency = 'usd';

    public function mount(): void
    {
        $this->currency = BillingCurrency::codeFor(auth()->user());
    }

    public function setCurrency(string $code): void
    {
        BillingCurrency::setCodeFor(auth()->user(), $code);
        $this->currency = BillingCurrency::codeFor(auth()->user());
    }

    public function money(float $amount): string
    {
        return BillingCurrency::format($amount, $this->currency);
    }

    public function markPaid(int $id): void
    {
        $payment = Payment::findOrFail($id);

        app(BillingService::class)->markPaid($payment, auth()->user());

        unset($this->rows, $this->overview);

        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

        Flux::toast(variant: 'success', text: 'Payment confirmed and fulfilled.');
    }

    public function cancel(int $id): void
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== PaymentStatus::Pending) {
            Flux::toast(variant: 'warning', text: 'Only pending payments can be cancelled.');

            return;
        }

        $payment->update(['status' => PaymentStatus::Cancelled]);

        unset($this->rows, $this->overview);

        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

        Flux::toast(variant: 'warning', text: 'Payment cancelled.');
    }

    public function toggleSelectAll(): void
    {
        $allIds = $this->rows->pluck('id')->toArray();

        if (count($this->selectedIds) === count($allIds)) {
            $this->selectedIds = [];
        } else {
            $this->selectedIds = $allIds;
        }
    }

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function exportSelectedPdf()
    {
        if ($this->selectedIds === []) {
            Flux::toast(variant: 'warning', text: 'Select at least one payment to export.');

            return;
        }

        $payments = Payment::with(['user', 'company'])->whereIn('id', $this->selectedIds)->latest()->get();

        $pdf = Pdf::loadView('pdf.sales-export', [
            'payments' => $payments,
            'currency' => $this->currency,
            'total' => (float) $payments->sum('amount'),
        ])->setPaper('a4')->setOption('defaultFont', 'Helvetica');

        $filename = 'payments-export-'.count($this->selectedIds).'-rows-'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function exportSelectedExcel()
    {
        if ($this->selectedIds === []) {
            Flux::toast(variant: 'warning', text: 'Select at least one payment to export.');

            return;
        }

        $payments = Payment::with(['user', 'company'])->whereIn('id', $this->selectedIds)->latest()->get();

        $headers = ['Invoice', 'Customer', 'Purpose', 'Method', 'Amount', 'Currency', 'Status', 'Date'];

        $callback = function () use ($payments, $headers) {
            $handle = fopen('php://temp', 'r+');
            fputcsv($handle, $headers);

            foreach ($payments as $p) {
                $customer = $p->company?->name;
                if ($p->user && $customer !== $p->user->name) {
                    $customer = $customer ? $customer.' ('.$p->user->name.')' : $p->user->name;
                }
                fputcsv($handle, [
                    $p->invoiceNumber(),
                    $customer ?? '-',
                    $p->purpose->label(),
                    $p->payment_method?->label() ?? 'Manual',
                    number_format((float) $p->amount, 2, '.', ''),
                    $p->currency,
                    $p->status->label(),
                    ($p->paid_at ?? $p->created_at)->toDateTimeString(),
                ]);
            }

            rewind($handle);
            echo stream_get_contents($handle);
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="payments-export-'.count($this->selectedIds).'-rows-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    #[Computed]
    public function overview(): array
    {
        $paid = Payment::where('status', PaymentStatus::Paid);
        $currency = (string) config('billing.currency', 'USD');

        return [
            'currency' => $currency,
            'pending' => Payment::where('status', PaymentStatus::Pending)->count(),
            'confirmed' => (clone $paid)->count(),
            'today' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfDay())->sum('amount'),
            'lifetime' => (float) (clone $paid)->sum('amount'),
        ];
    }

    public function updatedStatus(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
        unset($this->rows);
    }

    public function updatedSearch(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
        unset($this->rows);
    }

    #[Computed]
    public function rows()
    {
        return Payment::with(['user', 'company'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($query) {
                $query->where(function ($query) {
                    $query->whereHas('user', fn ($query) => $query
                        ->where('name', 'like', '%'.trim($this->search).'%')
                        ->orWhere('email', 'like', '%'.trim($this->search).'%'))
                        ->orWhereHas('company', fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%'))
                        ->orWhere('reference', 'like', '%'.trim($this->search).'%')
                        ->orWhere('id', 'like', '%'.trim($this->search).'%');
                });
            })
            ->orderByRaw('case when status = "pending" then 0 else 1 end')
            ->latest('paid_at')
            ->paginate(25);
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

    public function dueAt(Payment $payment)
    {
        return $payment->status === PaymentStatus::Paid ? $payment->paid_at : $payment->created_at;
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Payments</flux:heading>
            <flux:text>Confirm manual checkouts, review transactions and reconcile the ledger.</flux:text>
        </div>
        <div class="flex gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900" title="Display currency">
            @foreach (['usd' => 'USD', 'ugx' => 'UGX'] as $code => $label)
                <button type="button" wire:click="setCurrency('{{ $code }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->currency === $code ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Pending confirmations</div>
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->overview['pending']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Confirmed payments</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['confirmed']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Collected today</div>
            <div class="text-2xl font-bold tabular-nums">{{ $this->money($this->overview['today']) }}</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-xs text-zinc-500">Lifetime revenue</div>
            <div class="text-2xl font-bold tabular-nums">{{ $this->money($this->overview['lifetime']) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
            @foreach ([
                'all' => 'All',
                'pending' => 'Pending',
                'paid' => 'Paid',
                'refunded' => 'Refunded',
                'cancelled' => 'Cancelled',
            ] as $value => $label)
                <button type="button" wire:click="$set('status', '{{ $value }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->status === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <flux:input icon="magnifying-glass" type="search" placeholder="Search customer, company or reference..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
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
            <thead>                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="px-3 py-2.5 font-medium">
                            <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->rows->count() && $this->rows->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </th>
                        <th class="px-3 py-2.5 font-medium">Payment</th>
                    <th class="px-3 py-2.5 font-medium">Customer</th>
                    <th class="px-3 py-2.5 font-medium">Purpose</th>
                    <th class="px-3 py-2.5 font-medium">Method</th>
                    <th class="px-3 py-2.5 font-medium">Amount</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Date</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $payment)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($payment->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $payment->id }})" {{ in_array($payment->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5 font-mono text-xs">{{ $payment->invoiceNumber() }}</td>
                        <td class="px-3 py-2.5">
                            {{ $payment->user?->name ?? $payment->company?->name ?? '-' }}
                            @if ($payment->company && $payment->company->name !== $payment->user?->name)
                                <span class="block text-xs text-zinc-500">{{ $payment->company->name }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5">{{ $payment->purpose->label() }}</td>
                        <td class="px-3 py-2.5">{{ $payment->payment_method?->label() ?? 'Manual' }}</td>
                        <td class="px-3 py-2.5 font-semibold tabular-nums">{{ $this->money((float) $payment->amount) }}</td>
                        <td class="px-3 py-2.5">
                            <flux:badge size="sm" inset="top bottom" :color="$this->statusColor($payment->status->value)">
                                {{ $payment->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-3 py-2.5 text-zinc-500">{{ $this->dueAt($payment)?->toDateString() }}</td>
                        <td class="px-3 py-2.5">
                            @if ($payment->status === \App\Enums\PaymentStatus::Pending)
                                <div class="flex justify-end gap-1.5">
                                    <flux:button size="sm" variant="primary" wire:click="markPaid({{ $payment->id }})">Mark paid</flux:button>
                                    <flux:button size="sm" variant="subtle" wire:click="cancel({{ $payment->id }})">Cancel</flux:button>
                                </div>
                            @else
                                <div class="flex justify-end gap-1.5">
                                    <flux:button size="sm" variant="ghost" :href="route('invoices.show', $payment)" target="_blank" title="Open printable invoice">
                                        <flux:icon name="arrow-down-tray" variant="micro" />
                                        Invoice
                                    </flux:button>
                                    <form method="POST" action="{{ route('invoices.email', $payment) }}" class="inline-flex">
                                        @csrf
                                        <flux:button size="sm" variant="ghost" type="submit" title="Email a copy to the customer">
                                            <flux:icon name="envelope" variant="micro" />
                                            Email
                                        </flux:button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No payments match your filters.
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
