<?php

namespace App\Livewire\Concerns;

use App\Models\TalentPool;
use App\Models\TalentPoolMember;
use App\Models\User;
use App\Services\Recruiter\WorkspaceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Shared talent-pool interactions for recruiter surfaces (evidence search,
 * rankings, reports...). Components using this trait must render inside the
 * recruiter.access middleware.
 */
trait InteractsWithTalentPools
{
    public string $newPoolName = '';

    /**
     * Pools visible to the current recruiter (workspace-scoped, then personal).
     *
     * @return Collection<int, TalentPool>
     */
    #[Computed]
    public function pools()
    {
        $workspace = app(WorkspaceService::class)->current(auth()->user());

        if ($workspace) {
            return TalentPool::where('workspace_id', $workspace->id)
                ->withCount('members')
                ->orderBy('name')
                ->get();
        }

        return auth()->user()->talentPools()->withCount('members')->orderBy('name')->get();
    }

    public function saveToPool(int $userId, int $poolId): void
    {
        $candidate = User::find($userId);

        if (! $candidate) {
            return;
        }

        $workspace = app(WorkspaceService::class)->current(auth()->user());

        $pool = TalentPool::where('id', $poolId)
            ->where(function ($q) use ($workspace) {
                $q->where('recruiter_id', auth()->id());

                if ($workspace) {
                    $q->orWhere('workspace_id', $workspace->id);
                }
            })
            ->first();

        if (! $pool) {
            return;
        }

        TalentPoolMember::firstOrCreate(
            ['talent_pool_id' => $pool->id, 'candidate_id' => $candidate->id],
            ['status' => 'saved'],
        );

        $this->dispatch('toast', message: $candidate->name.' saved to '.$pool->name.'.', variant: 'success');

        $this->afterSavedToPool($candidate, $pool);
    }

    public function setCandidateStatus(int $userId, int $poolId, string $status): void
    {
        if (! in_array($status, TalentPoolMember::STATUSES, true)) {
            return;
        }

        $candidate = User::find($userId);

        if (! $candidate) {
            return;
        }

        $workspace = app(WorkspaceService::class)->current(auth()->user());

        $pool = TalentPool::where('id', $poolId)
            ->where(function ($q) use ($workspace) {
                $q->where('recruiter_id', auth()->id());

                if ($workspace) {
                    $q->orWhere('workspace_id', $workspace->id);
                }
            })
            ->first();

        if (! $pool) {
            return;
        }

        $member = TalentPoolMember::where('talent_pool_id', $pool->id)
            ->where('candidate_id', $candidate->id)
            ->first();

        if ($member) {
            $member->update(['status' => $status]);

            $this->dispatch('toast', message: $candidate->name.' marked as '.Str::lower($status).' in '.$pool->name.'.', variant: 'success');
        } else {
            TalentPoolMember::create([
                'talent_pool_id' => $pool->id,
                'candidate_id' => $candidate->id,
                'status' => $status,
            ]);

            $this->dispatch('toast', message: $candidate->name.' saved to '.$pool->name.' as '.Str::lower($status).'.', variant: 'success');
            $this->afterSavedToPool($candidate, $pool);
        }
    }

    public function removeFromPool(int $userId, int $poolId): void
    {
        $candidate = User::find($userId);

        if (! $candidate) {
            return;
        }

        $workspace = app(WorkspaceService::class)->current(auth()->user());

        $pool = TalentPool::where('id', $poolId)
            ->where(function ($q) use ($workspace) {
                $q->where('recruiter_id', auth()->id());

                if ($workspace) {
                    $q->orWhere('workspace_id', $workspace->id);
                }
            })
            ->first();

        if (! $pool) {
            return;
        }

        TalentPoolMember::where('talent_pool_id', $pool->id)
            ->where('candidate_id', $candidate->id)
            ->delete();

        $this->dispatch('toast', message: $candidate->name.' removed from '.$pool->name.'.', variant: 'success');
        $this->afterRemovedFromPool($candidate, $pool);
    }

    /**
     * Hook for components that want to react after a candidate is removed from a pool.
     */
    protected function afterRemovedFromPool(User $candidate, TalentPool $pool): void {}

    public function createPool(): void
    {
        $this->validate(['newPoolName' => ['required', 'string', 'max:100']]);

        $workspace = app(WorkspaceService::class)->current(auth()->user());

        $pool = TalentPool::create([
            'workspace_id' => $workspace?->id,
            'recruiter_id' => auth()->id(),
            'name' => trim($this->newPoolName),
            'slug' => Str::slug($this->newPoolName).'-'.Str::lower(Str::random(4)),
            'kind' => 'collection',
            'is_shared' => true,
        ]);

        $this->newPoolName = '';
        $this->dispatch('toast', message: 'Talent pool "'.$pool->name.'" created.', variant: 'success');
    }

    /**
     * Hook for components that want to react after a candidate is saved.
     */
    protected function afterSavedToPool(User $candidate, TalentPool $pool): void {}
}
