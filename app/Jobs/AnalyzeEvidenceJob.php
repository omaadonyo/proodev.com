<?php

namespace App\Jobs;

use App\Enums\EvidenceStatus;
use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Events\EvidenceAnalyzed;
use App\Models\Evidence;
use App\Models\Skill;
use App\Models\TimelineEvent;
use App\Services\Ai\AiService;
use App\Services\EvidenceScoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class AnalyzeEvidenceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public Evidence $evidence) {}

    public function handle(AiService $ai, EvidenceScoutService $scout): void
    {
        if ($this->evidence->status === EvidenceStatus::Analyzing) {
            return;
        }

        $this->evidence->update(['status' => EvidenceStatus::Analyzing]);

        try {
            $material = $this->evidence->metadata['material'] ?? $scout->fetch($this->evidence->url);

            $report = $ai->analyzeEvidence($scout->facts($material), [
                'url' => $this->evidence->url,
                'type' => $this->evidence->type->value,
            ]);

            $this->evidence->analysis()->updateOrCreate([], [
                'summary' => (string) ($report['summary'] ?? ''),
                'technologies' => array_values(array_filter((array) ($report['technologies'] ?? []))),
                'engineering_areas' => array_values(array_filter((array) ($report['engineering_areas'] ?? []))),
                'complexity' => in_array(($report['complexity'] ?? ''), ['simple', 'moderate', 'complex', 'advanced'], true)
                    ? $report['complexity']
                    : 'simple',
                'architecture_observations' => $report['architecture_observations'] ?? null,
                'skills' => array_values(array_filter((array) ($report['skills'] ?? []))),
                'knowledge_domains' => array_values(array_filter((array) ($report['knowledge_domains'] ?? []))),
                'highlights' => array_values(array_filter((array) ($report['highlights'] ?? []))),
                'strengths' => array_values(array_filter((array) ($report['strengths'] ?? []))),
                'references' => array_values(array_filter((array) ($report['references'] ?? []))),
                'generated_by' => (string) ($report['generated_by'] ?? 'rule-based-fallback'),
            ]);

            $this->evidence->update([
                'status' => EvidenceStatus::Ready,
                'metadata' => array_merge($this->evidence->metadata ?? [], ['material' => $material]),
                'ai_score' => $this->score($report),
                'analyzed_at' => now(),
            ]);

            $this->attachSkills($report['skills'] ?? []);
            $this->createTimelineEvent();

            EvidenceAnalyzed::dispatch($this->evidence->fresh());
        } catch (\Throwable $e) {
            $this->evidence->update([
                'status' => EvidenceStatus::Failed,
                'error' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $skills
     */
    private function attachSkills(array $skills): void
    {
        $user = $this->evidence->user;

        foreach (array_slice($skills, 0, 8) as $skill) {
            $name = (string) ($skill['name'] ?? '');
            $confidence = (int) ($skill['confidence'] ?? 60);

            if ($name === '') {
                continue;
            }

            $skillModel = Skill::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'category' => 'evidence'],
            );

            $existing = $user->skills()->where('skill_id', $skillModel->id)->first();

            if ($existing) {
                $level = max((int) $existing->pivot->level, $this->levelForConfidence($confidence));
                $user->skills()->updateExistingPivot($skillModel->id, [
                    'level' => $level,
                    'times_used' => (int) $existing->pivot->times_used + 1,
                ]);
            } else {
                $user->skills()->attach($skillModel->id, [
                    'level' => $this->levelForConfidence($confidence),
                    'times_used' => 1,
                ]);
            }
        }
    }

    private function levelForConfidence(int $confidence): int
    {
        return match (true) {
            $confidence >= 80 => 4,
            $confidence >= 60 => 3,
            $confidence >= 40 => 2,
            default => 1,
        };
    }

    private function createTimelineEvent(): void
    {
        TimelineEvent::create([
            'user_id' => $this->evidence->user_id,
            'type' => TimelineEventType::EvidenceAnalyzed,
            'title' => $this->evidence->title,
            'description' => 'Analyzed '.$this->evidence->type->label().' evidence and extracted an engineering report.',
            'data' => [
                'evidence_id' => $this->evidence->id,
                'complexity' => $this->evidence->analysis->complexity,
            ],
            'target_type' => Evidence::class,
            'target_id' => $this->evidence->id,
            'visibility' => Visibility::Public,
            'occurred_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function score(array $report): int
    {
        $points = 0;

        if (! empty($report['summary'])) {
            $points += 20;
        }

        $points += min(count($report['technologies'] ?? []), 6) * 5;
        $points += min(count($report['engineering_areas'] ?? []), 4) * 5;

        $complexity = match ($report['complexity'] ?? null) {
            'advanced' => 30,
            'complex' => 25,
            'moderate' => 18,
            'simple' => 10,
            default => 10,
        };

        $points += $complexity;

        if (! empty($report['architecture_observations'])) {
            $points += 10;
        }

        if (! empty($report['references'])) {
            $points += 5;
        }

        return min(100, $points);
    }
}
