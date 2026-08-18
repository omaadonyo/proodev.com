<?php

namespace App\Services\Recruiter;

use App\Models\TeamProfile;
use App\Models\User;

/**
 * Team fit analysis. Given a team profile (strengths, gaps, desired
 * expertise), score how well a candidate covers the gaps and complements
 * existing strengths using the evidence engine.
 */
class TeamFitService
{
    public function __construct(private CandidateIntelligenceService $intelligence) {}

    /**
     * @return array<string, mixed>
     */
    public function assess(TeamProfile $team, User $candidate, ?User $recruiter = null): array
    {
        $report = $this->intelligence->report($candidate, [
            'recruiter' => $recruiter,
            'persist' => $recruiter !== null,
        ]);

        $strengths = $team->strengths ?? [];
        $gaps = $team->gaps ?? [];
        $desired = $team->desired_expertise ?? [];

        $areas = $report['evidence']['engineering_areas'];
        $technologies = $report['evidence']['technologies'];

        $gapCoverage = $this->coverage($gaps, array_merge($areas, $technologies));
        $desiredCoverage = $this->coverage($desired, array_merge($areas, $technologies));
        $complementary = $this->complementarySignals($strengths, $areas);

        $fitScore = $this->fitScore($gapCoverage, $desiredCoverage, $complementary, $report);

        $verdict = match (true) {
            $fitScore >= 80 => 'Excellent fit - directly covers the team gaps.',
            $fitScore >= 60 => 'Strong fit - covers key gaps and complements the team.',
            $fitScore >= 40 => 'Partial fit - fills some gaps, may need ramp-up time.',
            default => 'Weak fit - significant skill and evidence gaps remain.',
        };

        return [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'strengths' => $strengths,
                'gaps' => $gaps,
                'desired_expertise' => $desired,
            ],
            'fit_score' => $fitScore,
            'verdict' => $verdict,
            'gap_coverage' => [
                'covered' => $gapCoverage['covered'],
                'missing' => $gapCoverage['missing'],
            ],
            'desired_coverage' => [
                'covered' => $desiredCoverage['covered'],
                'missing' => $desiredCoverage['missing'],
            ],
            'complementary_strengths' => $complementary,
            'candidate' => $report,
            'generated_by' => 'evidence-engine',
        ];
    }

    /**
     * @param  array<int, string>  $needs
     * @param  array<int, string>  $have
     * @return array{covered: array<int, string>, missing: array<int, string>}
     */
    private function coverage(array $needs, array $have): array
    {
        $needSet = collect($needs)->map(fn ($n) => strtolower(trim($n)))->filter()->unique();
        $haveSet = collect($have)->map(fn ($h) => strtolower(trim($h)));

        $covered = $needSet->filter(fn ($need) => $haveSet->contains(fn ($have) => str_contains($have, $need) || str_contains($need, $have)));

        return [
            'covered' => $needSet->filter(fn ($n) => $covered->contains($n))->values()->all(),
            'missing' => $needSet->reject(fn ($n) => $covered->contains($n))->values()->all(),
        ];
    }

    /**
     * @param  array<int, string>  $teamStrengths
     * @param  array<int, string>  $candidateAreas
     * @return array<int, string>
     */
    private function complementarySignals(array $teamStrengths, array $candidateAreas): array
    {
        $teamSet = collect($teamStrengths)->map(fn ($s) => strtolower(trim($s)))->filter();

        return collect($candidateAreas)->filter(
            fn ($area) => ! $teamSet->contains(fn ($strength) => str_contains($area, $strength) || str_contains($strength, $area))
        )->values()->all();
    }

    /**
     * @param  array{covered: array<int, string>, missing: array<int, string>}  $gapCoverage
     * @param  array{covered: array<int, string>, missing: array<int, string>}  $desiredCoverage
     * @param  array<int, string>  $complementary
     * @param  array<string, mixed>  $report
     */
    private function fitScore(array $gapCoverage, array $desiredCoverage, array $complementary, array $report): int
    {
        $score = 0;

        $gapTotal = count($gapCoverage['covered']) + count($gapCoverage['missing']);
        if ($gapTotal > 0) {
            $score += (int) round(count($gapCoverage['covered']) / $gapTotal * 55);
        }

        $desiredTotal = count($desiredCoverage['covered']) + count($desiredCoverage['missing']);
        if ($desiredTotal > 0) {
            $score += (int) round(count($desiredCoverage['covered']) / $desiredTotal * 25);
        }

        $score += min(10, count($complementary) * 3);

        $magnitude = (int) $report['magnitude']['total'];
        if ($magnitude >= 500) {
            $score += 10;
        } elseif ($magnitude >= 300) {
            $score += 5;
        }

        return min(100, $score);
    }
}
