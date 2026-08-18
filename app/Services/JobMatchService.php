<?php

namespace App\Services;

use App\Enums\EvidenceStatus;
use App\Models\Job;
use App\Models\JobMatch;
use App\Models\User;
use App\Services\Ai\AiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class JobMatchService
{
    public function __construct(private AiService $ai) {}

    /**
     * Build a compact, evidence-backed digest of the developer's profile.
     */
    public function profileDigest(User $user): string
    {
        $skills = $user->skills()
            ->orderByDesc('user_skills.level')
            ->limit(25)
            ->pluck('name');

        $evidence = $user->evidence()
            ->where('status', EvidenceStatus::Ready)
            ->with('analysis')
            ->latest('analyzed_at')
            ->limit(12)
            ->get();

        $technologies = $evidence
            ->flatMap(fn ($item) => $item->analysis?->technologies ?? [])
            ->unique()
            ->values()
            ->take(20);

        $areas = $evidence
            ->flatMap(fn ($item) => $item->analysis?->engineering_areas ?? [])
            ->unique()
            ->values()
            ->take(10);

        $projects = $user->projects()
            ->where('published_at', '!=', null)
            ->latest('published_at')
            ->limit(8)
            ->get();

        $projectTech = $projects
            ->flatMap(fn ($project) => $project->tech_stack ?? [])
            ->unique()
            ->values()
            ->take(15);

        $vouches = $user->approvedVouchesReceived()
            ->with('skill')
            ->get()
            ->pluck('skill.name')
            ->filter()
            ->unique()
            ->take(10);

        return collect([
            'Headline: '.($user->headline ?: 'Not provided'),
            'Bio: '.Str::limit((string) $user->bio, 600),
            'Level: '.$user->levelTitle().' ('.$user->level().')',
            'Experience points: '.number_format($user->experience_points),
            'Reputation score: '.number_format($user->reputation_score),
            'Skills: '.$skills->implode(', '),
            'Evidence technologies: '.$technologies->implode(', '),
            'Engineering areas: '.$areas->implode(', '),
            'Project technologies: '.$projectTech->implode(', '),
            'Vouched skills: '.$vouches->implode(', '),
        ])->filter(fn ($line) => Str::after($line, ': ') !== '')->implode("\n");
    }

    /**
     * Build a digest of the job posting for comparison.
     */
    public function jobDigest(Job $job): string
    {
        return collect([
            'Company: '.($job->company?->name ?? 'Unknown company'),
            'Title: '.$job->title,
            'Employment type: '.($job->employment_type ?: 'full-time'),
            'Location: '.($job->is_remote ? 'Remote' : ($job->location ?: 'On-site')),
            'Salary: '.($job->salaryRange() ?: 'Not disclosed'),
            'Description: '.Str::limit((string) $job->description, 6000),
            'Requirements: '.($job->requirements ? implode('; ', $job->requirements) : 'None listed'),
        ])->implode("\n");
    }

    /**
     * Fetch a cached match for a user/job pair, if one exists.
     */
    public function cached(User $user, Job $job): ?JobMatch
    {
        return JobMatch::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();
    }

    /**
     * Fetch all cached matches for a user keyed by job id.
     *
     * @param  array<int, int>  $jobIds
     * @return Collection<int, JobMatch>
     */
    public function cachedForUser(User $user, array $jobIds): Collection
    {
        return JobMatch::where('user_id', $user->id)
            ->whereIn('job_id', $jobIds)
            ->get()
            ->keyBy('job_id');
    }

    /**
     * Return an existing match or run a fresh analysis and persist it.
     */
    public function match(User $user, Job $job, bool $force = false): JobMatch
    {
        if (! $force) {
            $cached = $this->cached($user, $job);

            if ($cached) {
                return $cached;
            }
        }

        $result = $this->analyze($user, $job);

        return JobMatch::updateOrCreate(
            ['user_id' => $user->id, 'job_id' => $job->id],
            [
                'score' => $result['score'],
                'recommendation' => $result['recommendation'],
                'summary' => $result['summary'],
                'matched_skills' => $result['matched_skills'],
                'missing_skills' => $result['missing_skills'],
                'generated_by' => $result['generated_by'],
                'analyzed_at' => now(),
            ],
        );
    }

    /**
     * Run an AI job fit analysis. Falls back to a deterministic rule-based
     * comparison when the AI driver is unavailable.
     *
     * @return array<string, mixed>
     */
    public function analyze(User $user, Job $job): array
    {
        if ($this->ai->available()) {
            $report = $this->ai->matchJob($this->profileDigest($user), $this->jobDigest($job), [
                'user' => $user->name,
                'job_title' => $job->title,
            ]);

            return $this->normalize($report);
        }

        return $this->ruleBased($this->profileKeywords($user), $job);
    }

    /**
     * A cheap, deterministic fit score used for list previews without AI.
     *
     * @return array<string, mixed>
     */
    public function quickScore(User $user, Job $job): array
    {
        return $this->ruleBased($this->profileKeywords($user), $job);
    }

    /**
     * Same as quickScore but with precomputed profile keywords, so a list of
     * jobs can be scored without repeating the profile queries per job.
     *
     * @param  array<int, string>  $profileKeywords
     * @return array<string, mixed>
     */
    public function quickScoreWithProfile(array $profileKeywords, Job $job): array
    {
        return $this->ruleBased($profileKeywords, $job);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function normalize(array $report): array
    {
        $score = max(0, min(100, (int) ($report['score'] ?? 0)));
        $recommendation = in_array($report['recommendation'] ?? null, ['strong_match', 'possible_match', 'weak_match'], true)
            ? $report['recommendation']
            : $this->recommendationFor($score);

        return [
            'score' => $score,
            'recommendation' => $recommendation,
            'summary' => Str::limit((string) ($report['summary'] ?? 'No summary provided.'), 500),
            'matched_skills' => $this->strings($report['matched_skills'] ?? []),
            'missing_skills' => $this->strings($report['missing_skills'] ?? []),
            'generated_by' => 'ai',
        ];
    }

    /**
     * Deterministic keyword-overlap fit analysis.
     *
     * @param  array<int, string>  $profileKeywords
     * @return array<string, mixed>
     */
    private function ruleBased(array $profileKeywords, Job $job): array
    {
        $jobKeywords = $this->jobKeywords($job);

        $matched = array_values(array_intersect($profileKeywords, $jobKeywords));
        $missing = array_values(array_diff($jobKeywords, $profileKeywords));

        $total = count($jobKeywords);
        $score = $total > 0 ? (int) round(count($matched) / $total * 100) : 0;

        $display = fn (array $skills) => array_map(fn ($skill) => Str::title($skill), array_slice($skills, 0, 8));

        return [
            'score' => $score,
            'recommendation' => $this->recommendationFor($score),
            'summary' => $this->summaryFor($score, $matched, $missing),
            'matched_skills' => $display($matched),
            'missing_skills' => $display($missing),
            'generated_by' => 'rule-based-fallback',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function profileKeywords(User $user): array
    {
        $keywords = collect();

        foreach ($user->skills()->limit(30)->get() as $skill) {
            $keywords->push(Str::lower($skill->name));
            $keywords->push(Str::slug($skill->name));
        }

        $evidence = $user->evidence()
            ->where('status', EvidenceStatus::Ready)
            ->with('analysis')
            ->latest('analyzed_at')
            ->limit(15)
            ->get();

        foreach ($evidence as $item) {
            foreach (array_merge($item->analysis?->technologies ?? [], $item->analysis?->engineering_areas ?? []) as $tech) {
                $keywords->push(Str::lower((string) $tech));
            }
        }

        foreach ($user->projects()->whereNotNull('published_at')->limit(10)->get() as $project) {
            foreach ($project->tech_stack ?? [] as $tech) {
                $keywords->push(Str::lower((string) $tech));
            }
        }

        foreach ($user->approvedVouchesReceived()->with('skill')->get() as $vouch) {
            if ($vouch->skill) {
                $keywords->push(Str::lower($vouch->skill->name));
            }
        }

        return $keywords->unique()->values()->all();
    }

    /**
     * Extract recognizable skill keywords from arbitrary job text. Shared by
     * the recruiter job matcher and the public landing-page matcher.
     *
     * @return array<int, string>
     */
    public function keywordsFromText(string $text): array
    {
        $keywords = collect();
        $text = Str::lower($text);

        $lookups = [
            'laravel' => ['laravel', 'eloquent', 'livewire', 'blade'],
            'php' => ['php'],
            'python' => ['python'],
            'javascript' => ['javascript', 'js'],
            'typescript' => ['typescript', 'ts'],
            'react' => ['react'],
            'vue' => ['vue'],
            'node' => ['node'],
            'tailwind' => ['tailwind'],
            'mysql' => ['mysql'],
            'postgres' => ['postgres', 'postgresql'],
            'redis' => ['redis'],
            'docker' => ['docker'],
            'kubernetes' => ['kubernetes', 'k8s'],
            'aws' => ['aws'],
            'git' => ['git', 'github'],
            'graphql' => ['graphql'],
            'testing' => ['testing', 'pest', 'phpunit', 'jest', 'cypress'],
            'api' => ['api', 'rest', 'http'],
            'security' => ['security', 'auth', 'oauth'],
            'devops' => ['devops', 'ci/cd', 'pipeline'],
            'architecture' => ['architecture', 'system design'],
            'agile' => ['agile', 'scrum'],
        ];

        foreach ($lookups as $keyword => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($text, $needle)) {
                    $keywords->push($keyword);
                    break;
                }
            }
        }

        return $keywords->unique()->values()->all();
    }

    /**
     * Fetch a job posting URL, strip it to readable text, and extract skills.
     *
     * @return array<int, string>
     *
     * @throws RuntimeException when the URL cannot be fetched
     */
    public function keywordsFromUrl(string $url): array
    {
        $response = Http::withHeaders(['User-Agent' => 'ProoDev-JobMatcher'])
            ->timeout(15)
            ->get($url);

        if ($response->failed()) {
            throw new RuntimeException('Could not fetch that URL (HTTP '.$response->status().').');
        }

        return $this->keywordsFromText($this->htmlToText((string) $response->body()));
    }

    /**
     * @return array<int, string>
     */
    private function jobKeywords(Job $job): array
    {
        return $this->keywordsFromText($job->title.' '.$job->description.' '.implode(' ', $job->requirements ?? []));
    }

    private function htmlToText(string $html): string
    {
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text) ?? $text;
        $text = preg_replace('/<[^>]+>/', ' ', $text) ?? $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return Str::limit(trim($text), 20000);
    }

    private function recommendationFor(int $score): string
    {
        return match (true) {
            $score >= 70 => 'strong_match',
            $score >= 35 => 'possible_match',
            default => 'weak_match',
        };
    }

    private function summaryFor(int $score, array $matched, array $missing): string
    {
        $title = fn (array $skills) => implode(', ', array_map(
            fn ($skill) => Str::title($skill),
            array_slice($skills, 0, 3),
        ));

        $found = $matched !== []
            ? 'Their profile covers '.$title($matched).'.'
            : 'Their profile shows little direct overlap with this role.';

        if ($score >= 70) {
            return "Strong fit for this role. {$found}";
        }

        if ($score >= 35) {
            $gap = $missing !== []
                ? ' They may need to brush up on '.$title($missing).'.'
                : '';

            return "A possible match worth exploring. {$found}{$gap}";
        }

        return "Weak match overall. {$found}";
    }

    /**
     * @return array<int, string>
     */
    private function strings(mixed $value): array
    {
        return array_values(array_filter(
            array_map('strval', (array) $value),
            fn (string $item) => trim($item) !== '',
        ));
    }
}
