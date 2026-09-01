<?php

namespace App\Services\Recruiter;

use App\Models\User;

/**
 * Executive-level candidate exports. Produces a structured, evidence-backed
 * brief suitable for sharing with hiring managers, execs, or clients - the
 * recruiter's polished deliverable derived from the evidence engine.
 */
class ExecutiveReportService
{
    public function __construct(private CandidateIntelligenceService $intelligence) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $candidate, ?User $recruiter = null): array
    {
        $report = $this->intelligence->report($candidate, [
            'recruiter' => $recruiter,
            'persist' => $recruiter !== null,
        ]);

        return [
            'meta' => [
                'title' => 'Candidate Executive Brief',
                'candidate' => $report['developer']['name'],
                'prepared_for' => $recruiter?->name ?? 'Recruiting Team',
                'generated_at' => now()->format('F j, Y'),
                'generated_by' => $report['generated_by'],
                'confidence' => $report['confidence'],
            ],
            'executive_summary' => $report['summary'],
            'snapshot' => [
                'magnitude' => $report['magnitude']['total'].' / 1000 ('.$report['magnitude']['label'].')',
                'seniority' => $report['seniority'],
                'verified' => $report['verification']['verified'] ? 'Yes' : 'No',
                'evidence_sources' => $report['evidence']['count'],
                'technologies' => implode(', ', array_slice($report['evidence']['technologies'], 0, 10)),
                'areas' => implode(', ', array_slice($report['evidence']['engineering_areas'], 0, 8)),
                'location' => $report['developer']['location'] ?? 'Not listed',
                'timezone' => $report['developer']['timezone'] ?? 'Not listed',
            ],
            'profile' => [
                'name' => $report['developer']['name'],
                'headline' => $report['developer']['headline'],
                'location' => $report['developer']['location'],
                'passport' => $report['developer']['passport_url'],
                'reputation' => $report['developer']['reputation'],
            ],
            'magnitude_factors' => collect($report['magnitude']['factors'])->map(fn ($f) => [
                'label' => $f['label'],
                'points' => $f['points'],
                'max' => $f['max'],
                'description' => $f['description'],
                'evidence' => $f['evidence'],
            ])->values()->all(),
            'verified_skills' => $report['verification']['verified_skills'],
            'skills' => collect($report['skills'])->map(fn ($s) => [
                'name' => $s['name'],
                'level' => $s['level'],
                'verified' => $s['verified'],
            ])->all(),
            'strengths' => $report['strengths'],
            'concerns' => $report['weaknesses'],
            'evidence_highlights' => collect($report['evidence']['top'])->map(fn ($e) => [
                'title' => $e['title'],
                'type' => $e['type_label'],
                'score' => $e['ai_score'],
                'complexity' => $e['complexity'],
                'summary' => $e['summary'],
                'url' => $e['url'],
            ])->all(),
            'recommended_roles' => $report['suggested_roles'],
            'community' => $report['community'],
            'disclaimer' => 'This brief is generated from the candidate\'s analyzed evidence library on ProoDev. All scores are evidence-derived and explainable; no self-reported claims are taken at face value.',
        ];
    }
}
