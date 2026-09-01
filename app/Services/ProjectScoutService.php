<?php

namespace App\Services;

use App\Services\Ai\AiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProjectScoutService
{
    public function __construct(private AiService $ai) {}

    /**
     * Detect the source kind from a project URL.
     */
    public function source(string $url): string
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if (Str::contains($host, 'github.')) {
            return 'github';
        }

        return 'web';
    }

    /**
     * Extract the owner/repo pair from a GitHub URL.
     *
     * @return array{string|null, string|null}
     */
    public function githubParts(string $url): array
    {
        $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    /**
     * Fetch and analyze a project URL. GitHub repositories are read
     * through the API; everything else is fetched as a web page.
     *
     * @return array<string, mixed>
     */
    public function scout(string $url): array
    {
        $material = $this->fetch($url);
        $draft = $this->draft($this->facts($material), $material);

        return array_merge($material, $draft, [
            'score' => $this->score($material, $draft),
        ]);
    }

    /**
     * Fetch a project URL and return its raw material, without drafting.
     *
     * @return array<string, mixed>
     */
    public function fetch(string $url): array
    {
        $url = Str::startsWith($url, ['http://', 'https://']) ? $url : 'https://'.$url;

        $source = $this->source($url);

        $material = $source === 'github'
            ? $this->githubMaterial($url)
            : $this->webMaterial($url);

        return array_merge($material, [
            'source' => $source,
            'profile_url' => $url,
        ]);
    }

    /**
     * Draft a publish-ready project write-up from fetched material.
     *
     * @param  array<string, mixed>  $material
     * @return array<string, mixed>
     */
    public function draft(string $facts, array $material): array
    {
        return $this->draftFrom($facts, $material);
    }

    /**
     * Score a draft out of 100 from the material and produced fields.
     *
     * @param  array<string, mixed>  $material
     * @param  array<string, mixed>  $draft
     */
    public function score(array $material, array $draft): int
    {
        return $this->scoreDraft($material, $draft);
    }

    /**
     * @return array<string, mixed>
     */
    private function githubMaterial(string $url): array
    {
        [$owner, $repo] = $this->githubParts($url);

        if (! $owner || ! $repo) {
            throw new \InvalidArgumentException('We could not find a repository in that GitHub URL.');
        }

        $info = $this->get("https://api.github.com/repos/{$owner}/{$repo}");

        if (! isset($info['full_name'])) {
            throw new \InvalidArgumentException("We could not find a public repository for {$owner}/{$repo}.");
        }

        $readme = $this->get("https://api.github.com/repos/{$owner}/{$repo}/readme");
        $readmeText = '';

        if (isset($readme['content'])) {
            $readmeText = base64_decode($readme['content'], true) ?: '';
        }

        $topics = $info['topics'] ?? [];

        return [
            'title' => $repo,
            'tagline' => $info['description'] ?? null,
            'demo_url' => $info['homepage'] ?: null,
            'repository_url' => $info['html_url'] ?? $url,
            'tech_stack' => array_slice(array_values(array_filter([$info['language'] ?? null, ...$topics])), 0, 8),
            'content' => collect([$info['description'] ?? null, $readmeText])->filter()->implode("\n\n"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function webMaterial(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'EngineeringOS-ProjectScout',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->timeout(15)->connectTimeout(8)->retry(2, 500, throw: false)->get($url);

        $html = (string) $response->body();

        if (trim($html) === '') {
            throw new \InvalidArgumentException('We could not read any content from that URL.');
        }

        $title = $this->meta($html, ['property="og:title"', 'name="twitter:title"', 'name="title"']) ?: $this->documentTitle($html);
        $description = $this->meta($html, ['property="og:description"', 'name="twitter:description"', 'name="description"']);
        $text = $this->bodyText($html);

        return [
            'title' => Str::limit($title ?: Str::slug($url), 80),
            'tagline' => Str::limit($description, 180) ?: null,
            'demo_url' => $url,
            'repository_url' => null,
            'tech_stack' => [],
            'content' => trim(Str::limit("{$description}\n\n{$text}", 12000)),
        ];
    }

    /**
     * @param  array<string, mixed>  $material
     */
    private function facts(array $material): string
    {
        return collect([
            'Source URL: '.($material['profile_url'] ?? ''),
            'Title: '.($material['title'] ?? ''),
            'Tagline: '.($material['tagline'] ?? 'Not provided'),
            'Tech signals: '.implode(', ', $material['tech_stack'] ?? []),
            'Content: '.Str::limit((string) ($material['content'] ?? ''), 8000),
        ])->filter()->implode("\n\n");
    }

    /**
     * Draft a publish-ready project write-up from fetched material.
     *
     * @param  array<string, mixed>  $material
     * @return array<string, mixed>
     */
    private function draftFrom(string $facts, array $material): array
    {
        if ($this->ai->available()) {
            $draft = $this->ai->draftProject($facts);

            return [
                'title' => (string) ($draft['title'] ?? $material['title'] ?? 'Untitled project'),
                'tagline' => (string) ($draft['tagline'] ?? ''),
                'problem' => (string) ($draft['problem'] ?? ''),
                'solution' => (string) ($draft['solution'] ?? ''),
                'architecture' => (string) ($draft['architecture'] ?? ''),
                'tech_stack' => array_values(array_filter((array) ($draft['tech_stack'] ?? []))),
                'engineering_decisions' => array_values(array_filter((array) ($draft['engineering_decisions'] ?? []))),
                'lessons_learned' => (string) ($draft['lessons_learned'] ?? ''),
                'demo_url' => $draft['demo_url'] ?? $material['demo_url'] ?? null,
                'repository_url' => $draft['repository_url'] ?? $material['repository_url'] ?? null,
                'generated_by' => 'ai',
            ];
        }

        return $this->ruleDraft($material);
    }

    /**
     * Deterministic draft used when AI is unavailable.
     *
     * @param  array<string, mixed>  $material
     * @return array<string, mixed>
     */
    private function ruleDraft(array $material): array
    {
        $content = (string) ($material['content'] ?? '');
        $title = (string) ($material['title'] ?? 'Untitled project');
        $tagline = (string) ($material['tagline'] ?? '');

        $tech = array_values(array_filter(array_merge(
            $material['tech_stack'] ?? [],
            array_slice($this->detectTech($content), 0, 6),
        )));

        $sentences = $this->sentences($content);

        return [
            'title' => $title,
            'tagline' => $tagline ?: Str::limit($sentences[0] ?? '', 180),
            'problem' => $this->synthesize('This project set out to solve a real-world engineering challenge.', $sentences, 2),
            'solution' => $this->synthesize('The implementation delivers a working solution using a focused, maintainable stack.', $sentences, 3),
            'architecture' => $this->synthesize('The project is structured for clarity and scalability, separating concerns across its modules.', $sentences, 2),
            'tech_stack' => $tech,
            'engineering_decisions' => array_slice($this->detectDecisions($content), 0, 4),
            'lessons_learned' => $this->synthesize('Building this project reinforced the value of deliberate, incremental engineering.', $sentences, 1),
            'demo_url' => $material['demo_url'] ?? null,
            'repository_url' => $material['repository_url'] ?? null,
            'generated_by' => 'rule-based-fallback',
        ];
    }

    /**
     * Score a draft out of 100 from the material and produced fields.
     *
     * @param  array<string, mixed>  $material
     * @param  array<string, mixed>  $draft
     */
    private function scoreDraft(array $material, array $draft): int
    {
        $points = 0;

        if (! empty($draft['problem'])) {
            $points += 25;
        }

        if (! empty($draft['solution'])) {
            $points += 25;
        }

        if (! empty($draft['title']) && $draft['title'] !== 'Untitled project') {
            $points += 10;
        }

        if (! empty($draft['tagline'])) {
            $points += 10;
        }

        $tech = count($draft['tech_stack'] ?? []);
        $points += min($tech, 5) * 4;

        $decisions = count($draft['engineering_decisions'] ?? []);
        $points += min($decisions, 3) * 3;

        if (! empty($draft['architecture'])) {
            $points += 6;
        }

        if (! empty($draft['demo_url']) || ! empty($draft['repository_url'])) {
            $points += 5;
        }

        return min(100, $points);
    }

    /**
     * Split repository material into clean sentences. Raw HTML and entities
     * are stripped first so the rule-based fallback never copies markup into
     * the problem / solution / tagline fields.
     *
     * @return array<int, string>
     */
    private function sentences(string $content): array
    {
        $clean = html_entity_decode((string) preg_replace('/\s+/u', ' ', (string) strip_tags($content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return array_values(array_filter(array_map('trim', preg_split('/(?<=[.!?])\s+/u', $clean) ?: [])));
    }

    private function synthesize(string $fallback, array $sentences, int $count): string
    {
        $picked = array_slice($sentences, 0, $count);

        return $picked !== [] ? implode(' ', $picked) : $fallback;
    }

    /**
     * @return array<int, string>
     */
    private function detectTech(string $content): array
    {
        $keywords = ['laravel', 'php', 'javascript', 'typescript', 'python', 'react', 'vue', 'livewire', 'tailwind', 'docker', 'mysql', 'postgres', 'redis', 'rust', 'go', 'node'];

        return collect($keywords)
            ->filter(fn ($kw) => Str::contains(Str::lower($content), $kw))
            ->map(fn ($kw) => match ($kw) {
                'php' => 'PHP',
                'javascript' => 'JavaScript',
                'typescript' => 'TypeScript',
                'python' => 'Python',
                'react' => 'React',
                'vue' => 'Vue',
                'livewire' => 'Livewire',
                'tailwind' => 'Tailwind CSS',
                'docker' => 'Docker',
                'mysql' => 'MySQL',
                'postgres' => 'PostgreSQL',
                'redis' => 'Redis',
                'rust' => 'Rust',
                'go' => 'Go',
                'node' => 'Node.js',
                'laravel' => 'Laravel',
                default => Str::title($kw),
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function detectDecisions(string $content): array
    {
        $rules = [
            'Used queues for asynchronous work' => 'queue',
            'Cached hot queries to cut latency' => ['cache', 'redis'],
            'Wrapped external calls behind services' => 'service',
            'Enforced validation at the boundary' => 'validation',
            'Kept the schema simple and indexed' => 'index',
            'Followed a test-driven workflow' => 'test',
        ];

        $decisions = [];

        foreach ($rules as $label => $needles) {
            foreach ((array) $needles as $needle) {
                if (Str::contains(Str::lower($content), $needle)) {
                    $decisions[] = $label;
                    break;
                }
            }
        }

        return $decisions;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $url): array
    {
        $response = Http::withHeaders(['User-Agent' => 'EngineeringOS-ProjectScout'])
            ->timeout(20)
            ->connectTimeout(8)
            ->retry(2, 500, throw: false)
            ->get($url);

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?: [];
    }

    private function documentTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        return '';
    }

    private function meta(string $html, array $selectors): string
    {
        foreach ($selectors as $selector) {
            if (preg_match('/<meta[^>]*'.$selector.'[^>]*content=["\'](.*?)["\'][^>]*>/is', $html, $matches)) {
                return trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return '';
    }

    private function bodyText(string $html): string
    {
        $html = preg_replace('/<(script|style|noscript)[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<[^>]+>/', ' ', $html) ?? $html;
        $html = preg_replace('/\s+/u', ' ', $html) ?? $html;

        return trim(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
