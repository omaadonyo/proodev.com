<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Structured, candidate-safe rejection reasons. Recruiters never have to
 * write free-form explanations; the optional short note is separate.
 */
enum FeedbackCategory: string
{
    use HasLabels;

    case ExperienceMismatch = 'experience_mismatch';
    case SkillsMismatch = 'skills_mismatch';
    case StrongerCandidate = 'stronger_candidate';
    case CompensationMismatch = 'compensation_mismatch';
    case LocationOrAuthorization = 'location_or_authorization';
    case RoleChanged = 'role_requirements_changed';
    case PositionFilled = 'position_filled';
    case AssessmentResult = 'assessment_result';
    case InterviewPerformance = 'interview_performance';
    case InsufficientEvidence = 'insufficient_evidence';
    case Other = 'other';

    public const LABELS = [
        'experience_mismatch' => 'Experience did not match role requirements',
        'skills_mismatch' => 'Technical skills did not match',
        'stronger_candidate' => 'Another candidate had stronger relevant experience',
        'compensation_mismatch' => 'Compensation mismatch',
        'location_or_authorization' => 'Location / work authorization',
        'role_requirements_changed' => 'Role requirements changed',
        'position_filled' => 'Position filled',
        'assessment_result' => 'Assessment result',
        'interview_performance' => 'Interview / communication performance',
        'insufficient_evidence' => 'Insufficient evidence of required skill',
        'other' => 'Other',
    ];
}