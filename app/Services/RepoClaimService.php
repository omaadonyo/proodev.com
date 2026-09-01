<?php

namespace App\Services;

use App\Models\RepoClaim;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Tracks who added a repository and whether it was already claimed, so the
 * plagiarism guard and the scout know an established owner for every repo.
 *
 * Claims are keyed by the normalized owner/name pair (handles are
 * case-insensitive on every supported host) plus the source host, so GitHub,
 * GitLab and Bitbucket claims for the same name never collide.
 */
class RepoClaimService
{
    /**
     * Record that a user added a repository URL as a claim. Idempotent — the
     * first user to claim a repo pair keeps the record; later re-claims are
     * ignored so the earliest owner is always the one on file.
     */
    public function record(User $user, string $url, string $origin = 'manual'): ?RepoClaim
    {
        $pair = app(PlagiarismGuardService::class)->repoPair($url);

        if ($pair === null) {
            return null;
        }

        [$owner, $repo] = $pair;

        return RepoClaim::firstOrCreate(
            [
                'owner' => Str::lower($owner),
                'repo' => Str::lower($repo),
                'source' => $this->sourceFrom($url),
            ],
            [
                'user_id' => $user->id,
                'url' => $url,
                'origin' => $origin,
            ],
        );
    }

    /**
     * The earliest recorded claim for an owner/name pair, or null when no one
     * has claimed the repository yet.
     */
    public function claimedBy(string $owner, string $repo): ?RepoClaim
    {
        return RepoClaim::query()
            ->where('owner', Str::lower($owner))
            ->where('repo', Str::lower($repo))
            ->orderBy('created_at')
            ->first();
    }

    private function sourceFrom(string $url): string
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            Str::contains($host, 'gitlab.') => 'gitlab',
            Str::contains($host, 'bitbucket.') => 'bitbucket',
            Str::contains($host, 'github.') => 'github',
            default => 'web',
        };
    }
}