<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $achievement_id
 * @property int $progress
 * @property Carbon|null $awarded_at
 * @property array<string, mixed>|null $data
 */
class UserAchievement extends Model
{
    protected $fillable = ['user_id', 'achievement_id', 'progress', 'awarded_at', 'data'];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
