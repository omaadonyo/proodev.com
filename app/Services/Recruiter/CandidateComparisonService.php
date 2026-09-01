<?php

namespace App\Services\Recruiter;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Side-by-side candidate comparison. Compares two or three candidates
 * across magnitude, evidence, skills, verification, and strengths, then
 * produces a recommendation backed by the same evidence engine.
 */
class CandidateComparisonService
{
    public function __construct(private CandidateIntelligenceService $intelligence) {}

    /**
     * @param  array<int, User>  $candidates
     * @return array<string, mixed>
     */
    public function compare(array $candidates, ?User $recruiter = null): array
    {
        if (count($candidates) < 2) {
            throw new \InvalidArgumentException('Comparison requires at least two candidates.');
        }

        $reports = collect($candidates)->mapWithKeys(
            fn (User $candidate) => [$candidate->id => $this->intelligence->report($candidate, [
                'recruiter' => $recruiter,
                'persist' => $recruiter !== null,
            ])]
        );

        $rows = $this->buildMatrix($reports);
        $winner = $this->pickWinner($reports);

        return [
            'candidates' => $reports,
            'matrix' => $rows,
            'winner' => $winner,
            'summary' => $this->summary($reports, $winner),
            'generated_by' => 'evidence-engine',
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function buildMatrix(Collection $reports): array
    {
        $rows = [
            ['label' => 'Engineering Magnitude', 'key' => 'magnitude'],
            ['label' => 'Evidence sources analyzed', 'key' => 'evidence_count'],
            ['label' => 'Distinct technologies', 'key' => 'technology_count'],
            ['label' => 'Engineering areas', 'key' => 'area_count'],
            ['label' => 'Verified', 'key' => 'verified'],
            ['label' => 'Verified skills', 'key' => 'verified_skills'],
            ['label' => 'Seniority', 'key' => 'seniority'],
            ['label' => 'Community vouches', 'key' => 'vouches'],
            ['label' => 'Projects shipped', 'key' => 'projects'],
            ['label' => 'Suggested roles', 'key' => 'roles'],
        ];

        $matrix = [];

        foreach ($rows as $row) {
            $matrix[] = [
                'label' => $row['label'],
                'values' => $reports->map(fn ($report) => $this->cellValue($report, $row['key']))->all(),
            ];
        }

        return $matrix;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function cellValue(array $report, string $key): mixed
    {
        return match ($key) {
            'magnitude' => $report['magnitude']['total'].' / 1000 ('.$report['magnitude']['label'].')',
            'evidence_count' => $report['evidence']['count'],
            'technology_count' => count($report['evidence']['technologies']),
            'area_count' => count($report['evidence']['engineering_areas']),
            'verified' => $report['verification']['verified'] ? 'Yes' : 'No',
            'verified_skills' => count($report['verification']['verified_skills']),
            'seniority' => $report['seniority'],
            'vouches' => $report['community']['vouches'],
            'projects' => $report['community']['projects_shipped'],
            'roles' => implode(', ', $report['suggested_roles']),
            default => null,
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $reports
     * @return array<string, mixed>|null
     */
    private function pickWinner(Collection $reports): ?array
    {
        $best = $reports->sortByDesc(fn ($r) => $this->compositeScore($r))->first();

        return $best ? [
            'id' => $best['developer']['id'],
            'name' => $best['developer']['name'],
            'magnitude' => $best['magnitude']['total'],
            'composite' => $this->compositeScore($best),
        ] : null;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function compositeScore(array $report): int
    {
        $magnitude = (int) ($report['magnitude']['total'] / 10);
        $evidence = min(150, $report['evidence']['count'] * 10);
        $verified = $report['verification']['verified'] ? 60 : 0;
        $vouches = min(40, $report['community']['vouches'] * 4);

        return $magnitude + $evidence + $verified + $vouches;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $reports
     * @param  array<string, mixed>|null  $winner
     */
    private function summary(Collection $reports, ?array $winner): string
    {
        if (! $winner) {
            return 'No clear winner could be determined.';
        }

        $count = $reports->count();

        return sprintf(
            'Compared %d candidates across evidence, magnitude, verification, and community signals. %s leads on a composite of evidence-backed signals with an Engineering Magnitude of %d/1000.',
            $count,
            $winner['name'],
            $winner['magnitude'],
        );
    }
}
