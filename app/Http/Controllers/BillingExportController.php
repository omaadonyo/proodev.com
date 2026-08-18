<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\BillingCurrency;
use App\Support\BillingHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Streams the authenticated user's billing history as a downloadable CSV
 * spreadsheet or PDF document. Only the user's own payments and payments
 * on companies they own are ever included.
 */
class BillingExportController extends Controller
{
    public function csv(Request $request, BillingHistory $history)
    {
        $payments = $history->forUser(Auth::user());

        $csv = $history->csv($payments);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="billing-history-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function pdf(Request $request, BillingHistory $history)
    {
        $payments = $history->forUser(Auth::user());
        $pdf = $history->pdf($payments, Auth::user());

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="billing-history-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    /**
     * Admin-only: export the full platform payment ledger as CSV.
     */
    public function ledger(Request $request, BillingHistory $history)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $csv = $history->csv($history->ledger());

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="platform-ledger-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Admin-only: export a sales report for the given period as PDF, using
     * the admin's selected display currency.
     */
    public function salesReport(Request $request)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $period = in_array((string) $request->query('period'), ['today', 'week', 'month', 'quarter', 'year', 'all'], true)
            ? (string) $request->query('period')
            : 'month';

        [$start, $end] = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [null, null],
        };

        $paid = Payment::with(['user', 'company'])
            ->where('status', PaymentStatus::Paid)
            ->when($start, fn ($query) => $query->where('paid_at', '>=', $start)->where('paid_at', '<=', $end))
            ->latest('paid_at')
            ->limit(500)
            ->get();

        $refunds = Payment::where('status', PaymentStatus::Refunded)
            ->when($start, fn ($query) => $query->where('paid_at', '>=', $start)->where('paid_at', '<=', $end))
            ->sum('amount');

        $byPurpose = $paid->groupBy(fn (Payment $p) => $p->purpose->label())
            ->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('amount');
        $byMethod = $paid->groupBy(fn (Payment $p) => $p->payment_method?->label() ?? 'Manual')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('amount');

        $currency = BillingCurrency::codeFor(Auth::user());

        $pdf = Pdf::loadView('pdf.sales-report', [
            'payments' => $paid,
            'revenue' => (float) $paid->sum('amount'),
            'refunds' => (float) $refunds,
            'byPurpose' => $byPurpose,
            'byMethod' => $byMethod,
            'period' => $period,
            'currency' => $currency,
            'admin' => Auth::user(),
        ])->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'Helvetica');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="sales-report-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }
}
