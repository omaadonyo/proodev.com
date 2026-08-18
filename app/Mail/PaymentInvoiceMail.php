<?php

namespace App\Mail;

use App\Models\Payment;
use App\Support\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invoice & receipt for a confirmed payment. The same mailable is sent to the
 * customer and (with $copy = true) to the platform admins.
 */
class PaymentInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public bool $copy = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->payment->purpose->label().' — Invoice '.$this->payment->invoiceNumber();

        return new Envelope(
            subject: $this->copy ? '[Admin copy] '.$subject : $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice',
            with: ['copy' => $this->copy],
        );
    }

    /**
     * Attach the generated PDF so customers get a downloadable invoice file.
     */
    public function attachments(): array
    {
        $pdf = app(InvoicePdf::class);

        return [
            Attachment::fromData(
                fn () => $pdf->generate($this->payment),
                $pdf->filename($this->payment),
            )->withMime('application/pdf'),
        ];
    }
}
