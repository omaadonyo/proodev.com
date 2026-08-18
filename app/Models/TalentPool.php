<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $workspace_id
 * @property int|null $company_id
 * @property int $recruiter_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $kind
 * @property bool $is_shared
 * @property Carbon $created_at
 */
class TalentPool extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'company_id',
        'recruiter_id',
        'name',
        'slug',
        'description',
        'kind',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TalentPool $pool) {
            $pool->slug ??= $pool->uniqueSlug();
        });
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

    public function members(): HasMany
    {
        return $this->hasMany(TalentPoolMember::class);
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'talent_pool_members', 'talent_pool_id', 'candidate_id')
            ->withPivot(['status', 'rating', 'notes'])
            ->withTimestamps();
    }

    public function uniqueSlug(): string
    {
        $base = Str::slug($this->name) ?: 'pool';
        $slug = $base;
        $i = 2;

        while (static::where('recruiter_id', $this->recruiter_id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function scopeWhereShared($query)
    {
        return $query->where('is_shared', true);
    }
}
