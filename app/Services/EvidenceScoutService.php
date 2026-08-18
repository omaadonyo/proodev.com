<?php

namespace App\Services;

use App\Enums\EvidenceType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EvidenceScoutService
{
    /**
     * Classify a URL into an evidence type + extraction source.
     *
     * @return array{type: EvidenceType, source: string, handle: string|null, repo: string|null}
     */
    public function classify(string $url): array
    {
        $url = $this->normalize($url);
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $parts = array_values(array_filter(explode('/', $path)));

        if (Str::contains($host, 'github.')) {
            if (count($parts) >= 2) {
                return ['type' => EvidenceType::GithubRepository, 'source' => 'github', 'handle' => $parts[0], 'repo' => $parts[1]];
            }

            return ['type' => EvidenceType::GithubRepository, 'source' => 'github', 'handle' => $parts[0] ?? null, 'repo' => null];
        }

        if (Str::contains($host, 'gitlab.')) {
            return ['type' => EvidenceType::GitlabRepository, 'source' => 'gitlab', 'handle' => $parts[0] ?? null, 'repo' => $parts[1] ?? null];
        }

        if (Str::contains($host, 'bitbucket.')) {
            return ['type' => EvidenceType::BitbucketRepository, 'source' => 'bitbucket', 'handle' => $parts[0] ?? null, 'repo' => $parts[1] ?? null];
        }

        if (Str::contains($host, ['packagist', 'npmjs', 'pub.dev', 'pypi.org'])) {
            return ['type' => EvidenceType::Package, 'source' => 'web', 'handle' => null, 'repo' => null];
        }

        if (Str::contains($host, ['youtube.', 'youtu.be'])) {
            return ['type' => EvidenceType::Video, 'source' => 'web', 'handle' => null, 'repo' => null];
        }

        if (Str::contains($host, ['medium.', 'dev.to', 'hashnode.', 'blog.'])) {
            return ['type' => EvidenceType::TechnicalArticle, 'source' => 'web', 'handle' => null, 'repo' => null];
        }

        return ['type' => EvidenceType::PersonalWebsite, 'source' => 'web', 'handle' => null, 'repo' => null];
    }

    /**
     * Fetch an evidence source and return raw extracted material.
     *
     * @return array<string, mixed>
     */
    public function fetch(string $url): array
    {
        $url = $this->normalize($url);
        $classified = $this->classify($url);

        $material = $classified['source'] === 'github'
            ? $this->githubMaterial($url, $classified)
            : $this->webMaterial($url);

        return array_merge($material, [
            'source' => $classified['source'],
            'type' => $classified['type']->value,
            'profile_url' => $url,
        ]);
    }

    /**
     * @param  array{type: EvidenceType, source: string, handle: string|null, repo: string|null}  $classified
     * @return array<string, mixed>
     */
    private function githubMaterial(string $url, array $classified): array
    {
        $handle = $classified['handle'];
        $repo = $classified['repo'];

        if (! $handle) {
            throw new \InvalidArgumentException('We could not find a GitHub username in that URL.');
        }

        if (! $repo) {
            throw new \InvalidArgumentException('We could not find a repository in that GitHub URL.');
        }

        $info = $this->get("https://api.github.com/repos/{$handle}/{$repo}");

        if (! isset($info['full_name'])) {
            throw new \InvalidArgumentException("We could not find a public repository for {$handle}/{$repo}.");
        }

        $readme = $this->get("https://api.github.com/repos/{$handle}/{$repo}/readme");
        $readmeText = '';

        if (isset($readme['content'])) {
            $readmeText = base64_decode($readme['content'], true) ?: '';
        }

        $topics = $info['topics'] ?? [];

        return [
            'title' => $repo,
            'description' => $info['description'] ?? null,
            'demo_url' => $info['homepage'] ?: null,
            'repository_url' => $info['html_url'] ?? $url,
            'tech_stack' => array_slice(array_values(array_filter([$info['language'] ?? null, ...$topics])), 0, 8),
            'stars' => (int) ($info['stargazers_count'] ?? 0),
            'forks' => (int) ($info['forks_count'] ?? 0),
            'content' => collect([$info['description'] ?? null, $readmeText])->filter()->implode("\n\n"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function webMaterial(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'ProoDev-EvidenceScout',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->timeout(15)->get($url);

        $html = (string) $response->body();

        if (trim($html) === '') {
            throw new \InvalidArgumentException('We could not read any content from that URL.');
        }

        $title = $this->meta($html, ['property="og:title"', 'name="twitter:title"', 'name="title"']) ?: $this->documentTitle($html);
        $description = $this->meta($html, ['property="og:description"', 'name="twitter:description"', 'name="description"']);
        $text = $this->bodyText($html);

        return [
            'title' => Str::limit($title ?: Str::slug($url), 120),
            'description' => Str::limit($description, 220) ?: null,
            'demo_url' => $url,
            'repository_url' => null,
            'tech_stack' => [],
            'stars' => 0,
            'forks' => 0,
            'content' => trim(Str::limit("{$description}\n\n{$text}", 12000)),
        ];
    }

    /**
     * Convert fetched material into the normalized repo shape the import
     * pipeline consumes, preserving the source type so evidence is typed
     * correctly (repo, package, article, video, site…).
     *
     * @param  array<string, mixed>  $material
     * @return array<string, mixed>
     */
    public function toRepo(array $material, string $fallbackUrl): array
    {
        $url = $material['repository_url'] ?? $material['profile_url'] ?? $fallbackUrl;
        $tech = $material['tech_stack'] ?? [];

        return [
            'name' => Str::limit((string) ($material['title'] ?? 'project'), 60),
            'full_name' => $this->fullNameFrom($url),
            'description' => $material['description'] ?? null,
            'language' => $tech[0] ?? null,
            'stars' => (int) ($material['stars'] ?? 0),
            'forks' => (int) ($material['forks'] ?? 0),
            'topics' => array_values(array_slice($tech, 1)),
            'homepage' => $material['demo_url'] ?? null,
            'html_url' => $url,
            'size' => 0,
            'fork' => false,
            'archived' => false,
            'default_branch' => 'main',
            'content' => $material['content'] ?? null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'pushed_at' => now()->toIso8601String(),
            'evidence_type' => $material['type'] ?? null,
            'source' => $material['source'] ?? 'web',
        ];
    }

    private function fullNameFrom(string $url): string
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if (Str::contains($host, 'github.')) {
            $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

            if (count($parts) >= 2) {
                return $parts[0].'/'.$parts[1];
            }
        }

        return Str::slug($host).'/'.Str::slug(basename((string) parse_url($url, PHP_URL_PATH)) ?: 'project');
    }

    /**
     * Build a compact facts payload for AI analysis.
     *
     * @param  array<string, mixed>  $material
     */
    public function facts(array $material): string
    {
        return collect([
            'Source URL: '.($material['profile_url'] ?? ''),
            'Title: '.($material['title'] ?? ''),
            'Description: '.($material['description'] ?? 'Not provided'),
            'Tech signals: '.implode(', ', $material['tech_stack'] ?? []),
            'Stars: '.($material['stars'] ?? 0).' · Forks: '.($material['forks'] ?? 0),
            'Content: '.Str::limit((string) ($material['content'] ?? ''), 8000),
        ])->filter()->implode("\n\n");
    }

    private function normalize(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : 'https://'.$url;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $url): array
    {
        $response = Http::withHeaders(['User-Agent' => 'ProoDev-EvidenceScout'])
            ->timeout(15)
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
