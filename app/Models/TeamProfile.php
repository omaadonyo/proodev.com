<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property array|null $strengths
 * @property array|null $gaps
 * @property array|null $desired_expertise
 * @property bool $is_default
 * @property Carbon $created_at
 */
class TeamProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'strengths',
        'gaps',
        'desired_expertise',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'gaps' => 'array',
            'desired_expertise' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
