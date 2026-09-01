<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $company_id
 * @property PaymentPurpose $purpose
 * @property string $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property string|null $provider
 * @property PaymentMethod|null $payment_method
 * @property string|null $reference
 * @property string|null $gateway_reference
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $gateway_data
 * @property int|null $confirmed_by
 * @property Carbon|null $paid_at
 * @property Carbon|null $customer_confirmed_at
 * @property Carbon|null $invoice_emailed_at
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'company_id',
        'purpose',
        'amount',
        'currency',
        'status',
        'provider',
        'payment_method',
        'reference',
        'gateway_reference',
        'metadata',
        'gateway_data',
        'confirmed_by',
        'paid_at',
        'customer_confirmed_at',
        'invoice_emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => PaymentPurpose::class,
            'status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'metadata' => 'array',
            'gateway_data' => 'array',
            'paid_at' => 'datetime',
            'customer_confirmed_at' => 'datetime',
            'invoice_emailed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (! $payment->uuid) {
                $payment->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Checkout URLs reference the payment by its uuid, never the raw id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Whether the customer has confirmed they sent a manual payment
     * (WorldRemit / bank transfer) and it is awaiting admin verification.
     */
    public function confirmedByCustomer(): bool
    {
        return $this->customer_confirmed_at !== null;
    }

    /**
     * Whether the invoice/receipt has been emailed to the buyer.
     */
    public function invoiceEmailed(): bool
    {
        return $this->invoice_emailed_at !== null;
    }

    /**
     * Human-friendly invoice number derived from the payment creation
     * timestamp and the payment id — e.g. INV-1755432000-0042.
     */
    public function invoiceNumber(): string
    {
        return 'INV-'.$this->created_at?->getTimestamp().'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Line item description shown on the invoice.
     */
    public function lineDescription(): string
    {
        return match ($this->purpose) {
            PaymentPurpose::Verification => ($this->metadata['company_verification'] ?? false)
                ? 'Company Verification ('.$this->company?->name.')'
                : 'Developer Verification Badge',
            PaymentPurpose::Subscription => 'Company Subscription — '.($this->metadata['plan'] ?? '')
                .(($this->metadata['first_month'] ?? false) ? ' (first month)' : ''),
            PaymentPurpose::AutoScan => 'Repo Auto-Scan — '.(int) ($this->metadata['interval_days'] ?? 30).' days of automatic repo scanning',
            PaymentPurpose::Credits => 'Credit Bundle — '.(int) ($this->metadata['credits'] ?? 0).' credits',
            PaymentPurpose::JobPosts => 'Job Post Credits — '.(int) ($this->metadata['job_posts'] ?? 0).' job post'.((int) ($this->metadata['job_posts'] ?? 0) === 1 ? '' : 's'),
            default => 'Payment #'.$this->id,
        };
    }

    /**
     * The customer this invoice is billed to.
     *
     * @return array{name: string, email: string, company: string|null}
     */
    public function billedTo(): array
    {
        $name = $this->company?->name ?? $this->user?->name ?? 'Customer';
        $email = $this->user?->email ?? $this->company?->owner?->email ?? '';

        return [
            'name' => $name,
            'email' => $email,
            'company' => $this->company?->name,
        ];
    }
}
