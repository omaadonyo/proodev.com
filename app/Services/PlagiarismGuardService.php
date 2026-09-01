<?php

namespace App\Services;

use App\Enums\EvidenceStatus;
use App\Mail\PlagiarismAlertMail;
use App\Mail\PlagiarismBanMail;
use App\Mail\PlagiarismWarningMail;
use App\Models\Evidence;
use App\Models\PlagiarismStrike;
use App\Models\User;
use App\Notifications\PlagiarismAlertNotification;
use App\Notifications\PlagiarismBanNotification;
use App\Notifications\PlagiarismWarningNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Protects engineers from having their repositories copied and re-claimed as
 * someone else's proof.
 *
 * The guard runs whenever a developer pastes a repository URL as evidence:
 *
 *  1. Platform claim — if another user already claimed this exact repo on the
 *     platform, it is a confirmed copy: the offender gets a warning and the
 *     original owner is alerted.
 *  2. Association — when we can verify the user's GitHub identity (their
 *     linked GitHub URL or the accounts behind their existing repo evidence)
 *     and the repo belongs to a different handle, we confirm with the GitHub
 *     API that the user is not a contributor before flagging it.
 *
 * First offense → warning. Second offense → the account is banned and the ban
 * is shown as a public notice on the DevID. API checks fail open so a
 * rate-limited or offline GitHub never causes a false-positive ban.
 */
class PlagiarismGuardService
{
    /**
     * Run every plagiarism check for a repository claim.
     *
     * @return PlagiarismStrike|null the strike when the claim is rejected, or null when it is allowed
     */
    public function check(User $user, string $url, ?Evidence $evidence = null): ?PlagiarismStrike
    {
        $pair = $this->repoPair($url);

        if ($pair === null) {
            return null;
        }

        [$owner, $name] = $pair;

        $ownerUser = $this->platformOwner($owner, $name, $user);

        // Confirmed copy: another user on the platform already claims this repo.
        if ($ownerUser && $ownerUser->id !== $user->id) {
            return $this->strike(
                $user,
                $ownerUser,
                $owner,
                $name,
                $url,
                $evidence,
                "You claimed {$owner}/{$name}, a repository already claimed on ProoDev by {$ownerUser->name}."
            );
        }

        // Association: the repo belongs to a GitHub account we cannot connect
        // to this user. Only enforced when we know the user's GitHub identity.
        $handles = $this->knownHandles($user);

        if ($handles !== [] && ! in_array(Str::lower($owner), $handles, true)) {
            if ($this->githubConfirmsAssociation($handles, $owner, $name)) {
                return null;
            }

            return $this->strike(
                $user,
                $ownerUser,
                $owner,
                $name,
                $url,
                $evidence,
                "You claimed {$owner}/{$name}, a repository owned by the GitHub account “{$owner}”, which is not linked to your profile."
            );
        }

        return null;
    }

    /**
     * The earliest user on the platform who claimed this repo as evidence,
     * ignoring removed (failed) claims and the claimer themselves.
     */
    private function platformOwner(string $owner, string $name, User $claimant): ?User
    {
        $claim = app(RepoClaimService::class)->claimedBy($owner, $name);

        if ($claim) {
            return $claim->user_id !== $claimant->id ? $claim->user : null;
        }

        // Fallback for repositories claimed before repo claims tracking
        // existed — scan the evidence library for the earliest owner.
        return $this->repoEvidence()
            ->where('user_id', '!=', $claimant->id)
            ->where('status', '!=', EvidenceStatus::Failed->value)
            ->filter(fn (Evidence $evidence) => $this->sameRepo($evidence->url, $owner, $name))
            ->sortBy('created_at')
            ->first()
            ?->user;
    }

    /**
     * @return Collection<int, Evidence>
     */
    private function repoEvidence(): Collection
    {
        return Evidence::query()
            ->whereIn('source', ['github', 'gitlab', 'bitbucket'])
            ->get();
    }

    /**
     * Whether a stored evidence URL points at the same owner/name pair.
     */
    private function sameRepo(string $url, string $owner, string $name): bool
    {
        $pair = $this->repoPair($url);

        return $pair !== null
            && Str::lower($pair[0]) === Str::lower($owner)
            && Str::lower($pair[1]) === Str::lower($name);
    }

    /**
     * GitHub handles we can prove belong to this user: the one linked on
     * their profile plus every owner handle behind their own repo evidence.
     *
     * @return array<int, string>
     */
    private function knownHandles(User $user): array
    {
        $handles = [];

        if ($user->github_url) {
            $handle = $this->handleFromUrl($user->github_url);

            if ($handle) {
                $handles[] = Str::lower($handle);
            }
        }

        foreach ($user->evidence as $evidence) {
            $pair = $this->repoPair($evidence->url);

            if ($pair) {
                $handles[] = Str::lower($pair[0]);
            }
        }

        return array_values(array_unique(array_filter($handles)));
    }

    private function handleFromUrl(string $url): ?string
    {
        if (! str_contains($url, 'github.')) {
            return null;
        }

        $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

        return $parts[0] ?? null;
    }

    /**
     * Confirm with the GitHub API that one of the user's handles is genuinely
     * associated with the repo (owner account or a contributor). Fails open:
     * any API error means "cannot disprove the claim" and the claim is allowed.
     *
     * @param  array<int, string>  $handles
     */
    private function githubConfirmsAssociation(array $handles, string $owner, string $name): bool
    {
        try {
            $repo = Http::withHeaders(['User-Agent' => 'ProoDev-PlagiarismGuard'])
                ->timeout(8)
                ->get("https://api.github.com/repos/{$owner}/{$name}");

            if ($repo->failed()) {
                return true; // fail open
            }

            $repoOwner = Str::lower((string) data_get($repo->json(), 'owner.login', ''));

            if ($repoOwner !== '' && in_array($repoOwner, $handles, true)) {
                return true;
            }

            $contributors = Http::withHeaders(['User-Agent' => 'ProoDev-PlagiarismGuard'])
                ->timeout(8)
                ->get("https://api.github.com/repos/{$owner}/{$name}/contributors?per_page=100");

            if ($contributors->failed()) {
                return true; // fail open
            }

            $logins = collect($contributors->json())
                ->pluck('login')
                ->map(fn ($login) => Str::lower((string) $login))
                ->all();

            return array_intersect($logins, $handles) !== [];
        } catch (\Throwable) {
            return true; // fail open
        }
    }

    /**
     * Record the strike, escalate to a ban on the second offense, notify both
     * parties, and reject the copied evidence.
     *
     * @param  array{0: string, 1: string}  $pair
     */
    private function strike(
        User $offender,
        ?User $owner,
        string $repoOwner,
        string $repoName,
        string $repoUrl,
        ?Evidence $evidence,
        string $reason
    ): PlagiarismStrike {
        $strikeNumber = (int) PlagiarismStrike::where('offender_id', $offender->id)->count() + 1;
        $action = $strikeNumber >= 2 ? PlagiarismStrike::ACTION_BANNED : PlagiarismStrike::ACTION_WARNING;

        $strike = PlagiarismStrike::create([
            'offender_id' => $offender->id,
            'owner_id' => $owner?->id,
            'evidence_id' => $evidence?->id,
            'repo_owner' => $repoOwner,
            'repo_name' => $repoName,
            'repo_url' => $repoUrl,
            'strike_number' => $strikeNumber,
            'action' => $action,
            'reason' => $reason,
            'notified_at' => now(),
        ]);

        // The copied evidence must never become proof on a DevID.
        if ($evidence) {
            $evidence->update([
                'status' => EvidenceStatus::Failed,
                'error' => 'Removed: '.$reason,
            ]);
        }

        if ($action === PlagiarismStrike::ACTION_BANNED) {
            $offender->suspend();
            $offender->notify(new PlagiarismBanNotification($strike));
            Mail::to($offender)->send(new PlagiarismBanMail($strike));
        } else {
            $offender->notify(new PlagiarismWarningNotification($strike));
            Mail::to($offender)->send(new PlagiarismWarningMail($strike));
        }

        if ($owner && $owner->id !== $offender->id && $owner->email) {
            $owner->notify(new PlagiarismAlertNotification($strike));
            Mail::to($owner)->send(new PlagiarismAlertMail($strike));
        }

        return $strike;
    }

    /**
     * Extract the owner/name pair from a GitHub, GitLab or Bitbucket URL.
     *
     * @return array{0: string, 1: string}|null
     */
    public function repoPair(string $url): ?array
    {
        $url = trim($url);

        if ($url === '' || ! str_contains($url, '://')) {
            return null;
        }

        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $host = Str::startsWith($host, 'www.') ? Str::after($host, 'www.') : $host;

        if (! Str::contains($host, ['github.', 'gitlab.', 'bitbucket.'])) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $parts = array_values(array_filter(explode('/', $path)));

        if (count($parts) < 2) {
            return null;
        }

        $name = (string) end($parts);

        if (Str::endsWith($name, '.git')) {
            $name = Str::beforeLast($name, '.git');
        }

        $owner = (string) $parts[count($parts) - 2];

        if ($owner === '' || $name === '') {
            return null;
        }

        return [$owner, $name];
    }
}
