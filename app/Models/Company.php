<?php

namespace App\Models;

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $slug
 * @property string|null $logo_url
 * @property string|null $website
 * @property string|null $description
 * @property string|null $industry
 * @property string|null $location
 * @property string|null $size
 * @property CompanyPlan $plan
 * @property int $job_post_credits
 * @property bool $is_pioneer
 * @property CompanyStatus $status
 * @property Carbon|null $approved_at
 * @property Carbon|null $plan_renews_at
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'logo_url',
        'logo_path',
        'website',
        'description',
        'industry',
        'location',
        'size',
        'plan',
        'job_post_credits',
        'is_pioneer',
        'status',
        'approved_at',
        'plan_renews_at',
    ];

    public function logoUrl(): ?string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return $this->logo_url ?: null;
    }

    protected function casts(): array
    {
        return [
            'plan' => CompanyPlan::class,
            'job_post_credits' => 'integer',
            'status' => CompanyStatus::class,
            'is_pioneer' => 'boolean',
            'approved_at' => 'datetime',
            'plan_renews_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            $company->slug ??= $company->uniqueSlug();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CompanyMember::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function teamProfiles(): HasMany
    {
        return $this->hasMany(TeamProfile::class);
    }

    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, Job::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isApproved(): bool
    {
        return $this->status === CompanyStatus::Approved;
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function openJobsCount(): int
    {
        return $this->jobs()->where('status', 'open')->count();
    }

    public function jobPostCredits(): int
    {
        return (int) $this->job_post_credits;
    }

    public function usedJobPosts(): int
    {
        return $this->openJobsCount();
    }

    public function remainingJobPosts(): int
    {
        return max(0, $this->jobPostCredits() - $this->usedJobPosts());
    }

    public function grantJobPosts(int $credits): void
    {
        if ($credits > 0) {
            $this->increment('job_post_credits', $credits);
        }
    }

    public function planLimitReached(): bool
    {
        // Paid plans (Recruiter / Intelligence Suite) include unlimited job posts.
        // Free and trial companies draw from their purchased job post credits.
        if ($this->plan->isPaid()) {
            return false;
        }

        return $this->usedJobPosts() >= $this->jobPostCredits();
    }

    public function canPostJobs(): bool
    {
        return $this->isApproved() && ! $this->planLimitReached();
    }

    public function uniqueSlug(): string
    {
        $base = Str::slug($this->name) ?: 'company';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
