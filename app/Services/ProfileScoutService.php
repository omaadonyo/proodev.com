<?php

namespace App\Services;

use App\Support\GitHubApi;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProfileScoutService
{
    /**
     * Detect the supported profile source from a URL.
     */
    public function source(string $url): ?string
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            Str::contains($host, 'github.') => 'github',
            Str::contains($host, 'gitlab.') => 'gitlab',
            Str::contains($host, 'bitbucket.') => 'bitbucket',
            Str::contains($host, 'linkedin.') => 'linkedin',
            default => null,
        };
    }

    /**
     * Extract the profile handle from a URL for a given source.
     */
    public function handle(string $url, string $source): ?string
    {
        if ($source === 'linkedin') {
            return null;
        }

        $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

        return $parts[0] ?? null;
    }

    /**
     * Scout a profile URL. GitHub profiles are fully extracted; other
     * sources are validated and linked for the user to complete later.
     *
     * @return array<string, mixed>
     */
    public function scout(string $url): array
    {
        $source = $this->source($url);

        if (! $source) {
            throw new InvalidArgumentException('Please enter a supported profile URL (GitHub, GitLab, Bitbucket or LinkedIn).');
        }

        if ($source !== 'github') {
            return [
                'source' => $source,
                'handle' => $this->handle($url, $source),
                'profile_url' => $url,
                'extractable' => false,
            ];
        }

        $handle = $this->handle($url, $source);

        if (! $handle) {
            throw new InvalidArgumentException('We could not find a username in that GitHub URL.');
        }

        return array_merge($this->githubProfile($handle), [
            'source' => 'github',
            'handle' => $handle,
            'profile_url' => "https://github.com/{$handle}",
            'extractable' => true,
        ]);
    }

    /**
     * Fetch and extract a public GitHub profile plus its top languages
     * and derived achievements.
     *
     * @return array<string, mixed>
     */
    public function githubProfile(string $handle): array
    {
        $profile = GitHubApi::get("https://api.github.com/users/{$handle}");

        if (! isset($profile['login'])) {
            throw new InvalidArgumentException("We could not find a GitHub profile for @{$handle}. Double-check the username and try again.");
        }

        $repos = GitHubApi::get("https://api.github.com/users/{$handle}/repos?per_page=100&sort=updated");

        $repos = is_array($repos) ? $repos : [];

        $languages = $this->languages($repos);
        $totalStars = collect($repos)->sum('stargazers_count');
        $followers = (int) ($profile['followers'] ?? 0);
        $reposCount = (int) ($profile['public_repos'] ?? count($repos));
        $accountYears = isset($profile['created_at'])
            ? max(0, now()->diffInYears($profile['created_at']))
            : 0;

        return [
            'name' => $profile['name'] ?? $profile['login'] ?? $handle,
            'login' => $profile['login'] ?? $handle,
            'headline' => $profile['company'] ?? null,
            'bio' => $profile['bio'] ?? null,
            'location' => $profile['location'] ?? null,
            'blog' => ($profile['blog'] ?? null) ? (string) $profile['blog'] : null,
            'avatar_url' => $profile['avatar_url'] ?? null,
            'followers' => $followers,
            'public_repos' => $reposCount,
            'total_stars' => $totalStars,
            'account_years' => $accountYears,
            'languages' => $languages,
            'achievements' => $this->achievements($languages, $totalStars, $followers, $accountYears, $reposCount),
        ];
    }

    /**
     * Top languages across a user's public repositories.
     *
     * @param  array<int, array<string, mixed>>  $repos
     * @return array<int, string>
     */
    private function languages(array $repos): array
    {
        return collect($repos)
            ->pluck('language')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->keys()
            ->all();
    }

    /**
     * Derive achievements from the extracted GitHub signals.
     *
     * @param  array<int, string>  $languages
     * @return array<int, string>
     */
    private function achievements(array $languages, int $stars, int $followers, int $years, int $repos): array
    {
        $achievements = [];

        if ($repos > 0) {
            $achievements[] = 'Open Source';
        }

        if ($repos >= 5) {
            $achievements[] = 'Repository Builder';
        }

        if ($stars > 0) {
            $achievements[] = $stars >= 100 ? 'Star Collector' : 'Early Star';
        }

        if ($followers > 0) {
            $achievements[] = $followers >= 50 ? 'Community Builder' : 'Rising Contributor';
        }

        if ($years >= 3) {
            $achievements[] = 'Seasoned Engineer';
        }

        if (count($languages) >= 3) {
            $achievements[] = 'Polyglot';
        }

        return $achievements;
    }
}
