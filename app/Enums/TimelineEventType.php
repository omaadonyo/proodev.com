<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum TimelineEventType: string
{
    use HasLabels;

    case Joined = 'joined';
    case ProjectPublished = 'project-published';
    case PackageReleased = 'package-released';
    case ProblemSolved = 'problem-solved';
    case BadgeEarned = 'badge-earned';
    case VouchReceived = 'vouch-received';
    case ArticlePublished = 'article-published';
    case ArchitectureShowcase = 'architecture-showcase';
    case LearningMilestone = 'learning-milestone';
    case AchievementVerified = 'achievement-verified';
    case ProjectLaunch = 'project-launch';
    case OpenSourceContribution = 'open-source-contribution';
    case LevelUp = 'level-up';
    case SkillVerified = 'skill-verified';
    case JournalPublished = 'journal-published';
    case MilestoneReached = 'milestone-reached';
    case EvidenceAdded = 'evidence-added';
    case EvidenceAnalyzed = 'evidence-analyzed';
    case VerificationApproved = 'verification-approved';

    public const LABELS = [
        'joined' => 'Joined ProoDev',
        'project-published' => 'Published a Project',
        'package-released' => 'Released a Package',
        'problem-solved' => 'Solved an Engineering Problem',
        'badge-earned' => 'Earned a Badge',
        'vouch-received' => 'Received a Vouch',
        'article-published' => 'Published an Article',
        'architecture-showcase' => 'Architecture Showcase',
        'learning-milestone' => 'Learning Milestone',
        'achievement-verified' => 'Verified Achievement',
        'project-launch' => 'Project Launch',
        'open-source-contribution' => 'Open Source Contribution',
        'level-up' => 'Reached a New Level',
        'skill-verified' => 'Verified Skill',
        'journal-published' => 'Published Journal Entry',
        'milestone-reached' => 'Milestone Reached',
        'evidence-added' => 'Added Evidence',
        'evidence-analyzed' => 'Evidence Analyzed',
        'verification-approved' => 'Verification Approved',
    ];
}
