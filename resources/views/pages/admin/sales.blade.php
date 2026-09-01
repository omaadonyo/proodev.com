<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentMethodSetting;
use App\Services\BillingService;
use App\Services\Payments\PaymentMethodSettings;
use App\Support\BillingCurrency;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sales')] class extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public string $search = '';

    public array $selectedIds = [];

    public string $period = 'month';

    public string $currency = 'usd';

    public string $chartStyle = 'line';

    public string $methodSettingsMethod = '';

    public bool $methodEnabled = true;

    public array $methodSettings = [];

    public function mount(): void
    {
        $this->currency = BillingCurrency::codeFor(auth()->user());
    }

    public function setCurrency(string $code): void
    {
        BillingCurrency::setCodeFor(auth()->user(), $code);
        $this->currency = BillingCurrency::codeFor(auth()->user());
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['today', 'week', 'month', 'quarter', 'year', 'all'], true)
            ? $period
            : 'month';
    }

    public function setChartStyle(string $style): void
    {
        $this->chartStyle = in_array($style, ['line', 'area', 'bars'], true) ? $style : 'line';
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function periodRange(): array
    {
        return match ($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [null, null],
        };
    }

    /**
     * Revenue trend buckets for the selected period: hourly for today, daily
     * for the week/month, weekly for the quarter and monthly for the year.
     * Each bucket carries both the paid and the pending revenue so the chart
     * can compare confirmed sales against payments still awaiting confirmation.
     *
     * @return array{unit: string, total: float, paid_total: float, pending_total: float, points: array<int, array{label: string, paid: float, pending: float}>}
     */
    #[Computed]
    public function dailyRevenue(): array
    {
        [$start, $end] = $this->periodRange();

        if ($this->period === 'all') {
            $start = now()->startOfMonth()->subMonths(11);
            $end = now();
        }

        if (! $start) {
            $start = now()->startOfYear();
            $end = now();
        }

        $paid = Payment::where('status', PaymentStatus::Paid)
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<=', $end)
            ->get(['amount', 'paid_at']);
        $pending = Payment::where('status', PaymentStatus::Pending)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->get(['amount', 'created_at']);

        [$unit, $count] = match ($this->period) {
            'today' => ['hour', 24],
            'week' => ['day', 7],
            'month' => ['day', (int) now()->daysInMonth],
            'quarter' => ['week', 14],
            default => ['month', 12],
        };

        return [
            'unit' => $unit === 'hour' ? 'hourly' : 'per '.$unit,
            'total' => (float) $paid->sum('amount') + (float) $pending->sum('amount'),
            'paid_total' => (float) $paid->sum('amount'),
            'pending_total' => (float) $pending->sum('amount'),
            'points' => $this->bucketize($paid, $pending, $start, $end, $unit, $count),
        ];
    }

    /**
     * @param  Collection<int, Payment>  $paid
     * @param  Collection<int, Payment>  $pending
     * @return array<int, array{label: string, paid: float, pending: float}>
     */
    private function bucketize($paid, $pending, CarbonInterface $start, CarbonInterface $end, string $unit, int $count): array
    {
        $buckets = [];

        for ($i = 0; $i < $count; $i++) {
            $step = match ($unit) {
                'hour' => $start->copy()->addHours($i),
                'week' => $start->copy()->addWeeks($i),
                'month' => $start->copy()->addMonths($i),
                default => $start->copy()->addDays($i),
            };

            if ($step->gt($end)) {
                break;
            }

            $buckets[$this->bucketKey($step, $unit)] = [
                'label' => $unit === 'hour' ? $step->format('gA') : $step->format('M j'),
                'paid' => 0.0,
                'pending' => 0.0,
            ];
        }

        foreach ($paid as $payment) {
            $key = $this->bucketKey($payment->paid_at, $unit);
            if (isset($buckets[$key])) {
                $buckets[$key]['paid'] += (float) $payment->amount;
            }
        }

        foreach ($pending as $payment) {
            $key = $this->bucketKey($payment->created_at, $unit);
            if (isset($buckets[$key])) {
                $buckets[$key]['pending'] += (float) $payment->amount;
            }
        }

        return array_values($buckets);
    }

    private function bucketKey(CarbonInterface $date, string $unit): string
    {
        return match ($unit) {
            'hour' => $date->format('Y-m-d H'),
            'week' => $date->format('Y-W'),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }

    #[Computed]
    public function periodStats(): array
    {
        [$start, $end] = $this->periodRange();

        $paid = Payment::where('status', PaymentStatus::Paid)
            ->when($start, fn ($query) => $query->where('paid_at', '>=', $start)->where('paid_at', '<=', $end))
            ->get();

        $refunds = Payment::where('status', PaymentStatus::Refunded)
            ->when($start, fn ($query) => $query->where('paid_at', '>=', $start)->where('paid_at', '<=', $end))
            ->sum('amount');

        $byPurpose = $paid->groupBy(fn (Payment $p) => $p->purpose->label())
            ->sortByDesc(fn ($group) => $group->sum('amount'));
        $byMethod = $paid->groupBy(fn (Payment $p) => $p->payment_method?->label() ?? 'Manual')
            ->sortByDesc(fn ($group) => $group->sum('amount'));

        return [
            'revenue' => (float) $paid->sum('amount'),
            'count' => $paid->count(),
            'avg' => (float) $paid->avg('amount') ?? 0,
            'refunds' => (float) $refunds,
            'top_purpose' => $byPurpose->keys()->first(),
            'top_method' => $byMethod->keys()->first(),
        ];
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Today',
            'week' => 'This week',
            'month' => 'This month',
            'quarter' => 'This quarter',
            'year' => 'This year',
            default => 'All time',
        };
    }

    /**
     * Shared vertical scale for both chart series, so paid and pending share
     * the same maximum.
     *
     * @param  array<int, array{label: string, paid: float, pending: float}>  $points
     */
    public function chartMax(array $points): float
    {
        $max = max(array_map(fn ($p) => max((float) $p['paid'], (float) $p['pending']), $points ?: [['paid' => 0, 'pending' => 0]]));

        return max(1, $max);
    }

    /**
     * X/Y coordinates for one series across the 320x80 chart viewBox.
     *
     * @param  array<int, array{label: string, paid: float, pending: float}>  $points
     * @return array<int, array{x: float, y: float}>
     */
    public function chartSeries(array $points, string $key): array
    {
        $count = max(1, count($points));
        $width = 320;
        $height = 80;
        $pad = 6;
        $max = $this->chartMax($points);

        $coords = [];
        foreach (array_values($points) as $i => $point) {
            $x = $count === 1 ? $width / 2 : round(($i / ($count - 1)) * $width, 2);
            $y = round($height - $pad - (((float) $point[$key] / $max) * ($height - $pad * 2)), 2);
            $coords[] = ['x' => $x, 'y' => $y];
        }

        return $coords;
    }

    /**
     * A smooth SVG path (Catmull-Rom spline converted to cubic Béziers) for
     * the given series, so the revenue trend reads as a curve instead of
     * jagged straight segments.
     *
     * @param  array<int, array{label: string, paid: float, pending: float}>  $points
     */
    public function smoothPath(array $points, string $key): string
    {
        $coords = $this->chartSeries($points, $key);
        $n = count($coords);

        if ($n === 0) {
            return '';
        }

        if ($n === 1) {
            return "M {$coords[0]['x']} {$coords[0]['y']}";
        }

        $d = "M {$coords[0]['x']} {$coords[0]['y']}";
        for ($i = 0; $i < $n - 1; $i++) {
            $p0 = $coords[max(0, $i - 1)];
            $p1 = $coords[$i];
            $p2 = $coords[$i + 1];
            $p3 = $coords[min($n - 1, $i + 2)];
            $c1x = round($p1['x'] + ($p2['x'] - $p0['x']) / 6, 2);
            $c1y = round($p1['y'] + ($p2['y'] - $p0['y']) / 6, 2);
            $c2x = round($p2['x'] - ($p3['x'] - $p1['x']) / 6, 2);
            $c2y = round($p2['y'] - ($p3['y'] - $p1['y']) / 6, 2);
            $d .= " C {$c1x} {$c1y}, {$c2x} {$c2y}, {$p2['x']} {$p2['y']}";
        }

        return $d;
    }

    /**
     * The smooth series path closed down to the baseline, for the area layout.
     *
     * @param  array<int, array{label: string, paid: float, pending: float}>  $points
     */
    public function areaPath(array $points, string $key): string
    {
        $d = $this->smoothPath($points, $key);

        return $d === '' ? '' : $d.' L 320 80 L 0 80 Z';
    }

    /**
     * The peak paid bucket of the trend, shown in the card header.
     *
     * @param  array<int, array{label: string, paid: float, pending: float}>  $points
     * @return array{label: string, paid: float}|null
     */
    public function sparklinePeak(array $points): ?array
    {
        if ($points === []) {
            return null;
        }

        $peak = collect($points)->sortByDesc('paid')->first();

        return ['label' => $peak['label'], 'paid' => (float) $peak['paid']];
    }

    public function periodButtons(): array
    {
        return [
            ['key' => 'today', 'label' => 'Daily'],
            ['key' => 'week', 'label' => 'Weekly'],
            ['key' => 'month', 'label' => 'Monthly'],
            ['key' => 'quarter', 'label' => 'Quarterly'],
            ['key' => 'year', 'label' => 'Yearly'],
            ['key' => 'all', 'label' => 'All time'],
        ];
    }

    public function money(float $amount): string
    {
        return BillingCurrency::format($amount, $this->currency);
    }

    public function markPaid(int $id): void
    {
        $payment = Payment::findOrFail($id);

        app(BillingService::class)->markPaid($payment, auth()->user());

        unset($this->transactions, $this->pending, $this->overview);

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

        unset($this->transactions, $this->pending, $this->overview);

        $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

        Flux::toast(variant: 'warning', text: 'Payment cancelled.');
    }

    public function toggleSelectAll(): void
    {
        $allIds = $this->transactions->pluck('id')->toArray();

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
            Flux::toast(variant: 'warning', text: 'Select at least one transaction to export.');

            return;
        }

        $payments = Payment::with(['user', 'company'])->whereIn('id', $this->selectedIds)->latest()->get();

        $pdf = Pdf::loadView('pdf.sales-export', [
            'payments' => $payments,
            'currency' => $this->currency,
            'total' => (float) $payments->sum('amount'),
        ])->setPaper('a4')->setOption('defaultFont', 'Helvetica');

        $filename = 'sales-export-'.count($this->selectedIds).'-rows-'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function exportSelectedExcel()
    {
        if ($this->selectedIds === []) {
            Flux::toast(variant: 'warning', text: 'Select at least one transaction to export.');

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
            'Content-Disposition' => 'attachment; filename="sales-export-'.count($this->selectedIds).'-rows-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function toggleMethod(): void
    {
        $this->methodEnabled = ! $this->methodEnabled;
    }

    public function editMethod(string $method): void
    {
        $settings = app(PaymentMethodSettings::class);
        $row = PaymentMethodSetting::where('method', $method)->first();

        $this->methodSettingsMethod = $method;
        $this->methodEnabled = $settings->isEnabled(PaymentMethod::tryFrom($method));
        $this->methodSettings = array_merge(
            $settings->for(PaymentMethod::tryFrom($method)),
            $row?->settings ?? [],
        );
    }

    public function saveMethod(): void
    {
        $settings = $this->methodSettings;
        unset($settings['enabled']);

        app(PaymentMethodSettings::class)->update(
            PaymentMethod::tryFrom($this->methodSettingsMethod),
            $this->methodEnabled,
            $settings,
        );

        unset($this->methodRows);

        Flux::toast(variant: 'success', text: 'Payment method settings saved.');

        $this->methodSettingsMethod = '';
        $this->methodSettings = [];
    }

    #[Computed]
    public function overview(): array
    {
        $paid = Payment::where('status', PaymentStatus::Paid);
        $currency = (string) config('billing.currency', 'USD');

        return [
            'currency' => $currency,
            'today' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfDay())->sum('amount'),
            'month' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'year' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfYear())->sum('amount'),
            'lifetime' => (float) (clone $paid)->sum('amount'),
            'count' => (clone $paid)->count(),
            'pending' => Payment::where('status', PaymentStatus::Pending)->count(),
            'refunded' => Payment::where('status', PaymentStatus::Refunded)->sum('amount'),
            'avgOrder' => (float) (clone $paid)->avg('amount') ?? 0,
        ];
    }

    #[Computed]
    public function monthlyRevenue(): array
    {
        $start = now()->subMonths(5)->startOfMonth();

        $paidRows = Payment::where('status', PaymentStatus::Paid)
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $p) => $p->paid_at->format('Y-m'));
        $pendingRows = Payment::where('status', PaymentStatus::Pending)
            ->where('created_at', '>=', $start)
            ->get(['amount', 'created_at'])
            ->groupBy(fn (Payment $p) => $p->created_at->format('Y-m'));

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $months[] = [
                'label' => now()->subMonths($i)->format('M y'),
                'paid' => (float) collect($paidRows[$key] ?? [])->sum('amount'),
                'pending' => (float) collect($pendingRows[$key] ?? [])->sum('amount'),
            ];
        }

        $max = max(array_map(fn ($m) => $m['paid'] + $m['pending'], $months));
        if ($max <= 0) {
            $max = 1;
        }

        return ['max' => $max, 'months' => $months];
    }

    #[Computed]
    public function purposeBreakdown(): array
    {
        return Payment::where('status', PaymentStatus::Paid)
            ->get()
            ->groupBy(fn (Payment $p) => $p->purpose->label())
            ->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->all();
    }

    #[Computed]
    public function methodBreakdown(): array
    {
        return Payment::where('status', PaymentStatus::Paid)
            ->get()
            ->groupBy(fn (Payment $p) => $p->payment_method?->label() ?? 'Manual')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->all();
    }

    public function updatedFilter(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
        unset($this->transactions);
    }

    public function updatedSearch(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
        unset($this->transactions);
    }

    #[Computed]
    public function transactions()
    {
        return Payment::with(['user', 'company'])
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
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
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function pending()
    {
        return Payment::with(['user', 'company'])
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function methodRows()
    {
        return app(PaymentMethodSettings::class)->all();
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
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Sales dashboard</flux:heading>
            <flux:text>Track daily, weekly, monthly and quarterly revenue, with transaction lookup and per-method payment configuration.</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900" title="Display currency">
                @foreach (['usd' => 'USD', 'ugx' => 'UGX'] as $code => $label)
                    <button type="button" wire:click="setCurrency('{{ $code }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->currency === $code ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <a href="{{ route('admin.sales.export') }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200" title="Export the full payment ledger as CSV">
                <flux:icon name="arrow-down-tray" variant="micro" />
                CSV
            </a>
            <a href="{{ route('admin.sales.export.pdf', ['period' => $this->period]) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200" title="Export the sales report as PDF">
                <flux:icon name="document-arrow-down" variant="micro" />
                PDF
            </a>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
                @foreach ($this->periodButtons() as $button)
                    <button type="button" wire:click="setPeriod('{{ $button['key'] }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->period === $button['key'] ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                        {{ $button['label'] }}
                    </button>
                @endforeach
            </div>
            <span class="text-xs text-zinc-500">{{ $this->periodLabel() }} · amounts shown in {{ strtoupper($this->currency) }}</span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Revenue · {{ $this->periodLabel() }}</div>
                <div class="text-2xl font-bold tabular-nums">{{ $this->money($this->periodStats['revenue']) }}</div>
                <div class="mt-1 text-[11px] text-zinc-500">Top purpose: {{ $this->periodStats['top_purpose'] ?? '-' }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Transactions</div>
                <div class="text-2xl font-bold tabular-nums">{{ number_format($this->periodStats['count']) }}</div>
                <div class="mt-1 text-[11px] text-zinc-500">Top method: {{ $this->periodStats['top_method'] ?? '-' }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Average order</div>
                <div class="text-2xl font-bold tabular-nums">{{ $this->money($this->periodStats['avg']) }}</div>
                <div class="mt-1 text-[11px] text-zinc-500">per transaction</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs text-zinc-500">Refunded</div>
                <div class="text-2xl font-bold tabular-nums {{ $this->periodStats['refunds'] > 0 ? 'text-sky-500' : '' }}">{{ $this->money($this->periodStats['refunds']) }}</div>
                <div class="mt-1 text-[11px] text-zinc-500">within period</div>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <flux:heading size="sm">Revenue trend · {{ $this->periodLabel() }}</flux:heading>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-2 rounded-full" style="background:#3750eb"></span>
                        Paid
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-2 rounded-full" style="background:#f59e0b"></span>
                        Pending
                    </span>
                    @if ($this->dailyRevenue['total'] > 0)
                        @php $peak = $this->sparklinePeak($this->dailyRevenue['points']); @endphp
                        <span>
                            {{ ucfirst($this->dailyRevenue['unit']) }}
                            @if ($peak && $peak['paid'] > 0)
                                · peak {{ $peak['label'] }}, {{ $this->money($peak['paid']) }}
                            @endif
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
                @foreach (['line' => 'Line', 'area' => 'Area', 'bars' => 'Bars'] as $key => $label)
                    <button type="button" wire:click="setChartStyle('{{ $key }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->chartStyle === $key ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        @php
            $chartBuckets = collect($this->dailyRevenue['points'])->map(fn ($p) => [
                'label' => $p['label'],
                'paid' => $this->money((float) $p['paid']),
                'pending' => $this->money((float) $p['pending']),
            ])->values()->all();
        @endphp
        @if ($this->dailyRevenue['total'] > 0)
            <div
                class="relative mt-3"
                x-data="{
                    buckets: @js($chartBuckets),
                    hover: false,
                    x: 0,
                    y: 0,
                    index: null,
                    get bucket() { return this.index === null ? null : this.buckets[this.index]; },
                    move(e) {
                        const rect = $refs.chart.getBoundingClientRect();
                        const ratio = (e.clientX - rect.left) / rect.width;
                        this.index = Math.min(this.buckets.length - 1, Math.max(0, Math.floor(ratio * this.buckets.length)));
                        this.x = Math.min(Math.max(e.clientX - rect.left, 80), Math.max(80, rect.width - 80));
                        this.y = e.clientY - rect.top;
                        this.hover = true;
                    },
                    leave() { this.hover = false; this.index = null; }
                }"
                @mousemove="move($event)"
                @mouseleave="leave"
            >
                @php $points = $this->dailyRevenue['points']; @endphp
                @if ($this->chartStyle === 'bars')
                    @php
                        $max = $this->chartMax($points);
                        $count = max(1, count($points));
                        $slot = 320 / $count;
                        $barW = round($slot * 0.62, 2);
                        $scale = 68 / $max;
                        $base = 74;
                    @endphp
                    <svg x-ref="chart" viewBox="0 0 320 80" preserveAspectRatio="none" class="h-28 w-full" role="img" aria-label="Revenue trend bars for {{ $this->periodLabel() }}">
                        @foreach ($points as $i => $point)
                            @php
                                $x = round($i * $slot + ($slot - $barW) / 2, 2);
                                $paidH = max(0, (float) $point['paid'] * $scale);
                                $pendingH = max(0, (float) $point['pending'] * $scale);
                            @endphp
                            @if ($pendingH > 0)
                                <rect x="{{ $x }}" y="{{ round($base - $paidH - $pendingH, 2) }}" width="{{ $barW }}" height="{{ max(1.5, round($pendingH, 2)) }}" rx="1.5" fill="#f59e0b" />
                            @endif
                            @if ($paidH > 0)
                                <rect x="{{ $x }}" y="{{ round($base - $paidH, 2) }}" width="{{ $barW }}" height="{{ max(1.5, round($paidH, 2)) }}" rx="1.5" fill="#3750eb" />
                            @endif
                        @endforeach
                    </svg>
                @else
                    <svg x-ref="chart" viewBox="0 0 320 80" preserveAspectRatio="none" class="h-28 w-full" role="img" aria-label="Revenue trend for {{ $this->periodLabel() }}">
                        @if ($this->chartStyle === 'area')
                            <path d="{{ $this->areaPath($points, 'paid') }}" fill="#3750eb" opacity="0.12" />
                        @endif
                        <path d="{{ $this->smoothPath($points, 'paid') }}" fill="none" stroke="#3750eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="{{ $this->smoothPath($points, 'pending') }}" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                @endif
                <div
                    x-show="hover && bucket"
                    x-cloak
                    class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                    :style="`left:${x}px; top:${y - 8}px`"
                >
                    <div class="mb-1 font-semibold text-zinc-900 dark:text-white" x-text="bucket.label"></div>
                    <div class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-300">
                        <span class="size-2 rounded-full" style="background:#3750eb"></span>
                        Paid: <span class="font-semibold tabular-nums" x-text="bucket.paid"></span>
                    </div>
                    <div class="mt-0.5 flex items-center gap-1.5 text-zinc-600 dark:text-zinc-300">
                        <span class="size-2 rounded-full" style="background:#f59e0b"></span>
                        Pending: <span class="font-semibold tabular-nums" x-text="bucket.pending"></span>
                    </div>
                </div>
            </div>
        @else
            <p class="mt-3 text-sm text-zinc-500">No revenue recorded in this period yet.</p>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">Revenue, last 6 months</flux:heading>
                <div class="flex items-center gap-3 text-[11px] text-zinc-500">
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full" style="background:#3750eb"></span> Paid</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full" style="background:#f59e0b"></span> Pending</span>
                </div>
            </div>
            <div class="mt-4 flex h-40 items-end gap-3">
                @foreach ($this->monthlyRevenue['months'] as $bar)
                    @php
                        $totalH = max(4, (($bar['paid'] + $bar['pending']) / $this->monthlyRevenue['max']) * 120);
                        $paidH = max(0, ($bar['paid'] / $this->monthlyRevenue['max']) * 120);
                        $pendingH = max(0, ($bar['pending'] / $this->monthlyRevenue['max']) * 120);
                    @endphp
                    <div class="flex flex-1 flex-col items-center gap-1.5">
                        <div class="relative w-full rounded-t-md" style="height: {{ $totalH }}px">
                            @if ($pendingH > 0)
                                <div class="absolute inset-x-0 top-0 rounded-t-md" style="height: {{ $pendingH }}px; background:#f59e0b"></div>
                            @endif
                            @if ($paidH > 0)
                                <div class="absolute inset-x-0 bottom-0" style="height: {{ $paidH }}px; background:#3750eb"></div>
                            @endif
                        </div>
                        <span class="text-[10px] text-zinc-500">{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Revenue by purpose</flux:heading>
                <div class="mt-3 grid gap-2 text-sm">
                    @forelse ($this->purposeBreakdown as $label => $row)
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
                            <span class="text-xs text-zinc-500">{{ $row['count'] }} tx</span>
                            <span class="font-semibold tabular-nums">{{ $this->money($row['amount']) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No paid transactions yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Revenue by method</flux:heading>
                <div class="mt-3 grid gap-2 text-sm">
                    @forelse ($this->methodBreakdown as $label => $row)
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
                            <span class="font-semibold tabular-nums">{{ $this->money($row['amount']) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No paid transactions yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="sm">Payment methods</flux:heading>
            <span class="text-xs text-zinc-500">{{ count($this->methodRows) }} configured</span>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach ($this->methodRows as $row)
                <button type="button" wire:click="editMethod('{{ $row['method']->value }}')" class="rounded-lg border border-zinc-200 p-3 text-left transition hover:border-accent dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <x-payment-method-logo :method="$row['method']" class="shrink-0" />
                        <div class="flex-1">
                            <div class="font-semibold">{{ $row['method']->label() }}</div>
                            @if (! $row['enabled'])
                                <div class="text-xs text-zinc-500">Disabled</div>
                            @elseif (! $row['configured'])
                                <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Enabled · add details to go live</div>
                            @else
                                <div class="text-xs text-emerald-600 dark:text-emerald-400">Enabled · live</div>
                            @endif
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    @if ($this->methodSettingsMethod)
        <div>
            <flux:heading size="sm">Configure {{ \App\Enums\PaymentMethod::tryFrom($this->methodSettingsMethod)?->label() }}</flux:heading>
            <div class="mt-4 grid gap-4">
                <flux:switch :checked="$this->methodEnabled" wire:click="toggleMethod" />
                @foreach ($this->methodSettings as $key => $value)
                    @if (in_array($key, ['enabled'], true))
                        @continue
                    @endif
                    <flux:field>
                        <flux:label>{{ ucwords(str_replace('_', ' ', $key)) }}</flux:label>
                        <flux:input wire:model="methodSettings.{{ $key }}" :value="$value" />
                    </flux:field>
                @endforeach
                <div class="flex gap-2">
                    <flux:button variant="primary" wire:click="saveMethod">Save settings</flux:button>
                    <flux:button variant="subtle" wire:click="$set('methodSettingsMethod', '')">Cancel</flux:button>
                </div>
            </div>
        </div>
    @endif

    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="sm">All transactions</flux:heading>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
                    @foreach ([
                        'all' => 'All',
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ] as $value => $label)
                        <button type="button" wire:click="$set('filter', '{{ $value }}')" class="rounded px-2.5 py-1 text-xs font-medium {{ $this->filter === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <flux:input icon="magnifying-glass" type="search" placeholder="Search transactions..." wire:model.live.debounce.300ms="search" class="w-full sm:w-72" />
                @if (count($this->selectedIds) > 0)
                    <span class="text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
                    <a href="{{ route('admin.sales.export') }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="arrow-down-tray" variant="micro" />
                        CSV
                    </a>
                    <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="document-arrow-down" variant="micro" />
                        PDF
                    </button>
                    <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="table-cells" variant="micro" />
                        Excel
                    </button>
                @else
                    <a href="{{ route('admin.sales.export') }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200" title="Export the full payment ledger as CSV">
                        <flux:icon name="arrow-down-tray" variant="micro" />
                        Export CSV
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="px-3 py-2.5 font-medium">
                            <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->transactions->count() && $this->transactions->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
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
                    @forelse ($this->transactions as $payment)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($payment->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                            <td class="px-3 py-2.5">
                                <input type="checkbox" wire:click="toggleSelect({{ $payment->id }})" {{ in_array($payment->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                            </td>
                            <td class="px-3 py-2.5 font-mono text-xs">{{ $payment->invoiceNumber() }}</td>
                            <td class="px-3 py-2.5">
                                {{ $payment->user?->name ?? $payment->company?->name ?? '-' }}
                                @if ($payment->company)
                                    <span class="block text-xs text-zinc-500">{{ $payment->company->name }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">{{ $payment->purpose->label() }}</td>
                            <td class="px-3 py-2.5">{{ $payment->payment_method?->label() ?? 'Manual' }}</td>
                            <td class="px-3 py-2.5 font-semibold tabular-nums">{{ $this->money((float) $payment->amount) }}</td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <flux:badge size="sm" inset="top bottom" :color="$this->statusColor($payment->status->value)">
                                        {{ $payment->status->label() }}
                                    </flux:badge>
                                    @if ($payment->status === \App\Enums\PaymentStatus::Pending && $payment->confirmedByCustomer())
                                        <flux:badge size="sm" inset="top bottom" color="amber" title="Customer confirmed payment {{ $payment->customer_confirmed_at?->diffForHumans() }}">
                                            Customer confirmed
                                        </flux:badge>
                                    @endif
                                </div>
                                @if ($payment->status === \App\Enums\PaymentStatus::Pending && $payment->confirmedByCustomer())
                                    <p class="mt-1 text-[11px] text-zinc-500" title="The receipt is only generated once you confirm this payment.">
                                        No invoice generated yet, the receipt is sent on confirmation.
                                    </p>
                                @endif
                                @if ($payment->status === \App\Enums\PaymentStatus::Paid && $payment->invoiceEmailed())
                                    <p class="mt-1 text-[11px] text-zinc-500" title="Receipt emailed {{ $payment->invoice_emailed_at->format('M j, Y g:i A') }}">
                                        Receipt emailed {{ $payment->invoice_emailed_at->diffForHumans() }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $payment->paid_at?->toDateString() ?? $payment->created_at->toDateString() }}</td>
                            <td class="px-3 py-2.5">
                                @if ($payment->status === \App\Enums\PaymentStatus::Pending)
                                    <div class="flex justify-end gap-1.5">
                                        <flux:button size="sm" variant="primary" wire:click="markPaid({{ $payment->id }})">Confirm</flux:button>
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
                                No transactions {{ $this->filter !== 'all' ? 'with this status' : 'yet' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->transactions->hasPages())
            <div class="mt-4">
                {{ $this->transactions->links() }}
            </div>
        @endif
    </div>
</div>
