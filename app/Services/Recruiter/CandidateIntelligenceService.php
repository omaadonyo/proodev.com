<?php

namespace App\Services\Recruiter;

use App\Models\Evidence;
use App\Models\User;
use App\Services\Ai\AiService;
use App\Services\EngineeringMagnitudeService;
use Illuminate\Support\Collection;

/**
 * The evidence engine behind every recruiter intelligence feature.
 *
 * Produces a fully explainable candidate report derived from analyzed
 * evidence. When an OpenAI driver is configured the report is enriched
 * with a generated narrative; otherwise every section stays rule-based
 * and evidence-linked so the feature works identically offline.
 */
class CandidateIntelligenceService
{
    public const CACHE_TTL_MINUTES = 60;

    public function __construct(
        private EngineeringMagnitudeService $magnitude,
        private AiService $ai,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function report(User $candidate, array $options = []): array
    {
        $fresh = (bool) ($options['fresh'] ?? false);
        $persist = (bool) ($options['persist'] ?? true);
        $recruiter = $options['recruiter'] ?? null;

        if ($recruiter && ! $fresh) {
            $cached = $recruiter->candidateIntelligenceReports()
                ->where('candidate_id', $candidate->id)
                ->first();

            if ($cached && $cached->isFresh()) {
                return $cached->report;
            }
        }

        $magnitude = $this->magnitude->breakdown($candidate);
        $evidence = $candidate->evidence()->ready()->with('analysis')->get();
        $projects = $candidate->projects()->published()->get();
        $vouches = $candidate->vouchesReceived()->where('status', 'approved')->with(['voucher', 'skill'])->get();
        $skills = $candidate->skills()->get();
        $verifications = $candidate->verificationRequests()->where('status', 'approved')->get();

        $strengths = $this->deriveStrengths($magnitude['factors'], $evidence);
        $weaknesses = $this->deriveWeaknesses($magnitude['factors'], $candidate);

        $report = [
            'developer' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'handle' => $candidate->handle(),
                'headline' => $candidate->headline,
                'location' => $candidate->location,
                'timezone' => $candidate->timezone,
                'avatar' => $candidate->avatarUrl(),
                'passport_url' => route('passport', $candidate->handle()),
                'reputation' => (int) $candidate->reputation_score,
            ],
            'summary' => $this->buildSummary($candidate, $magnitude, $evidence),
            'magnitude' => [
                'total' => $magnitude['total'],
                'label' => $this->magnitude->labelFor($magnitude['total']),
                'percentile' => $this->magnitude->percentile($magnitude['total']),
                'factors' => $magnitude['factors'],
            ],
            'verification' => [
                'verified' => $candidate->isVerified() || $verifications->isNotEmpty(),
                'verification_count' => $verifications->count(),
                'approved_requests' => $verifications->map(fn ($v) => [
                    'label' => $v->label ?: $v->type->label(),
                    'type' => $v->type->value,
                ])->all(),
                'verified_skills' => $skills
                    ->filter(fn ($s) => $s->pivot->verified_at !== null)
                    ->pluck('name')
                    ->values()
                    ->all(),
            ],
            'skills' => $skills->map(fn ($s) => [
                'name' => $s->name,
                'level' => $s->pivot->level,
                'verified' => $s->pivot->verified_at !== null,
                'times_used' => (int) $s->pivot->times_used,
            ])->values()->all(),
            'evidence' => $this->evidenceSection($evidence),
            'community' => [
                'vouches' => $vouches->count(),
                'vouch_weight' => (int) $vouches->sum('weight'),
                'vouch_types' => $vouches->countBy(fn ($v) => $v->type->value)->all(),
                'recognitions' => (int) $projects->sum('recognition_count'),
                'achievements' => $candidate->achievements()->wherePivotNotNull('awarded_at')->count(),
                'projects_shipped' => $projects->count(),
                'streak' => (int) $candidate->streak_count,
            ],
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'suggested_roles' => $this->suggestRoles($evidence),
            'seniority' => $this->seniorityFor($magnitude['total'], $projects->count(), $skills->count()),
            'confidence' => $this->confidenceFor($evidence, $verifications),
            'generated_by' => 'evidence-engine',
            'generated_at' => now()->toIso8601String(),
        ];

        $report = $this->enrichWithAi($report, $candidate);

        if ($recruiter && $persist) {
            $recruiter->candidateIntelligenceReports()->updateOrCreate(
                ['candidate_id' => $candidate->id],
                [
                    'workspace_id' => app(WorkspaceService::class)->currentId($recruiter),
                    'report' => $report,
                    'generated_by' => $report['generated_by'],
                    'expires_at' => now()->addMinutes(self::CACHE_TTL_MINUTES),
                ],
            );
        }

        return $report;
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     * @return array<string, mixed>
     */
    private function evidenceSection(Collection $evidence): array
    {
        $technologies = $evidence
            ->flatMap(fn ($e) => $e->analysis?->technologies ?? [])
            ->unique()
            ->values()
            ->take(30)
            ->all();

        $areas = $evidence
            ->flatMap(fn ($e) => $e->analysis?->engineering_areas ?? [])
            ->unique()
            ->values()
            ->take(20)
            ->all();

        $domains = $evidence
            ->flatMap(fn ($e) => $e->analysis?->knowledge_domains ?? [])
            ->unique()
            ->values()
            ->take(20)
            ->all();

        $complexity = $this->dominantComplexity($evidence);

        $top = $evidence->sortByDesc(fn ($e) => $e->ai_score ?? 0)
            ->take(6)
            ->values()
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'url' => $e->url,
                'source' => $e->source,
                'type' => $e->type->value,
                'type_label' => $e->type->label(),
                'ai_score' => (int) $e->ai_score,
                'complexity' => $e->analysis?->complexity,
                'summary' => $e->analysis?->summary,
                'technologies' => $e->analysis?->technologies ?? [],
                'areas' => $e->analysis?->engineering_areas ?? [],
                'highlights' => array_slice($e->analysis?->highlights ?? [], 0, 3),
                'references' => array_slice($e->analysis?->references ?? [], 0, 3),
                'analyzed_at' => $e->analyzed_at?->toIso8601String(),
            ])
            ->all();

        return [
            'count' => $evidence->count(),
            'sources' => $evidence->countBy(fn ($e) => $e->source ?: 'web')->all(),
            'types' => $evidence->countBy(fn ($e) => $e->type->label())->all(),
            'technologies' => $technologies,
            'engineering_areas' => $areas,
            'knowledge_domains' => $domains,
            'complexity' => $complexity,
            'avg_score' => $evidence->isEmpty()
                ? 0
                : (int) round($evidence->map(fn ($e) => (int) $e->ai_score)->avg()),
            'top' => $top,
        ];
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     */
    private function dominantComplexity(Collection $evidence): string
    {
        $counts = $evidence->countBy(fn ($e) => $e->analysis?->complexity ?: 'simple');

        return match (true) {
            ($counts['advanced'] ?? 0) >= 2 => 'advanced',
            ($counts['complex'] ?? 0) >= 2 => 'complex',
            ($counts['moderate'] ?? 0) >= 2 => 'moderate',
            $evidence->isEmpty() => 'none',
            default => 'simple',
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $factors
     * @param  Collection<int, Evidence>  $evidence
     * @return array<int, string>
     */
    private function deriveStrengths(array $factors, Collection $evidence): array
    {
        $strengths = [];

        $ranked = collect($factors)
            ->sortByDesc(fn ($f) => $f['max'] > 0 ? ($f['points'] / $f['max']) : 0)
            ->filter(fn ($f) => $f['max'] > 0 && ($f['points'] / $f['max']) >= 0.7);

        foreach ($ranked->take(3) as $factor) {
            $strengths[] = $factor['label'].' - '.$factor['description'].' ('.$factor['points'].'/'.$factor['max'].' points).';
        }

        $topAreas = $evidence
            ->flatMap(fn ($e) => $e->analysis?->engineering_areas ?? [])
            ->countBy()
            ->sortDesc()
            ->take(3);

        foreach ($topAreas as $area => $count) {
            $strengths[] = "Deep, evidence-backed expertise in {$area} ({$count} analyzed sources).";
        }

        return array_slice(array_values(array_unique($strengths)), 0, 6);
    }

    /**
     * @param  array<string, array<string, mixed>>  $factors
     * @return array<int, string>
     */
    private function deriveWeaknesses(array $factors, User $candidate): array
    {
        $weaknesses = [];

        $ranked = collect($factors)
            ->sortBy(fn ($f) => $f['max'] > 0 ? ($f['points'] / $f['max']) : 1)
            ->filter(fn ($f) => $f['max'] > 0);

        foreach ($ranked->take(2) as $factor) {
            if (($factor['points'] / $factor['max']) < 0.4) {
                $weaknesses[] = $factor['label'].' is thin ('.$factor['points'].'/'.$factor['max'].' points) - '.$factor['description'];
            }
        }

        $evidenceCount = $candidate->evidence()->ready()->count();

        if ($evidenceCount < 3) {
            $weaknesses[] = 'Small evidence library ('.$evidenceCount.' analyzed sources) - hard to judge depth reliably.';
        }

        if ($candidate->skills()->wherePivotNotNull('verified_at')->count() === 0) {
            $weaknesses[] = 'No verified skills yet - proficiency is inferred from evidence, not confirmed by an authority.';
        }

        return array_values($weaknesses);
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     * @return array<int, string>
     */
    private function suggestRoles(Collection $evidence): array
    {
        $areas = $evidence->flatMap(fn ($e) => $e->analysis?->engineering_areas ?? []);

        $roleMap = [
            'Backend Engineering' => 'Backend Engineer',
            'Frontend Engineering' => 'Frontend Engineer',
            'API Engineering' => 'API / Integrations Engineer',
            'Software Architecture' => 'Software Architect',
            'Data Engineering' => 'Data Engineer',
            'DevOps' => 'DevOps Engineer',
            'Security Engineering' => 'Security Engineer',
            'Performance Engineering' => 'Performance Engineer',
            'Testing & Quality' => 'QA / Test Engineer',
        ];

        $roles = [];

        foreach ($roleMap as $area => $role) {
            if ($areas->contains($area)) {
                $roles[] = $role;
            }
        }

        return array_slice(array_values(array_unique($roles)), 0, 4);
    }

    public function seniorityFor(int $magnitudeTotal, int $projectsCount, int $skillsCount): string
    {
        if ($magnitudeTotal >= 700 && $projectsCount >= 5) {
            return 'Staff / Principal';
        }

        if ($magnitudeTotal >= 500 && $projectsCount >= 3) {
            return 'Senior';
        }

        if ($magnitudeTotal >= 300 && $skillsCount >= 4) {
            return 'Mid-level';
        }

        if ($magnitudeTotal >= 150) {
            return 'Junior / Associate';
        }

        return 'Entry';
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     * @param  \Illuminate\Database\Eloquent\Collection<int, mixed>  $verifications
     */
    private function confidenceFor(Collection $evidence, $verifications): int
    {
        $score = 30;

        $score += min(40, $evidence->count() * 6);
        $score += min(20, (int) $evidence->map(fn ($e) => (int) $e->ai_score)->avg() / 5);
        $score += min(10, $verifications->count() * 5);

        return min(100, $score);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function enrichWithAi(array $report, User $candidate): array
    {
        if (! $this->ai->available()) {
            return $report;
        }

        $narrative = $this->ai->complete(
            'You are an expert technical recruiter writing an evidence-based candidate summary. Stay strictly factual - never invent claims beyond the provided facts.',
            'Write a 2-3 sentence recruiter-ready summary of this candidate.',
            ['profile' => json_encode([
                'name' => $report['developer']['name'],
                'headline' => $report['developer']['headline'],
                'magnitude' => $report['magnitude']['label'].' ('.$report['magnitude']['total'].'/1000)',
                'seniority' => $report['seniority'],
                'skills' => $report['skills'],
                'evidence' => $report['evidence'],
            ])],
        );

        $report['summary'] = $narrative ?: $report['summary'];
        $report['generated_by'] = 'openai';

        return $report;
    }

    /**
     * @param  array<string, mixed>  $magnitude
     * @param  Collection<int, Evidence>  $evidence
     */
    private function buildSummary(User $candidate, array $magnitude, Collection $evidence): string
    {
        $evidenceCount = $evidence->count();
        $topAreas = $evidence
            ->flatMap(fn ($e) => $e->analysis?->engineering_areas ?? [])
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(3);

        $areaPhrase = $topAreas->isNotEmpty()
            ? 'Their analyzed work clusters around '.implode(', ', $topAreas->all()).'.'
            : 'They have not yet published analyzed engineering evidence.';

        $magnitudeLabel = $this->magnitude->labelFor($magnitude['total']);

        return sprintf(
            '%s is a %s engineer with an Engineering Magnitude of %d/1000 (%s). %d evidence sources have been analyzed and scored. %s',
            $candidate->name,
            $this->seniorityFor($magnitude['total'], $candidate->projects()->published()->count(), $candidate->skills()->count()),
            $magnitude['total'],
            $magnitudeLabel,
            $evidenceCount,
            $areaPhrase,
        );
    }
}
