<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Services\Recruiter\JobMatchService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $created_by
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property array<int, string>|null $requirements
 * @property string|null $location
 * @property bool $is_remote
 * @property string|null $employment_type
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property string $currency
 * @property JobStatus $status
 * @property Carbon|null $published_at
 */
class Job extends Model
{
    use HasFactory;

    protected $table = 'job_posts';

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'slug',
        'description',
        'requirements',
        'location',
        'is_remote',
        'employment_type',
        'salary_min',
        'salary_max',
        'currency',
        'status',
        'published_at',
        'deadline',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'is_remote' => 'boolean',
            'status' => JobStatus::class,
            'published_at' => 'datetime',
            'deadline' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Job $job) {
            $job->slug ??= $job->uniqueSlug();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Open;
    }

    /**
     * Skill/capability tags for this role, derived from the description via
     * the same keyword extraction used by the recruiter match flow.
     *
     * @return array<int, string>
     */
    public function skillTags(int $limit = 4): array
    {
        $requirements = is_array($this->requirements)
            ? implode("\n", $this->requirements)
            : (string) $this->requirements;

        $keywords = app(JobMatchService::class)
            ->extractKeywords(implode("\n\n", array_filter([
                $this->title,
                $this->description,
                $requirements,
            ])));

        $names = Skill::query()
            ->whereIn('slug', array_slice($keywords['skills'], 0, $limit))
            ->pluck('name')
            ->values()
            ->all();

        return array_slice(array_merge($names, $keywords['technologies']), 0, $limit);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', JobStatus::Open);
    }

    public function salaryRange(): ?string
    {
        if ($this->salary_min === null && $this->salary_max === null) {
            return null;
        }

        $fmt = fn ($value) => number_format((int) $value).' '.strtoupper($this->currency);

        if ($this->salary_min && $this->salary_max) {
            return $fmt($this->salary_min).' – '.$fmt($this->salary_max);
        }

        return $this->salary_min ? 'From '.$fmt($this->salary_min) : 'Up to '.$fmt($this->salary_max);
    }

    public function uniqueSlug(): string
    {
        $base = Str::slug($this->title) ?: 'position';
        $slug = $base;
        $i = 2;

        while (static::where('company_id', $this->company_id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
