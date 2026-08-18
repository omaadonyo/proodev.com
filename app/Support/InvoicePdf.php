<?php

namespace App\Support;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generates the printable PDF for a payment invoice. The PDF uses a
 * table-based, print-optimized layout (dompdf-friendly) that mirrors the
 * on-screen invoice page and is attached to the invoice email.
 */
class InvoicePdf
{
    public function generate(Payment $payment): string
    {
        $pdf = Pdf::loadView('pdf.invoice', ['payment' => $payment])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'Helvetica');

        return $pdf->output();
    }

    public function filename(Payment $payment): string
    {
        return $payment->invoiceNumber().'.pdf';
    }
}
