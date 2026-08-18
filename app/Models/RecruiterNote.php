<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $workspace_id
 * @property int $recruiter_id
 * @property int $candidate_id
 * @property int|null $talent_pool_id
 * @property string $body
 * @property bool $is_shared
 * @property Carbon $created_at
 */
class RecruiterNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'recruiter_id',
        'candidate_id',
        'talent_pool_id',
        'body',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
        ];
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(TalentPool::class, 'talent_pool_id');
    }
}
