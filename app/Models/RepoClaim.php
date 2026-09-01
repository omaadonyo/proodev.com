<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property string $owner
 * @property string $repo
 * @property string $source
 * @property string $origin
 * @property Carbon $created_at
 */
class RepoClaim extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'owner',
        'repo',
        'source',
        'origin',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}