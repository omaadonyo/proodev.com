<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $job_id
 * @property int $user_id
 * @property ApplicationStatus $status
 * @property string|null $cover_letter
 * @property string|null $resume_path
 * @property int $resume_view_count
 * @property Carbon|null $last_resume_viewed_at
 * @property string|null $notes
 * @property Carbon|null $reviewed_at
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = ['job_id', 'user_id', 'status', 'cover_letter', 'resume_path', 'resume_view_count', 'last_resume_viewed_at', 'notes', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'reviewed_at' => 'datetime',
            'last_resume_viewed_at' => 'datetime',
            'resume_view_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
