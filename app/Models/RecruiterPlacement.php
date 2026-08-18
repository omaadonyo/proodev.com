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
 * @property int|null $company_id
 * @property string|null $role_title
 * @property string $status
 * @property Carbon|null $placed_at
 * @property Carbon $created_at
 */
class RecruiterPlacement extends Model
{
    use HasFactory;

    public const STATUSES = ['in_progress', 'placed', 'closed'];

    protected $fillable = [
        'workspace_id',
        'recruiter_id',
        'candidate_id',
        'company_id',
        'role_title',
        'status',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'date',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
