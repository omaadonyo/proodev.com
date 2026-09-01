<?php

namespace App\Services\Recruiter;

use App\Models\Application;
use App\Models\User;

/**
 * Hiring risk assessment. Flags the risks of moving a candidate through
 * the pipeline - evidence gaps, verification gaps, weak signals, and
 * pipeline red flags - all derived from evidence rather than gut feeling.
 */
class RiskAssessmentService
{
    public function __construct(private CandidateIntelligenceService $intelligence) {}

    /**
     * @return array<string, mixed>
     */
    public function assess(User $candidate, ?User $recruiter = null, ?Application $application = null): array
    {
        $report = $this->intelligence->report($candidate, [
            'recruiter' => $recruiter,
            'persist' => $recruiter !== null,
        ]);

        $risks = [];
        $severity = 0;

        if ($report['evidence']['count'] < 3) {
            $risks[] = [
                'level' => 'high',
                'type' => 'thin_evidence',
                'title' => 'Thin evidence library',
                'detail' => 'Only '.$report['evidence']['count'].' analyzed sources. Depth of expertise cannot be reliably judged.',
            ];
            $severity += 30;
        }

        if (! $report['verification']['verified']) {
            $risks[] = [
                'level' => 'medium',
                'type' => 'unverified',
                'title' => 'Not verified',
                'detail' => 'No approved verification requests or verified skills. Proficiency is inferred, not confirmed.',
            ];
            $severity += 15;
        }

        if ($report['magnitude']['total'] < 300) {
            $risks[] = [
                'level' => 'medium',
                'type' => 'early_career',
                'title' => 'Early career signal',
                'detail' => 'Engineering Magnitude '.$report['magnitude']['total'].'/1000 suggests limited proven experience.',
            ];
            $severity += 15;
        }

        $weakFactors = collect($report['magnitude']['factors'])
            ->filter(fn ($f) => $f['max'] > 0 && ($f['points'] / $f['max']) < 0.3)
            ->keys()
            ->take(2);

        foreach ($weakFactors as $factor) {
            $label = $report['magnitude']['factors'][$factor]['label'];
            $risks[] = [
                'level' => 'low',
                'type' => 'weak_factor',
                'title' => 'Thin '.strtolower($label),
                'detail' => $report['magnitude']['factors'][$factor]['description'],
            ];
            $severity += 5;
        }

        if (count($report['weaknesses']) > 0) {
            $risks[] = [
                'level' => 'medium',
                'type' => 'weakness',
                'title' => 'Identified weaknesses',
                'detail' => $report['weaknesses'][0],
            ];
            $severity += 10;
        }

        if ($application && $application->cover_letter === null && $application->notes === null) {
            $risks[] = [
                'level' => 'low',
                'type' => 'bare_application',
                'title' => 'Minimal application',
                'detail' => 'No cover letter or notes provided - engagement level is unclear.',
            ];
            $severity += 5;
        }

        $confidence = $report['confidence'];
        if ($confidence < 50) {
            $risks[] = [
                'level' => 'high',
                'type' => 'low_confidence',
                'title' => 'Low assessment confidence',
                'detail' => 'Assessment confidence is '.$confidence.'% - verify claims before progressing.',
            ];
            $severity += 20;
        }

        $score = max(0, 100 - $severity);

        $band = match (true) {
            $score >= 75 => 'low',
            $score >= 50 => 'medium',
            default => 'high',
        };

        return [
            'candidate' => $report['developer'],
            'risk_score' => $severity,
            'overall_risk' => $band,
            'recommendation' => $this->recommendation($band, $report),
            'risks' => $risks,
            'confidence' => $confidence,
            'evidence_count' => $report['evidence']['count'],
            'verification' => $report['verification']['verified'],
            'generated_by' => 'evidence-engine',
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function recommendation(string $band, array $report): string
    {
        return match ($band) {
            'low' => 'Proceed with confidence - the candidate clears the evidence and verification bar.',
            'medium' => 'Proceed with an interview, but probe the identified evidence gaps directly.',
            default => 'Pause and verify. Request additional evidence or references before moving forward.',
        };
    }
}
