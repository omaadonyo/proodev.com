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
 * @property array|null $report
 * @property string $generated_by
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 */
class CandidateIntelligenceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'recruiter_id',
        'candidate_id',
        'report',
        'generated_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'report' => 'array',
            'expires_at' => 'datetime',
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

    public function isFresh(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
