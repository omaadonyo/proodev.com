<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Candidate-facing hiring pipeline stages. Every stage change is recorded
 * as an auditable ApplicationEvent.
 */
enum HiringStage: string
{
    use HasLabels;

    case ApplicationReceived = 'application_received';
    case Reviewing = 'reviewing';
    case Reviewed = 'reviewed';
    case Shortlisted = 'shortlisted';
    case Assessment = 'assessment';
    case Interview = 'interview';
    case Offer = 'offer';
    case NotSelected = 'not_selected';
    case Withdrawn = 'withdrawn';
    case RolePaused = 'role_paused';
    case RoleClosed = 'role_closed';

    public const LABELS = [
        'application_received' => 'Application received',
        'reviewing' => 'Application under review',
        'reviewed' => 'Application reviewed',
        'shortlisted' => 'Shortlisted',
        'assessment' => 'Technical assessment',
        'interview' => 'Interview',
        'offer' => 'Offer',
        'not_selected' => 'Not selected',
        'withdrawn' => 'Application withdrawn',
        'role_paused' => 'Role paused',
        'role_closed' => 'Position closed',
    ];

    /**
     * Signal strength for UI hierarchy — the timeline emphasises meaningful
     * milestones over weak signals like profile opens.
     */
    public function signal(): string
    {
        return match ($this) {
            self::ApplicationReceived, self::Reviewing, self::Reviewed => 'low',
            self::Shortlisted, self::Assessment => 'strong',
            self::Interview => 'very_strong',
            self::Offer, self::NotSelected => 'final',
            default => 'neutral',
        };
    }

    public function isDecision(): bool
    {
        return in_array($this, [self::Offer, self::NotSelected, self::Withdrawn], true);
    }

    public function isClosedState(): bool
    {
        return in_array($this, [self::NotSelected, self::RoleClosed, self::Withdrawn], true);
    }

    /**
     * The ordered candidate-facing milestone track used to render timelines.
     */
    /** @return array<int, self> */
    public static function milestoneTrack(): array
    {
        return [
            self::ApplicationReceived,
            self::Reviewing,
            self::Reviewed,
            self::Shortlisted,
            self::Assessment,
            self::Interview,
            self::Offer,
        ];
    }

    /**
     * Map a hiring stage onto the legacy ApplicationStatus enum so existing
     * screens and filters keep working.
     */
    public function legacyStatus(): ?ApplicationStatus
    {
        return match ($this) {
            self::Shortlisted, self::Assessment, self::Interview => ApplicationStatus::Shortlisted,
            self::Offer => ApplicationStatus::Hired,
            self::NotSelected => ApplicationStatus::Rejected,
            default => null,
        };
    }
}