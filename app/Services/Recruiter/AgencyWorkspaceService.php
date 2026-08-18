<?php

namespace App\Services\Recruiter;

use App\Models\RecruiterNote;
use App\Models\TalentPool;
use App\Models\TalentPoolMember;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

/**
 * The agency workspace backend: talent pools, candidate statuses, notes,
 * interviews, and placements for a recruiting professional.
 *
 * Data is scoped to the recruiter's active workspace when one is set.
 */
class AgencyWorkspaceService
{
    public function __construct(private WorkspaceService $workspaces) {}

    private function scope(User $recruiter, string $relation)
    {
        $workspace = $this->workspaces->current($recruiter);

        if (! $workspace) {
            return $recruiter->{$relation}();
        }

        $model = $recruiter->{$relation}()->getRelated();

        return $model::query()->where('workspace_id', $workspace->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(User $recruiter): array
    {
        $workspace = $this->workspaces->current($recruiter);

        $pools = $workspace
            ? TalentPool::where('workspace_id', $workspace->id)->withCount('members')->orderBy('name')->get()
            : $recruiter->talentPools()->withCount('members')->orderBy('name')->get();

        $members = TalentPoolMember::query()
            ->whereIn('talent_pool_id', $pools->pluck('id'))
            ->with(['candidate']);

        $statusCounts = (clone $members)->get()
            ->countBy(fn ($m) => $m->status)
            ->all();

        return [
            'pools' => $pools,
            'total_candidates' => (clone $members)->distinct('candidate_id')->count(),
            'status_counts' => $statusCounts,
            'active_interviews' => $this->interviews($recruiter)
                ->where('status', 'scheduled')
                ->with(['candidate', 'job'])
                ->latest('scheduled_at')
                ->take(10)
                ->get(),
            'recent_notes' => $this->notes($recruiter)
                ->with(['candidate'])
                ->latest()
                ->take(10)
                ->get(),
            'recent_placements' => $this->placements($recruiter)
                ->with(['candidate', 'company'])
                ->latest()
                ->take(5)
                ->get(),
            'active_alerts' => $this->alerts($recruiter)->where('is_active', true)->count(),
        ];
    }

    public function defaultPool(User $recruiter): TalentPool
    {
        $workspace = $this->workspaces->current($recruiter);

        if ($workspace) {
            return TalentPool::firstOrCreate(
                ['workspace_id' => $workspace->id, 'kind' => 'collection', 'name' => 'Saved Candidates'],
                [
                    'recruiter_id' => $recruiter->id,
                    'slug' => 'saved-candidates-'.Str::lower(Str::random(4)),
                    'is_shared' => true,
                ],
            );
        }

        return TalentPool::firstOrCreate(
            ['recruiter_id' => $recruiter->id, 'kind' => 'collection', 'slug' => 'saved-candidates'],
            ['name' => 'Saved Candidates', 'is_shared' => false],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function addCandidate(User $recruiter, User $candidate, ?string $poolSlug = null, string $status = 'saved'): array
    {
        $workspace = $this->workspaces->current($recruiter);

        $pool = $poolSlug
            ? TalentPool::where('recruiter_id', $recruiter->id)->where('slug', $poolSlug)->first()
            : null;

        $pool ??= $this->defaultPool($recruiter);

        $member = TalentPoolMember::firstOrCreate(
            ['talent_pool_id' => $pool->id, 'candidate_id' => $candidate->id],
            ['status' => $status],
        );

        return ['pool' => $pool, 'member' => $member];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function setStatus(TalentPoolMember $member, array $attributes): void
    {
        $member->update($attributes);
    }

    public function addNote(User $recruiter, User $candidate, string $body, ?int $poolId = null, bool $shared = true): RecruiterNote
    {
        return RecruiterNote::create([
            'workspace_id' => $this->workspaces->currentId($recruiter),
            'recruiter_id' => $recruiter->id,
            'candidate_id' => $candidate->id,
            'talent_pool_id' => $poolId,
            'body' => $body,
            'is_shared' => $shared,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPool(User $recruiter, array $data): TalentPool
    {
        return TalentPool::create(array_merge($data, [
            'workspace_id' => $this->workspaces->currentId($recruiter),
            'recruiter_id' => $recruiter->id,
            'slug' => Str::slug($data['name'] ?? 'pool').'-'.Str::lower(Str::random(4)),
        ]));
    }

    public function interviews(User $recruiter)
    {
        return $this->scope($recruiter, 'recruiterInterviews');
    }

    public function notes(User $recruiter)
    {
        return $this->scope($recruiter, 'recruiterNotes');
    }

    public function placements(User $recruiter)
    {
        return $this->scope($recruiter, 'recruiterPlacements');
    }

    public function alerts(User $recruiter)
    {
        return $this->scope($recruiter, 'talentAlerts');
    }
}
