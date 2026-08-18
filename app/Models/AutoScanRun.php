<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoScanRun extends Model
{
    protected $fillable = [
        'user_id',
        'scanned',
        'new_evidence',
        'new_projects',
        'new_journal',
        'xp',
        'error',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
