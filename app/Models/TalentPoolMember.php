<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $talent_pool_id
 * @property int $candidate_id
 * @property string $status
 * @property int|null $rating
 * @property string|null $notes
 * @property Carbon $created_at
 */
class TalentPoolMember extends Model
{
    use HasFactory;

    public const STATUSES = ['saved', 'shortlisted', 'contacted', 'interviewing', 'offered', 'placed', 'rejected'];

    protected $fillable = [
        'talent_pool_id',
        'candidate_id',
        'status',
        'rating',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(TalentPool::class, 'talent_pool_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}
