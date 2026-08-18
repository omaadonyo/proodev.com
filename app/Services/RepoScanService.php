<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class RepoScanService
{
    public const PER_PAGE = 100;

    /**
     * Cap how many pages we walk so very large accounts stay within a single
     * GitHub API rate-limit window (unauthenticated requests are limited).
     */
    public const MAX_PAGES = 3;

    /**
     * Scan every public repository for a GitHub handle. Repositories are
     * returned newest-first (as GitHub orders by last update) and normalized
     * to a compact shape that the rest of the import pipeline consumes.
     *
     * @return array{
     *     repos: array<int, array<string, mixed>>,
     *     total: int,
     *     pages: int,
     *     failed: bool
     * }
     */
    public function scan(string $handle): array
    {
        $repos = [];
        $page = 1;
        $pages = 0;
        $failed = false;

        do {
            $url = "https://api.github.com/users/{$handle}/repos?per_page=".self::PER_PAGE."&page={$page}&sort=updated";

            $response = Http::withHeaders([
                'User-Agent' => 'ProoDev-RepoScan',
                'Accept' => 'application/vnd.github.mercy-preview+json',
            ])->timeout(15)->get($url);

            if ($response->failed()) {
                if ($page === 1) {
                    $failed = true;
                }

                break;
            }

            $data = $response->json();

            if (! is_array($data) || ! array_is_list($data) || $data === []) {
                break;
            }

            $repos = array_merge($repos, $data);

            $page++;
            $pages++;
        } while (count($data) === self::PER_PAGE && $pages < self::MAX_PAGES);

        return [
            'repos' => collect($repos)->map(fn (array $repo) => $this->normalize($repo))->values()->all(),
            'total' => count($repos),
            'pages' => $pages,
            'failed' => $failed,
        ];
    }

    /**
     * Fetch a single repository by its GitHub URL (or owner/name pair) and
     * normalize it to the same shape a profile scan produces. Returns null
     * when the URL cannot be resolved or the repo is not publicly readable.
     *
     * @return array<string, mixed>|null
     */
    public function repo(string $url): ?array
    {
        $pair = $this->repoPair($url);

        if ($pair === null) {
            return null;
        }

        [$owner, $name] = $pair;

        $response = Http::withHeaders([
            'User-Agent' => 'ProoDev-RepoScan',
            'Accept' => 'application/vnd.github.mercy-preview+json',
        ])->timeout(15)->get("https://api.github.com/repos/{$owner}/{$name}");

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data) || empty($data['full_name'])) {
            return null;
        }

        return $this->normalize($data);
    }

    /**
     * Extract the owner/name pair from a GitHub URL or shorthand pair.
     *
     * @return array{0: string, 1: string}|null
     */
    private function repoPair(string $url): ?array
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (str_contains($url, 'github.com')) {
            $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
            $parts = array_values(array_filter(explode('/', $path)));
        } else {
            $parts = array_values(array_filter(explode('/', $url)));
        }

        if (count($parts) < 2) {
            return null;
        }

        $name = rtrim((string) end($parts), '.git');
        $owner = (string) $parts[count($parts) - 2];

        return [$owner, $name];
    }

    /**
     * @param  array<string, mixed>  $repo
     * @return array<string, mixed>
     */
    private function normalize(array $repo): array
    {
        return [
            'name' => (string) ($repo['name'] ?? ''),
            'full_name' => (string) ($repo['full_name'] ?? ''),
            'description' => ($repo['description'] ?? null) ? (string) $repo['description'] : null,
            'language' => ($repo['language'] ?? null) ? (string) $repo['language'] : null,
            'stars' => (int) ($repo['stargazers_count'] ?? 0),
            'forks' => (int) ($repo['forks_count'] ?? 0),
            'topics' => array_values(array_filter((array) ($repo['topics'] ?? []))),
            'homepage' => ($repo['homepage'] ?? null) ? (string) $repo['homepage'] : null,
            'html_url' => (string) ($repo['html_url'] ?? ''),
            'size' => (int) ($repo['size'] ?? 0),
            'fork' => (bool) ($repo['fork'] ?? false),
            'archived' => (bool) ($repo['archived'] ?? false),
            'default_branch' => (string) ($repo['default_branch'] ?? 'main'),
            'created_at' => $this->date($repo['created_at'] ?? null),
            'updated_at' => $this->date($repo['updated_at'] ?? null),
            'pushed_at' => $this->date($repo['pushed_at'] ?? null),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
