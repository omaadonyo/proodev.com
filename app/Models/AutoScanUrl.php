<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoScanUrl extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SCANNED = 'scanned';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'url',
        'status',
        'last_scanned_at',
        'last_error',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_scanned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
