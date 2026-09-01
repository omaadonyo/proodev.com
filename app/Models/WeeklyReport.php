<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $week_started
 * @property array<string, mixed>|null $data
 * @property Carbon|null $generated_at
 */
class WeeklyReport extends Model
{
    protected $fillable = ['user_id', 'week_started', 'data', 'generated_at'];

    protected function casts(): array
    {
        return [
            'week_started' => 'date',
            'data' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
