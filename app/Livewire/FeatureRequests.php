<?php

namespace App\Livewire;

use App\Models\FeatureRequest;
use App\Models\FeatureRequestVote;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FeatureRequests extends Component
{
    public string $filter = 'approved';

    public int $perPage = 10;

    public bool $showComposer = false;

    public string $title = '';

    public string $description = '';

    /** @var array<int, string|int> */
    public array $targets = [];

    #[Computed]
    public function counts(): array
    {
        return [
            'pending' => FeatureRequest::where('status', 'pending')->count(),
            'approved' => FeatureRequest::where('status', 'approved')->count(),
            'built' => FeatureRequest::where('status', 'built')->count(),
        ];
    }

    #[Computed]
    public function featureRequests()
    {
        return FeatureRequest::query()
            ->whereHas('user')
            ->when($this->filter !== 'all', fn ($q) => $q->where('status', $this->filter))
            ->with(['user:id,name'])
            ->withExists([
                'votesRelation as voted_by_me' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->orderBy('votes', 'desc')
            ->paginate($this->perPage);
    }

    public function mount(): void
    {
        //
    }

    public function setFilter(string $filter = 'approved'): void
    {
        if (! in_array($filter, ['approved', 'pending', 'built'], true)) {
            return;
        }

        $this->filter = $filter;
        $this->perPage = 10;
        unset($this->featureRequests);
    }

    public function toggleComposer(): void
    {
        $this->showComposer = ! $this->showComposer;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        FeatureRequest::create($validated + [
            'user_id' => auth()->id(),
            'status' => 'pending',
            'votes' => 0,
        ]);

        $this->reset('title', 'description');
        $this->showComposer = false;
        unset($this->counts, $this->featureRequests);

        $this->dispatch('feature-request-submitted');
        session()->flash('feature-request-status', __('Thanks! Your suggestion was sent for review.'));
    }

    public function vote(int $featureRequestId): void
    {
        $request = FeatureRequest::find($featureRequestId);

        if (! $request || $request->status === 'built') {
            return;
        }

        $existing = FeatureRequestVote::query()
            ->where('feature_request_id', $featureRequestId)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            $existing->delete();
            $request->decrement('votes');
        } else {
            FeatureRequestVote::create([
                'feature_request_id' => $featureRequestId,
                'user_id' => auth()->id(),
            ]);
            $request->increment('votes');
        }

        unset($this->featureRequests);
    }

    public function approve(int $featureRequestId): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $request = FeatureRequest::find($featureRequestId);

        if (! $request || $request->status !== 'pending') {
            return;
        }

        $target = max(1, (int) ($this->targets[$featureRequestId] ?? 10));

        $request->update([
            'status' => 'approved',
            'admin_target' => $target,
            'approved_at' => now(),
        ]);

        unset($this->targets[$featureRequestId], $this->counts, $this->featureRequests);
    }

    public function buildFeature(int $featureRequestId): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $feature = FeatureRequest::find($featureRequestId);

        if ($feature && $feature->votes >= $feature->admin_target && $feature->status !== 'built') {
            $feature->update(['status' => 'built', 'built_at' => now()]);
            unset($this->counts, $this->featureRequests);
        }
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
        unset($this->featureRequests);
    }

    public function render()
    {
        return view('livewire.feature-requests');
    }
}
