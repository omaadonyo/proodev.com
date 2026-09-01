<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int|null $overturned_by
 * @property Carbon|null $overturned_at
 */

/**
 * @property int $id
 * @property int $offender_id
 * @property int|null $owner_id
 * @property int|null $evidence_id
 * @property string $repo_owner
 * @property string $repo_name
 * @property string $repo_url
 * @property int $strike_number
 * @property string $action
 * @property string $reason
 * @property Carbon|null $notified_at
 * @property Carbon $created_at
 */
class PlagiarismStrike extends Model
{
    public const ACTION_WARNING = 'warning';

    public const ACTION_BANNED = 'banned';

    protected $fillable = [
        'offender_id',
        'owner_id',
        'evidence_id',
        'repo_owner',
        'repo_name',
        'repo_url',
        'strike_number',
        'action',
        'reason',
        'review_note',
        'notified_at',
        'overturned_at',
        'overturned_by',
    ];

    protected function casts(): array
    {
        return [
            'strike_number' => 'integer',
            'notified_at' => 'datetime',
            'overturned_at' => 'datetime',
        ];
    }

    public function offender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offender_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    public function overturnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overturned_by');
    }

    public function isOverturned(): bool
    {
        return $this->overturned_at !== null;
    }

    public function isWarning(): bool
    {
        return $this->action === self::ACTION_WARNING;
    }

    public function isBan(): bool
    {
        return $this->action === self::ACTION_BANNED;
    }
}
