<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\Vouch;
use Illuminate\Support\Collection;

class EngineeringMagnitudeService
{
    /**
     * Compute the engineering magnitude score (0–1000) with an explainable
     * factor breakdown. Every point is backed by evidence — nothing is
     * self-reported or opaque.
     *
     * @return array{
     *     total: int,
     *     factors: array<string, array{label: string, points: int, max: int, weight: int, description: string, evidence: array<int, string>}>
     * }
     */
    public function breakdown(User $user): array
    {
        $evidence = $user->evidence()->ready()->with('analysis')->get();
        $projects = $user->projects()->published()->get();
        $journal = $user->journalEntries()->publiclyVisible()->get();
        $vouches = $user->vouchesReceived()->where('status', 'approved')->get();
        $skills = $user->skills()->get();
        $achievements = $user->achievements()->wherePivotNotNull('awarded_at')->get();
        $verifications = $user->verificationRequests()->where('status', 'approved')->get();

        $factors = [];

        $factors['evidence_quality'] = $this->factor(
            label: 'Evidence Quality',
            max: 200,
            weight: 20,
            description: 'Significance of the analyzed evidence sources in your library.',
            points: $this->evidenceQualityPoints($evidence),
            evidence: $evidence->take(5)->map->title->all(),
        );

        $factors['technical_depth'] = $this->factor(
            label: 'Technical Depth',
            max: 150,
            weight: 15,
            description: 'Complexity and architectural weight of your published work.',
            points: $this->technicalDepthPoints($projects, $evidence),
            evidence: $projects->take(5)->pluck('title')->all(),
        );

        $factors['knowledge_sharing'] = $this->factor(
            label: 'Knowledge Sharing',
            max: 150,
            weight: 15,
            description: 'Articles, journal entries, talks, and documentation you have shared.',
            points: $this->knowledgeSharingPoints($journal, $evidence),
            evidence: $journal->take(5)->pluck('title')->map(fn ($t) => $t ?: 'Journal entry')->all(),
        );

        $factors['breadth_of_expertise'] = $this->factor(
            label: 'Breadth of Expertise',
            max: 100,
            weight: 10,
            description: 'Distinct engineering areas and technologies backed by evidence.',
            points: $this->breadthPoints($evidence, $skills),
            evidence: $this->areasFromEvidence($evidence)->take(6)->values()->all(),
        );

        $factors['consistency'] = $this->factor(
            label: 'Consistency',
            max: 100,
            weight: 10,
            description: 'Sustained engineering activity over time.',
            points: $this->consistencyPoints($user),
            evidence: ['Current streak: '.$user->streak_count.' days'],
        );

        $factors['community_trust'] = $this->factor(
            label: 'Community Trust',
            max: 150,
            weight: 15,
            description: 'Evidence-weighted vouches and recognitions from the community.',
            points: $this->communityTrustPoints($vouches, $projects),
            evidence: $vouches->take(5)->map(fn ($v) => $v->type->label().' from '.$v->voucher->name)->all(),
        );

        $factors['verification'] = $this->factor(
            label: 'Verification',
            max: 100,
            weight: 10,
            description: 'Verified expertise and officially approved credentials.',
            points: $this->verificationPoints($verifications, $skills),
            evidence: $verifications->take(5)->map(fn ($v) => $v->label ?: $v->type->label())->all(),
        );

        $factors['contribution_history'] = $this->factor(
            label: 'Open Source Contribution',
            max: 50,
            weight: 5,
            description: 'Open-source signals: repos, packages, and public contributions.',
            points: $this->contributionPoints($evidence),
            evidence: [
                count($evidence->where('source', 'github')).' open-source sources',
            ],
        );

        $total = (int) min(1000, collect($factors)->sum('points'));

        return [
            'total' => $total,
            'factors' => $factors,
        ];
    }

    public function labelFor(int $total): string
    {
        return match (true) {
            $total >= 800 => 'Exceptional',
            $total >= 600 => 'Distinguished',
            $total >= 450 => 'Proven',
            $total >= 300 => 'Established',
            $total >= 150 => 'Building',
            default => 'Emerging',
        };
    }

    public function percentile(int $total): int
    {
        $buckets = [800 => 95, 600 => 80, 450 => 60, 300 => 40, 150 => 20, 0 => 5];

        foreach ($buckets as $threshold => $percentile) {
            if ($total >= $threshold) {
                return $percentile;
            }
        }

        return 0;
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     */
    private function evidenceQualityPoints(Collection $evidence): int
    {
        if ($evidence->isEmpty()) {
            return 0;
        }

        $scores = $evidence->map(fn ($e) => (int) $e->ai_score)->filter();

        if ($scores->isEmpty()) {
            return min(50, $evidence->count() * 10);
        }

        $quality = (int) $scores->avg();
        $count = $evidence->count();

        return min(200, (int) round($quality * 1.5 + $count * 8));
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, Evidence>  $evidence
     */
    private function technicalDepthPoints(Collection $projects, Collection $evidence): int
    {
        $complexity = $evidence->map(fn ($e) => $e->analysis?->complexity);

        $weight = $complexity->map(fn ($c) => match ($c) {
            'advanced' => 30,
            'complex' => 22,
            'moderate' => 14,
            default => 8,
        })->sum();

        $projectsWeight = $projects->count() * 6;
        $decisions = $projects->sum(fn ($p) => count($p->engineering_decisions ?? []));

        return min(150, $weight + $projectsWeight + $decisions * 2);
    }

    /**
     * @param  Collection<int, JournalEntry>  $journal
     * @param  Collection<int, Evidence>  $evidence
     */
    private function knowledgeSharingPoints(Collection $journal, Collection $evidence): int
    {
        $articles = $evidence->filter(fn ($e) => in_array($e->type->value, [
            'technical-article', 'blog-post', 'documentation', 'conference-talk', 'technical-video', 'technical-presentation',
        ], true));

        $sharing = $journal->count() * 5 + $articles->count() * 10;
        $journalAi = $journal->where('ai_processed', true)->count() * 3;

        return min(150, $sharing + $journalAi);
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     * @param  Collection<int, Skill>  $skills
     */
    private function breadthPoints(Collection $evidence, Collection $skills): int
    {
        $areas = $this->areasFromEvidence($evidence);
        $tech = $evidence->flatMap(fn ($e) => $e->analysis?->technologies ?? [])->unique();

        return min(100, $areas->count() * 10 + $tech->take(6)->count() * 5 + $skills->count() * 2);
    }

    private function consistencyPoints(User $user): int
    {
        return min(100, $user->streak_count * 4 + min($user->longest_streak, 50) / 2);
    }

    /**
     * @param  Collection<int, Vouch>  $vouches
     * @param  Collection<int, Project>  $projects
     */
    private function communityTrustPoints(Collection $vouches, Collection $projects): int
    {
        $vouchWeight = $vouches->sum('weight') * 10;
        $recognitions = $projects->sum('recognition_count') * 2;

        return min(150, $vouchWeight + $recognitions);
    }

    /**
     * @param  Collection<int, VerificationRequest>  $verifications
     * @param  Collection<int, Skill>  $skills
     */
    private function verificationPoints(Collection $verifications, Collection $skills): int
    {
        $verifiedSkills = $skills->filter(fn ($s) => $s->pivot->verified_at !== null);

        return min(100, $verifications->count() * 20 + $verifiedSkills->count() * 10);
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     */
    private function contributionPoints(Collection $evidence): int
    {
        $openSource = $evidence->filter(fn ($e) => in_array($e->source, ['github', 'gitlab', 'bitbucket'], true))->count();

        return min(50, $openSource * 8);
    }

    /**
     * @param  Collection<int, Evidence>  $evidence
     * @return Collection<int, string>
     */
    private function areasFromEvidence(Collection $evidence): Collection
    {
        return $evidence
            ->flatMap(fn ($e) => $e->analysis?->engineering_areas ?? [])
            ->unique()
            ->values();
    }

    /**
     * @param  array<int, string>  $evidence
     * @return array{label: string, points: int, max: int, weight: int, description: string, evidence: array<int, string>}
     */
    private function factor(string $label, int $max, int $weight, string $description, int $points, array $evidence): array
    {
        return [
            'label' => $label,
            'points' => min($max, max(0, $points)),
            'max' => $max,
            'weight' => $weight,
            'description' => $description,
            'evidence' => $evidence,
        ];
    }
}
