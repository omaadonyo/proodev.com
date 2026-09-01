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
 * @property int|null $company_id
 * @property string $name
 * @property array|null $criteria
 * @property string $frequency
 * @property bool $email_enabled
 * @property bool $in_app_enabled
 * @property bool $is_active
 * @property Carbon|null $last_run_at
 * @property Carbon $created_at
 */
class TalentAlert extends Model
{
    use HasFactory;

    public const FREQUENCIES = ['realtime', 'daily', 'weekly'];

    protected $fillable = [
        'workspace_id',
        'recruiter_id',
        'company_id',
        'name',
        'criteria',
        'frequency',
        'email_enabled',
        'in_app_enabled',
        'is_active',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'email_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
