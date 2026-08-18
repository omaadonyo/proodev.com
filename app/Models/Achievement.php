<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property string $category
 * @property int $points
 * @property string $type
 * @property int|null $threshold
 */
class Achievement extends Model
{
    protected $fillable = ['key', 'name', 'description', 'icon', 'category', 'points', 'type', 'threshold'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('progress', 'awarded_at', 'data')
            ->withTimestamps();
    }
}
