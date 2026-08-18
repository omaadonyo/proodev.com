<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProvider;
use Illuminate\Support\Str;

class RuleBasedFallbackProvider implements AiProvider
{
    private const KEYWORDS = [
        'laravel' => ['laravel', 'eloquent', 'livewire', 'blade', 'queue', 'pint'],
        'frontend' => ['vue', 'react', 'tailwind', 'css', 'alpine', 'typescript', 'javascript', 'ui'],
        'backend' => ['api', 'http', 'rest', 'endpoint', 'service', 'repository', 'dto'],
        'architecture' => ['architecture', 'pattern', 'modular', 'hexagonal', 'ddd', 'clean', 'design'],
        'databases' => ['sql', 'database', 'index', 'query', 'schema', 'mysql', 'postgres'],
        'devops' => ['docker', 'deploy', 'ci', 'pipeline', 'kubernetes', 'server'],
        'security' => ['security', 'auth', 'csrf', 'injection', 'secret', 'encrypt'],
        'performance' => ['performance', 'cache', 'optimize', 'slow', 'benchmark', 'n+1'],
        'testing' => ['test', 'pest', 'phpunit', 'coverage'],
    ];

    public function complete(string $system, string $prompt, array $context = []): string
    {
        $content = Str::of($context['content'] ?? '')->trim();

        if ($content->length() <= 160) {
            return $content->toString();
        }

        return $content->limit(160)->append('…')->toString();
    }

    public function structured(string $system, string $prompt, array $context = []): array
    {
        if (Str::contains($system, 'engineering intelligence analyst')) {
            return $this->evidenceReport($context['content'] ?? '');
        }

        if (Str::contains($system, 'expert technical recruiter and hiring manager')) {
            return $this->jobPostingReport($context);
        }

        if (Str::contains($system, 'expert technical recruiter')) {
            return $this->jobMatchReport($context);
        }

        $content = (string) ($context['content'] ?? '');

        $sentences = preg_split('/(?<=[.!?])\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $summary = implode(' ', array_slice($sentences, 0, 3));
        $summary = Str::limit($summary, 280);

        $matches = [];
        $categories = [];

        foreach (self::KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains(Str::lower($content), $keyword)) {
                    $matches[] = Str::title($keyword);
                    $categories[$category] = true;
                    break;
                }
            }
        }

        $tags = array_slice(array_unique($matches), 0, 6);

        return [
            'summary' => $summary,
            'highlights' => array_slice($sentences, 0, 5),
            'categories' => array_keys($categories),
            'tags' => $tags,
            'estimated_level' => $this->estimateLevel($content),
            'generated_by' => 'rule-based-fallback',
        ];
    }

    /**
     * Deterministic, evidence-backed engineering report used when AI is unavailable.
     *
     * @return array<string, mixed>
     */
    private function evidenceReport(string $content): array
    {
        $lower = Str::lower($content);

        $sentences = preg_split('/(?<=[.!?])\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $summary = Str::limit(implode(' ', array_slice($sentences, 0, 3)), 300);

        $technologies = [];
        $areas = [];

        foreach (self::KEYWORDS as $category => $keywords) {
            $hit = null;

            foreach ($keywords as $keyword) {
                if (Str::contains($lower, $keyword)) {
                    $hit = $keyword;
                    break;
                }
            }

            if ($hit) {
                $technologies[] = Str::title($hit);
                $areas[] = $this->areaFor($category);
            }
        }

        $technologies = array_values(array_unique($technologies));
        $areas = array_values(array_unique($areas));

        $complexity = match (true) {
            count($areas) >= 4 => 'advanced',
            count($areas) >= 2 => 'complex',
            count($areas) >= 1 => 'moderate',
            default => 'simple',
        };

        $references = array_values(array_map(
            fn ($sentence) => ['claim' => Str::limit($sentence, 140), 'reference' => $sentence],
            array_slice($sentences, 0, 3),
        ));

        return [
            'summary' => $summary ?: 'Technical material was captured for the evidence library.',
            'technologies' => $technologies,
            'engineering_areas' => $areas,
            'complexity' => $complexity,
            'architecture_observations' => $areas !== [] ? 'Observed a '.implode(', ', $areas).' engineering profile across the captured material.' : null,
            'skills' => array_values(array_map(fn ($tech) => ['name' => $tech, 'confidence' => 60], $technologies)),
            'knowledge_domains' => $areas,
            'highlights' => array_slice($sentences, 0, 4),
            'strengths' => array_slice($sentences, 0, 2),
            'references' => $references,
            'generated_by' => 'rule-based-fallback',
        ];
    }

    private function areaFor(string $category): string
    {
        return match ($category) {
            'laravel' => 'Backend Engineering',
            'frontend' => 'Frontend Engineering',
            'backend' => 'API Engineering',
            'architecture' => 'Software Architecture',
            'databases' => 'Data Engineering',
            'devops' => 'DevOps',
            'security' => 'Security Engineering',
            'performance' => 'Performance Engineering',
            'testing' => 'Testing & Quality',
            default => 'Software Engineering',
        };
    }

    /**
     * Deterministic job fit analysis used when AI is unavailable.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function jobMatchReport(array $context): array
    {
        $profile = Str::lower((string) ($context['profile'] ?? ''));
        $job = Str::lower((string) ($context['job'] ?? ''));

        $profileSkills = $this->matchKeywords($profile);
        $jobKeywords = $this->matchKeywords($job);

        $matched = array_values(array_intersect($profileSkills, $jobKeywords));
        $missing = array_values(array_diff($jobKeywords, $profileSkills));

        $total = count($jobKeywords);
        $score = $total > 0 ? (int) round(count($matched) / $total * 100) : 0;

        $recommendation = match (true) {
            $score >= 70 => 'strong_match',
            $score >= 35 => 'possible_match',
            default => 'weak_match',
        };

        return [
            'score' => $score,
            'summary' => $this->matchSummary($score, $matched, $missing),
            'matched_skills' => array_slice($matched, 0, 8),
            'missing_skills' => array_slice($missing, 0, 8),
            'recommendation' => $recommendation,
            'strengths' => array_map(fn ($skill) => "Demonstrated experience with {$skill}.", array_slice($matched, 0, 3)),
            'generated_by' => 'rule-based-fallback',
        ];
    }

    /**
     * Collect recognizable skill/technology keywords mentioned in a blob of text.
     *
     * @return array<int, string>
     */
    private function matchKeywords(string $text): array
    {
        $keywords = collect(self::KEYWORDS)->flatten()->merge([
            'laravel', 'php', 'python', 'javascript', 'typescript', 'react', 'vue', 'livewire', 'tailwind',
            'mysql', 'postgres', 'redis', 'docker', 'kubernetes', 'aws', 'git', 'graphql', 'testing',
        ])->unique();

        return $keywords
            ->filter(fn ($keyword) => Str::contains($text, $keyword))
            ->map(fn ($keyword) => Str::title($keyword))
            ->values()
            ->all();
    }

    private function matchSummary(int $score, array $matched, array $missing): string
    {
        $found = $matched !== []
            ? 'Their profile covers '.implode(', ', array_slice($matched, 0, 3)).'.'
            : 'Their profile shows little direct overlap with this role.';

        if ($score >= 70) {
            return "Strong fit for this role. {$found}";
        }

        if ($score >= 35) {
            $gap = $missing !== []
                ? ' They may need to brush up on '.implode(', ', array_slice($missing, 0, 3)).'.'
                : '';

            return "A possible match worth exploring. {$found}{$gap}";
        }

        return "Weak match overall. {$found}";
    }

    private function estimateLevel(string $content): string
    {
        $lower = Str::lower($content);

        if (Str::contains($lower, ['index', 'cache', 'n+1', 'deadlock', 'locking', 'sharding'])) {
            return 'advanced';
        }

        if (Str::contains($lower, ['queue', 'docker', 'architecture', 'pattern'])) {
            return 'intermediate';
        }

        return 'beginner';
    }

    /**
     * Deterministic job-posting draft used when AI is unavailable.
     *
     * @return array<string, mixed>
     */
    private function jobPostingReport(array $context): array
    {
        $brief = (string) ($context['content'] ?? '');
        $company = $context['company'] ?? [];

        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($brief), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $description = trim($brief) !== '' ? trim($brief) : 'We are hiring an experienced engineer to join our team.';
        $description = Str::limit($description, 1200);

        $companyParts = [
            $company['name'] ?? null,
            $company['description'] ?? null,
            $company['industry'] ?? null,
            $company['location'] ?? null,
        ];

        $companyParts = array_values(array_filter(array_map('trim', $companyParts), fn ($part) => $part !== ''));

        if ($companyParts !== []) {
            $about = 'About the company: '.implode(' — ', $companyParts);

            $description = Str::limit($description, 900)
                ."\n\n".Str::limit($about, 260);
        }

        $keywords = [];

        foreach (self::KEYWORDS as $category => $candidates) {
            foreach ($candidates as $candidate) {
                if (Str::contains(Str::lower($brief), $candidate)) {
                    $keywords[] = Str::title($candidate);
                }
            }
        }

        $keywords = array_values(array_unique($keywords));

        if ($keywords === []) {
            $keywords = ['Software Engineering'];
        }

        $requirements = array_merge(
            array_map(fn ($keyword) => "Strong experience with {$keyword}", array_slice($keywords, 0, 4)),
            ['Collaborate with a cross-functional team and ship iteratively'],
        );

        $title = $sentences !== []
            ? Str::title(Str::limit($sentences[0], 60))
            : 'Software Engineer';

        return [
            'title' => $title,
            'description' => $description,
            'requirements' => $requirements,
            'location' => null,
            'is_remote' => true,
            'employment_type' => 'full-time',
            'salary_min' => null,
            'salary_max' => null,
            'currency' => 'USD',
            'deadline' => null,
            'generated_by' => 'rule-based-fallback',
        ];
    }
}
