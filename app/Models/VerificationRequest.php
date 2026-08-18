<?php

namespace App\Models;

use App\Enums\VerificationRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property VerificationRequestType $type
 * @property string|null $company_name
 * @property string|null $company_domain
 * @property string|null $label
 * @property array<string, mixed>|null $evidence
 * @property string $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $expires_at
 */
class VerificationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'company_name',
        'company_domain',
        'label',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => VerificationRequestType::class,
            'evidence' => 'array',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
