<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Services\LevelService;
use App\Services\Recruiter\WorkspaceService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Wirechat\Wirechat\Contracts\WirechatUser;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\Traits\InteractsWithWirechat;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $username
 * @property string|null $avatar_path
 * @property UserRole $role
 * @property string|null $headline
 * @property string|null $bio
 * @property string|null $location
 * @property string $timezone
 * @property string|null $github_url
 * @property string|null $website_url
 * @property string|null $linkedin_url
 * @property int $experience_points
 * @property int $reputation_score
 * @property int $streak_count
 * @property int $longest_streak
 * @property Carbon|null $last_activity_at
 * @property int $two_hour_streak_count
 * @property Carbon|null $last_two_hour_reward_at
 * @property int $two_hour_earned_xp
 * @property int $vouch_credits
 * @property bool $is_admin
 * @property Carbon|null $suspended_at
 * @property bool $public_passport
 * @property array<string, mixed>|null $preferences
 * @property string $feed_layout
 * @property Carbon|null $onboarding_completed_at
 * @property bool $auto_scan_enabled
 * @property Carbon|null $auto_scan_active_until
 * @property Carbon|null $verification_expires_at
 * @property Carbon|null $last_auto_scan_at
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'username', 'avatar_path', 'role', 'headline', 'bio', 'location', 'timezone', 'github_url', 'website_url', 'linkedin_url', 'is_admin', 'suspended_at', 'public_passport', 'feed_layout', 'onboarding_completed_at', 'credit_balance', 'daily_evidence_count', 'daily_evidence_date', 'is_verified', 'verified_at', 'verification_expires_at', 'short_domain', 'auto_scan_enabled', 'auto_scan_active_until', 'last_auto_scan_at', 'two_hour_streak_count', 'last_two_hour_reward_at', 'two_hour_earned_xp'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser, WirechatUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithWirechat, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Model-level attribute defaults applied to new instances before persist.
     *
     * @var array<string, int|string|bool>
     */
    protected $attributes = [
        'experience_points' => 0,
        'reputation_score' => 0,
        'streak_count' => 0,
        'longest_streak' => 0,
        'two_hour_streak_count' => 0,
        'two_hour_earned_xp' => 0,
        'vouch_credits' => 3,
        'is_admin' => false,
        'public_passport' => true,
        'timezone' => 'UTC',
        'feed_layout' => 'list',
        'role' => UserRole::Developer,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
            'public_passport' => 'boolean',
            'preferences' => 'array',
            'last_activity_at' => 'datetime',
            'last_two_hour_reward_at' => 'datetime',
            'verified_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'auto_scan_enabled' => 'boolean',
            'auto_scan_active_until' => 'datetime',
            'last_auto_scan_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function handle(): string
    {
        return $this->username ?: 'user-'.$this->id;
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function completeOnboarding(): void
    {
        if ($this->onboarding_completed_at === null) {
            $this->forceFill(['onboarding_completed_at' => now()])->save();
        }
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return asset('storage/'.$this->avatar_path);
        }

        return $this->initialsAvatar();
    }

    /**
     * A real, locally-served avatar derived from the database record: the
     * user's own initials on a black plate with white text. Used when no photo
     * has been uploaded, so profile images never depend on an external service.
     * In dark mode the app CSS inverts the image so the plate reads white with
     * black initials, matching the theme.
     */
    public function initialsAvatar(): string
    {
        $initials = htmlspecialchars($this->initials(), ENT_QUOTES, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">'
            .'<rect width="96" height="96" fill="#000000"/>'
            .'<text x="48" y="52" font-family="-apple-system, Segoe UI, Roboto, Arial, sans-serif" font-size="34" font-weight="600" fill="#ffffff" text-anchor="middle" dominant-baseline="middle">'.$initials.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')->withPivot('level', 'verified_at', 'times_used')->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /**
     * @return HasMany<RepoClaim, $this>
     */
    public function repoClaims(): HasMany
    {
        return $this->hasMany(RepoClaim::class);
    }

    public function vouchesGiven(): HasMany
    {
        return $this->hasMany(Vouch::class, 'voucher_id');
    }

    public function vouchesReceived(): HasMany
    {
        return $this->hasMany(Vouch::class, 'vouchee_id');
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('progress', 'awarded_at', 'data')
            ->withTimestamps();
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function weeklyReports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function passportViews(): HasMany
    {
        return $this->hasMany(PassportView::class, 'passport_owner_id');
    }

    public function passportViewsMade(): HasMany
    {
        return $this->hasMany(PassportView::class, 'viewer_id');
    }

    public function recognitionsGiven(): HasMany
    {
        return $this->hasMany(ProjectRecognition::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Platform admin accounts are internal-only: they must never surface in
     * public listings, feeds, search, or on the landing page.
     */
    public function isVisibleToPublic(): bool
    {
        return ! $this->isAdmin();
    }

    /**
     * Restrict a query to users visible to the public (i.e. not admins).
     */
    public function scopeVisibleToPublic($query)
    {
        return $query->where('is_admin', false);
    }

    /**
     * Emails are private by default. Only platform admins and recruiters /
     * companies that are hiring may see a developer's email address.
     */
    public function canViewEmail(?User $viewer = null): bool
    {
        if (! $viewer) {
            return false;
        }

        return $viewer->isAdmin() || $viewer->isRecruiterOrCompanyAccount();
    }

    /**
     * Whether the user has been active recently enough to be considered online.
     */
    public function isOnline(int $withinMinutes = 5): bool
    {
        return $this->last_activity_at !== null
            && $this->last_activity_at->isAfter(now()->subMinutes($withinMinutes));
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function suspend(): void
    {
        $this->forceFill(['suspended_at' => now()])->save();
    }

    public function unsuspend(): void
    {
        $this->forceFill(['suspended_at' => null])->save();
    }

    public function isCompanyAccount(): bool
    {
        return $this->role?->isCompany() ?? false;
    }

    public function isRecruiterAccount(): bool
    {
        return $this->role?->isRecruiter() ?? false;
    }

    public function isRecruiterOrCompanyAccount(): bool
    {
        return $this->role?->isRecruiterOrCompany() ?? false;
    }

    public function hasIntelligenceAccess(): bool
    {
        return $this->isRecruiterAccount()
            || $this->isCompanyAccount();
    }

    public function hasWorkspaceAccess(): bool
    {
        return $this->isRecruiterAccount()
            || (($this->ownedCompany()?->plan?->hasIntelligence()) ?? false);
    }

    public function ownedCompany(): ?Company
    {
        return $this->companiesOwned()->latest()->first();
    }

    public function level(): int
    {
        return app(LevelService::class)->levelForXp($this->experience_points);
    }

    public function levelTitle(): string
    {
        return app(LevelService::class)->titleForLevel($this->level());
    }

    public function nextLevel(): int
    {
        return $this->level() + 1;
    }

    public function levelProgress(): float
    {
        return app(LevelService::class)->progress($this->experience_points);
    }

    public function xpToNextLevel(): int
    {
        return app(LevelService::class)->xpToNextLevel($this->experience_points);
    }

    public function companiesOwned(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    public function companyMemberships(): HasManyThrough
    {
        return $this->hasManyThrough(Company::class, CompanyMember::class, 'user_id', 'id', 'id', 'company_id');
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    public function currentWorkspace(): ?Workspace
    {
        return app(WorkspaceService::class)->current($this);
    }

    public function isWorkspaceMember(Workspace $workspace): bool
    {
        return $this->workspaces()->whereKey($workspace->id)->exists();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function candidateIntelligenceReports(): HasMany
    {
        return $this->hasMany(CandidateIntelligenceReport::class, 'recruiter_id');
    }

    public function talentPools(): HasMany
    {
        return $this->hasMany(TalentPool::class, 'recruiter_id');
    }

    public function recruiterNotes(): HasMany
    {
        return $this->hasMany(RecruiterNote::class, 'recruiter_id');
    }

    public function talentAlerts(): HasMany
    {
        return $this->hasMany(TalentAlert::class, 'recruiter_id');
    }

    public function recruiterInterviews(): HasMany
    {
        return $this->hasMany(RecruiterInterview::class, 'recruiter_id');
    }

    public function recruiterPlacements(): HasMany
    {
        return $this->hasMany(RecruiterPlacement::class, 'recruiter_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<AutoScanUrl, $this>
     */
    public function autoScanUrls(): HasMany
    {
        return $this->hasMany(AutoScanUrl::class);
    }

    /**
     * @return HasMany<AutoScanRun, $this>
     */
    public function autoScanRuns(): HasMany
    {
        return $this->hasMany(AutoScanRun::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function plagiarismStrikes(): HasMany
    {
        return $this->hasMany(PlagiarismStrike::class, 'offender_id');
    }

    public function userVerifications(): HasMany
    {
        return $this->hasMany(UserVerification::class);
    }

    public function approvedVouchesReceived(): HasMany
    {
        return $this->vouchesReceived()->where('status', 'approved');
    }

    public function isVerified(): bool
    {
        // Monthly verifications lapse automatically once their window ends;
        // lifetime verifications have no expiry (null).
        return (bool) $this->is_verified
            && (! $this->verification_expires_at || $this->verification_expires_at->isFuture());
    }

    /**
     * Whether the user opted in to a given email category.
     *
     * Stored in the JSON `preferences` column; every category defaults to on
     * so existing users keep receiving mail until they opt out.
     */
    public function wantsEmail(string $category): bool
    {
        $defaults = ['job_offers' => true, 'new_chats' => true, 'scans_evidence' => true, 'transactions' => true];

        return (bool) ($this->preferences['email_'.$category] ?? $defaults[$category] ?? true);
    }

    /**
     * Whether the user opted in to a given in-app (database) notification
     * category. Stored in the JSON `preferences` column; every category
     * defaults to on so existing users keep getting notified until they opt out.
     */
    public function wantsNotification(string $category): bool
    {
        $defaults = ['chats' => true, 'mentions' => true, 'weekly_reports' => true];

        return (bool) ($this->preferences['notify_'.$category] ?? $defaults[$category] ?? true);
    }

    /**
     * Total unread chat messages across all of the user's conversations.
     */
    public function unreadMessageCount(): int
    {
        return (int) $this->conversations()
            ->get()
            ->sum(fn ($conversation) => $conversation->unreadMessages($this)->count());
    }

    public function canAccessWirechatPanel(Panel $panel): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($panel->getId() === 'admin-chats') {
            return false;
        }

        if ($this->isVerified() && ! $this->suspended_at) {
            return true;
        }

        // Unverified: allow limited access via 1 free 5-min streak (forces verification after)
        if (! $this->isVerified() && ! $this->suspended_at && ($this->canUseFreeChat() || $this->hasActiveFreeChatStreak())) {
            return true;
        }

        return false;
    }

    public function canCreateChats(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isVerified() && ! $this->suspended_at) {
            return true;
        }

        // Unverified: 1 free chat per streak, 120-min window — then must verify
        if (! $this->isVerified() && ! $this->suspended_at && $this->canUseFreeChat()) {
            return true;
        }

        return false;
    }

    public function canCreateGroups(): bool
    {
        return $this->isAdmin() || ($this->isVerified() && ! $this->suspended_at);
    }

    public function getWirechatAvatarUrlAttribute(): ?string
    {
        return $this->avatarUrl();
    }

    public function getWirechatProfileUrlAttribute(): ?string
    {
        return route('devid', $this->handle());
    }

    /**
     * Whether the paid repo auto-scan subscription is currently active.
     */
    public function autoScanActive(): bool
    {
        return (bool) $this->auto_scan_enabled
            && $this->auto_scan_active_until !== null
            && $this->auto_scan_active_until->isFuture();
    }

    public function verifiedBadge(): ?string
    {
        if (! $this->is_verified) {
            return null;
        }

        return $this->short_domain ?: $this->handle();
    }

    /**
     * The short shareable passport URL for verified developers, or null when
     * the user has no reserved short name.
     */
    public function shortLink(): ?string
    {
        if (! $this->isVerified() || ! $this->short_domain) {
            return null;
        }

        return route('passport.short', $this->short_domain);
    }

    public function creditBalance(): int
    {
        return (int) $this->credit_balance;
    }

    public function awardedAchievements(): HasOne
    {
        return $this->hasOne(UserAchievement::class)->whereNotNull('awarded_at');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('users.id', $user->id)->exists();
    }

    public function follow(User $user): void
    {
        if ($this->id === $user->id || $this->isFollowing($user)) {
            return;
        }

        $this->following()->attach($user->id);
    }

    public function unfollow(User $user): void
    {
        $this->following()->detach($user->id);
    }

    public function canEarnTwoHourReward(): bool
    {
        // 0 streak: earn after 2 hours on site (from account creation or last reward)
        $baseline = $this->last_two_hour_reward_at ?? $this->created_at ?? now();
        return $baseline->diffInMinutes(now()) >= 120;
    }

    public function twoHourProgressPercent(): int
    {
        $baseline = $this->last_two_hour_reward_at ?? $this->created_at ?? now();
        $elapsed = $baseline->diffInMinutes(now());
        // Cap at 120, progress 0-100
        return (int) min(100, ($elapsed / 120) * 100);
    }

    public function minutesUntilNextTwoHourReward(): int
    {
        if ($this->canEarnTwoHourReward()) {
            return 0;
        }

        $baseline = $this->last_two_hour_reward_at ?? $this->created_at ?? now();
        $elapsed = $baseline->diffInMinutes(now());
        return max(0, 120 - $elapsed);
    }

    public function canUseFreeChat(): bool
    {
        if ($this->isVerified()) {
            return true;
        }

        // Need at least 1 streak earned (after 2 hours) to chat
        return $this->two_hour_streak_count >= 1 && $this->hasActiveFreeChatStreak();
    }

    public function hasActiveFreeChatStreak(): bool
    {
        if ($this->isVerified()) {
            return true;
        }

        // Streak is active for 2 hours after earning, allows 1 chat
        return $this->last_two_hour_reward_at !== null
            && $this->last_two_hour_reward_at->isAfter(now()->subMinutes(120));
    }

    public function freeChatExpiresAt(): ?\Carbon\CarbonInterface
    {
        return $this->last_two_hour_reward_at?->copy()->addMinutes(120);
    }
}
