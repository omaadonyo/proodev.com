<?php

namespace App\Models;

use App\Enums\VouchStatus;
use App\Enums\VouchType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $voucher_id
 * @property int $vouchee_id
 * @property VouchType $type
 * @property int|null $skill_id
 * @property string|null $message
 * @property VouchStatus $status
 * @property int $weight
 */
class Vouch extends Model
{
    protected $fillable = ['voucher_id', 'vouchee_id', 'type', 'skill_id', 'message', 'status', 'weight'];

    protected function casts(): array
    {
        return [
            'type' => VouchType::class,
            'status' => VouchStatus::class,
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voucher_id');
    }

    public function vouchee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vouchee_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function isApproved(): bool
    {
        return $this->status === VouchStatus::Approved;
    }
}
