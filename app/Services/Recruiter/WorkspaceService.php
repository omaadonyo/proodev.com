<?php

namespace App\Services\Recruiter;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Collection;

/**
 * Multi-tenancy for the Recruiter Intelligence Suite.
 *
 * A workspace is a container shared by one or more recruiters (seats).
 * Each recruiter can belong to several workspaces and switch the active
 * workspace. All recruiter suite data (pools, notes, interviews,
 * placements, alerts, validations, reports) is scoped to a workspace.
 */
class WorkspaceService
{
    public const SESSION_KEY = 'active_workspace_id';

    /**
     * The workspace the user is currently operating in, or null when they
     * have none (legacy personal mode).
     */
    public function current(?User $user): ?Workspace
    {
        if (! $user) {
            return null;
        }

        $activeId = (int) session()->get(self::SESSION_KEY, 0);

        if ($activeId > 0) {
            $workspace = Workspace::find($activeId);

            if ($workspace && ($workspace->isMember($user) || $workspace->isOwner($user))) {
                return $workspace;
            }
        }

        return $this->available($user)->first();
    }

    public function currentId(?User $user): ?int
    {
        return $this->current($user)?->id;
    }

    /**
     * Workspaces the user belongs to (owned + member).
     *
     * @return Collection<int, Workspace>
     */
    public function available(User $user): Collection
    {
        return Workspace::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
            })
            ->orderBy('name')
            ->get();
    }

    public function switch(User $user, Workspace $workspace): void
    {
        abort_unless($workspace->isOwner($user) || $workspace->isMember($user), 403);

        session()->put(self::SESSION_KEY, $workspace->id);
    }

    public function switchToId(User $user, int $workspaceId): void
    {
        $workspace = Workspace::findOrFail($workspaceId);

        $this->switch($user, $workspace);
    }

    public function create(User $user, string $name): Workspace
    {
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => $name,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        session()->put(self::SESSION_KEY, $workspace->id);

        return $workspace;
    }

    /**
     * Add a recruiter as a seat on the workspace.
     */
    public function addMember(Workspace $workspace, User $member, string $role = 'member', ?User $actor = null): WorkspaceMember
    {
        $actor ??= auth()->user();

        abort_unless($workspace->isOwner($actor) || ($actor?->isAdmin() ?? false), 403);

        return WorkspaceMember::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $member->id],
            ['role' => $role],
        );
    }

    public function removeMember(Workspace $workspace, User $member, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        abort_unless($workspace->isOwner($actor) || ($actor?->isAdmin() ?? false), 403);
        abort_if($workspace->isOwner($member), 403, 'The workspace owner cannot be removed.');

        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $member->id)
            ->delete();

        if (session()->get(self::SESSION_KEY) === $workspace->id) {
            session()->forget(self::SESSION_KEY);
        }
    }
}
