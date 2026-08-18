<?php

namespace App\Http\Controllers;

use App\Mail\PaymentInvoiceMail;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Printable invoices & receipts. Any payment — developer verification,
 * company subscription, credits, auto-scan — can be viewed by the person
 * it was billed to (or their company's owner) and by admins. The same
 * invoice can be re-sent to the customer's email from the page.
 */
class InvoiceController extends Controller
{
    /**
     * Show the printable invoice for a payment.
     */
    public function show(Payment $payment)
    {
        abort_unless($this->canAccess($payment), 403);

        return view('invoices.show', [
            'payment' => $payment,
            'sent' => (bool) session('invoice-sent'),
        ]);
    }

    /**
     * Re-send the invoice email to the customer.
     */
    public function email(Request $request, Payment $payment)
    {
        abort_unless($this->canAccess($payment), 403);

        $recipient = $payment->user ?? $payment->company?->owner;

        if ($recipient) {
            Mail::to($recipient)->send(new PaymentInvoiceMail($payment));
            $payment->forceFill(['invoice_emailed_at' => now()])->save();
        }

        return redirect()->route('invoices.show', $payment)->with('invoice-sent', true);
    }

    private function canAccess(Payment $payment): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return $payment->user_id === $user->id
            || $payment->company?->owner_id === $user->id;
    }
}
