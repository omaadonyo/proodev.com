<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $payment_id
 * @property string|null $short_name
 * @property VerificationStatus $status
 * @property Carbon|null $approved_at
 */
class UserVerification extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'payment_id', 'short_name', 'status', 'approved_at'];

    protected function casts(): array
    {
        return [
            'status' => VerificationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
