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
 * @property int|null $job_id
 * @property string $status
 * @property Carbon|null $scheduled_at
 * @property string|null $mode
 * @property array|null $guide
 * @property string|null $outcome
 * @property Carbon $created_at
 */
class RecruiterInterview extends Model
{
    use HasFactory;

    public const STATUSES = ['scheduled', 'completed', 'cancelled', 'no_show'];

    protected $fillable = [
        'workspace_id',
        'recruiter_id',
        'candidate_id',
        'job_id',
        'status',
        'scheduled_at',
        'mode',
        'guide',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'guide' => 'array',
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

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}
