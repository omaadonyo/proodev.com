<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Exports a user's full billing history — every payment across verification,
 * credits, auto-scan and company subscriptions — as a CSV spreadsheet or a
 * printable PDF document.
 */
class BillingHistory
{
    /**
     * Every payment belonging to the user or to companies they own.
     *
     * @return Collection<int, Payment>
     */
    public function forUser(User $user): Collection
    {
        $ownedCompanies = $user->companiesOwned()->pluck('id');

        return Payment::with(['user', 'company'])
            ->where(function ($query) use ($ownedCompanies, $user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('company_id', $ownedCompanies);
            })
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();
    }

    /**
     * The full platform payment ledger for admins — every payment with its
     * customer resolved, newest first.
     *
     * @return Collection<int, Payment>
     */
    public function ledger(): Collection
    {
        return Payment::with(['user', 'company'])
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();
    }

    /**
     * @param  Collection<int, Payment>  $payments
     */
    public function csv(Collection $payments): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Invoice', 'Customer', 'Purpose', 'Description', 'Method', 'Reference',
            'Amount', 'Currency', 'Status', 'Paid at',
        ]);

        foreach ($payments as $payment) {
            $customer = $payment->company?->name;

            if ($payment->user && $customer !== $payment->user->name) {
                $customer = $customer ? $customer.' ('.$payment->user->name.')' : $payment->user->name;
            }

            fputcsv($handle, [
                $payment->invoiceNumber(),
                $customer ?: '—',
                $payment->purpose->label(),
                Str::limit($payment->lineDescription(), 120),
                $payment->payment_method?->label() ?? 'Manual',
                $payment->reference ?: $payment->gateway_reference ?: 'PDV-'.$payment->id,
                number_format((float) $payment->amount, 2, '.', ''),
                $payment->currency,
                $payment->status->label(),
                ($payment->paid_at ?? $payment->created_at)->toDateTimeString(),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param  Collection<int, Payment>  $payments
     */
    public function pdf(Collection $payments, User $user): string
    {
        $pdf = Pdf::loadView('pdf.billing-history', [
            'payments' => $payments,
            'user' => $user,
        ])->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'Helvetica');

        return $pdf->output();
    }
}
