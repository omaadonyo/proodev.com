<?php

namespace App\Services\Recruiter;

use App\Models\ResumeValidation;
use App\Models\User;
use App\Services\Ai\AiService;
use Illuminate\Support\Str;

/**
 * Resume vs evidence validation. Compares claims on a resume (technologies,
 * areas, seniority) against the candidate's analyzed evidence library. This
 * is the recruiter's "truth check" - it surfaces what is proven, what is
 * unproven, and what is contradicted by the evidence.
 */
class ResumeValidationService
{
    private const TECH_PATTERN = '/\b(laravel|php|python|javascript|typescript|react|vue|livewire|tailwind|node\.?js|django|flask|ruby|go|rust|java|spring|kubernetes|docker|aws|gcp|azure|mysql|postgresql|postgres|redis|graphql|sql|mongodb|elasticsearch|kafka)\b/i';

    public function __construct(private AiService $ai) {}

    /**
     * Validate a candidate's resume text against their evidence library.
     *
     * @return array<string, mixed>
     */
    public function validate(User $recruiter, User $candidate, string $resumeText): array
    {
        $report = app(CandidateIntelligenceService::class)->report($candidate, [
            'recruiter' => $recruiter,
            'persist' => true,
        ]);

        $resumeTech = $this->extractTechnologies($resumeText);
        $resumeAreas = $this->inferAreas($resumeText);

        $evidenceTech = collect($report['evidence']['technologies'])->map(fn ($t) => strtolower($t))->all();
        $evidenceAreas = collect($report['evidence']['engineering_areas'])->map(fn ($a) => strtolower($a))->all();

        $proven = [];
        $unproven = [];
        $contradicted = [];

        foreach ($resumeTech as $tech) {
            $found = collect($evidenceTech)->contains(fn ($e) => str_contains($e, $tech) || str_contains($tech, $e));
            if ($found) {
                $proven[] = $tech;
            } else {
                $unproven[] = $tech;
            }
        }

        foreach ($resumeAreas as $area) {
            $found = collect($evidenceAreas)->contains(fn ($e) => str_contains($e, $area) || str_contains($area, $e));
            if (! $found) {
                $unproven[] = $area;
            }
        }

        $resumeSeniority = $this->seniorityFromResume($resumeText);
        $evidenceSeniority = $report['seniority'];

        if ($resumeSeniority !== null && $this->seniorityRank($resumeSeniority) > $this->seniorityRank($evidenceSeniority) + 1) {
            $contradicted[] = "Resume claims {$resumeSeniority} seniority, but the evidence library supports at most {$evidenceSeniority}.";
        }

        $evidenceCount = $report['evidence']['count'];
        $totalClaims = count($resumeTech) + count($resumeAreas);
        $proofRate = $totalClaims > 0 ? (int) round(count($proven) / $totalClaims * 100) : 0;

        $verdict = match (true) {
            $proofRate >= 80 => 'Strongly verified - resume claims align with analyzed evidence.',
            $proofRate >= 50 => 'Partially verified - core claims check out, some claims lack evidence.',
            $evidenceCount === 0 => 'Cannot verify - no analyzed evidence exists for this candidate yet.',
            $proofRate >= 25 => 'Weakly verified - several resume claims are not backed by evidence.',
            default => 'Poorly verified - most resume claims are not backed by evidence.',
        };

        $confidence = $this->confidence($proofRate, $evidenceCount);

        $result = [
            'candidate' => $report['developer'],
            'summary' => $this->ai->available()
                ? $this->ai->complete(
                    'You are a verification analyst comparing a resume to verified engineering evidence. Be factual and strict.',
                    'Summarize the resume validation result.',
                    ['context' => json_encode([
                        'proven' => $proven,
                        'unproven' => $unproven,
                        'contradicted' => $contradicted,
                        'proof_rate' => $proofRate,
                    ])],
                )
                : null,
            'proven_claims' => array_values(array_unique($proven)),
            'unproven_claims' => array_values(array_unique($unproven)),
            'contradictions' => array_values(array_unique($contradicted)),
            'proof_rate' => $proofRate,
            'verdict' => $verdict,
            'confidence' => $confidence,
            'evidence_count' => $evidenceCount,
            'evidence_seniority' => $evidenceSeniority,
            'generated_by' => 'evidence-engine',
            'generated_at' => now()->toIso8601String(),
        ];

        ResumeValidation::create([
            'workspace_id' => app(WorkspaceService::class)->currentId($recruiter),
            'recruiter_id' => $recruiter->id,
            'candidate_id' => $candidate->id,
            'resume_text' => Str::limit($resumeText, 100000),
            'results' => $result,
            'confidence' => $confidence,
            'generated_by' => 'evidence-engine',
        ]);

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function extractTechnologies(string $text): array
    {
        preg_match_all(self::TECH_PATTERN, $text, $matches);

        $normalized = array_map(function ($tech) {
            return str_replace(['.', '-'], '', strtolower($tech));
        }, $matches[0]);

        $map = [
            'js' => 'javascript',
            'nodejs' => 'node',
            'postgres' => 'postgresql',
            'aws' => 'aws',
            'gcp' => 'gcp',
        ];

        $normalized = array_map(fn ($tech) => $map[$tech] ?? $tech, $normalized);

        return array_values(array_unique($normalized));
    }

    /**
     * @return array<int, string>
     */
    private function inferAreas(string $text): array
    {
        $lower = strtolower($text);

        $signals = [
            'Backend Engineering' => ['backend', 'server-side', 'rest api', 'api', 'microservice'],
            'Frontend Engineering' => ['frontend', 'ui', 'ux', 'react', 'vue', 'css'],
            'Data Engineering' => ['data', 'etl', 'pipeline', 'sql', 'database'],
            'DevOps' => ['devops', 'ci/cd', 'kubernetes', 'docker', 'infrastructure'],
            'Security Engineering' => ['security', 'penetration', 'auth', 'encryption'],
            'Software Architecture' => ['architecture', 'system design', 'distributed', 'scalable'],
            'Testing & Quality' => ['testing', 'quality', 'coverage', 'test automation'],
        ];

        $areas = [];

        foreach ($signals as $area => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($lower, $keyword)) {
                    $areas[] = $area;
                    break;
                }
            }
        }

        return $areas;
    }

    private function seniorityFromResume(string $text): ?string
    {
        $lower = strtolower($text);

        if (preg_match('/(staff|principal|distinguished)/', $lower)) {
            return 'Staff / Principal';
        }

        if (preg_match('/(senior|lead)/', $lower)) {
            return 'Senior';
        }

        if (preg_match('/(mid[- ]level|intermediate)/', $lower)) {
            return 'Mid-level';
        }

        if (preg_match('/(junior|associate|entry)/', $lower)) {
            return 'Junior / Associate';
        }

        return null;
    }

    private function seniorityRank(string $level): int
    {
        return match ($level) {
            'Staff / Principal' => 5,
            'Senior' => 4,
            'Mid-level' => 3,
            'Junior / Associate' => 2,
            default => 1,
        };
    }

    private function confidence(int $proofRate, int $evidenceCount): int
    {
        $score = $proofRate;
        $score += min(20, $evidenceCount * 3);

        return min(100, $score);
    }
}
