<?php

namespace App\Models;

use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property TimelineEventType $type
 * @property string $title
 * @property string|null $description
 * @property array<string, mixed>|null $data
 * @property string|null $target_type
 * @property int|null $target_id
 * @property Visibility $visibility
 * @property Carbon $occurred_at
 */
class TimelineEvent extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'description', 'data', 'target_type', 'target_id', 'visibility', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'type' => TimelineEventType::class,
            'data' => 'array',
            'visibility' => Visibility::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', Visibility::Public);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', Visibility::Public);

            if ($user) {
                $q->orWhere('user_id', $user->id);
            }
        });
    }

    /**
     * Keep only the single most recent event for each developer, so the feed
     * surfaces one result per person instead of flooding with their history.
     */
    public function scopeLatestVisiblePerUser($query, ?User $viewer = null, ?TimelineEventType $type = null)
    {
        $bindings = [Visibility::Public->value];
        $viewerSql = '';
        $typeSql = '';

        if ($viewer) {
            $viewerSql = 'or latest.user_id = ?';
            $bindings[] = $viewer->id;
        }

        if ($type) {
            $typeSql = 'and latest.type = ?';
            $bindings[] = $type->value;
        }

        return $query->whereRaw(
            'id = (
                select latest.id
                from timeline_events as latest
                where latest.user_id = timeline_events.user_id
                and (latest.visibility = ? '.$viewerSql.')
                '.$typeSql.'
                order by latest.occurred_at desc, latest.id desc
                limit 1
            )',
            $bindings,
        );
    }

    public static function iconFor(TimelineEventType $type): string
    {
        return [
            TimelineEventType::Joined->value => 'sparkles',
            TimelineEventType::ProjectPublished->value => 'folder-git-2',
            TimelineEventType::PackageReleased->value => 'archive-box',
            TimelineEventType::ProblemSolved->value => 'wrench-screwdriver',
            TimelineEventType::BadgeEarned->value => 'trophy',
            TimelineEventType::VouchReceived->value => 'shield-check',
            TimelineEventType::ArticlePublished->value => 'document-text',
            TimelineEventType::ArchitectureShowcase->value => 'building-library',
            TimelineEventType::LearningMilestone->value => 'academic-cap',
            TimelineEventType::AchievementVerified->value => 'check-badge',
            TimelineEventType::ProjectLaunch->value => 'rocket-launch',
            TimelineEventType::OpenSourceContribution->value => 'code-bracket',
            TimelineEventType::LevelUp->value => 'arrow-trending-up',
            TimelineEventType::SkillVerified->value => 'finger-print',
            TimelineEventType::JournalPublished->value => 'book-open',
            TimelineEventType::MilestoneReached->value => 'flag',
            TimelineEventType::EvidenceAdded->value => 'document-plus',
            TimelineEventType::EvidenceAnalyzed->value => 'sparkles',
            TimelineEventType::VerificationApproved->value => 'check-badge',
        ][$type->value] ?? 'bolt';
    }
}
