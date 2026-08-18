<?php

namespace App\Models;

use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $title
 * @property string $content
 * @property array<string, mixed>|null $structured_content
 * @property Visibility $visibility
 * @property bool $ai_processed
 * @property Carbon|null $published_at
 */
class JournalEntry extends Model
{
    protected $fillable = ['user_id', 'title', 'content', 'structured_content', 'visibility', 'ai_processed', 'published_at'];

    protected function casts(): array
    {
        return [
            'visibility' => Visibility::class,
            'structured_content' => 'array',
            'ai_processed' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('visibility', Visibility::Public);
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', Visibility::Public)
                ->orWhere('user_id', $user->id);
        });
    }

    public function isPublic(): bool
    {
        return $this->visibility === Visibility::Public;
    }
}
