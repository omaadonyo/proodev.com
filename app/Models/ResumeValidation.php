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
 * @property string $resume_text
 * @property array|null $results
 * @property int $confidence
 * @property string $generated_by
 * @property Carbon $created_at
 */
class ResumeValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'recruiter_id',
        'candidate_id',
        'resume_text',
        'results',
        'confidence',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'results' => 'array',
            'confidence' => 'integer',
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
}
