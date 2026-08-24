<?php

namespace App\Livewire;

use App\Models\FeatureRequest;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FeedSuggestions extends Component
{
    public ?string $title = '';

    public ?string $description = '';

    public function vote(int $id): void
    {
        $request = FeatureRequest::where('status', FeatureRequest::STATUS_APPROVED)->findOrFail($id);

        $existing = $request->votes()->where('user_id', auth()->id())->first();

        if ($existing) {
            $existing->delete();

            return;
        }

        $request->votes()->create(['user_id' => auth()->id(), 'created_at' => now()]);

        // Vote target reached — the feature is developed and included.
        if ($request->fresh()->hasReachedTarget()) {
            $request->markIncluded();
            unset($this->requests, $this->requestsCount, $this->included);
            Flux::toast(variant: 'success', text: 'Target reached — "'.$request->title.'" is being developed!');
            $this->dispatch('feature-request-included');
        }
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        FeatureRequest::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
            'status' => FeatureRequest::STATUS_PENDING,
            'target_votes' => 50,
            'created_by' => auth()->id(),
        ]);

        $this->reset('title', 'description');
        unset($this->requestsCount);

        Flux::toast(variant: 'success', text: 'Suggestion submitted — it will appear here once approved.');
    }

    /**
     * Every approved feature request, most voted first.
     */
    #[Computed]
    public function requests()
    {
        return FeatureRequest::query()
            ->where('status', FeatureRequest::STATUS_APPROVED)
            ->withCount('votes')
            ->orderByDesc('votes_count')
            ->get();
    }

    #[Computed]
    public function requestsCount(): int
    {
        return FeatureRequest::where('status', FeatureRequest::STATUS_APPROVED)->count();
    }

    #[Computed]
    public function included()
    {
        return FeatureRequest::query()
            ->where('status', FeatureRequest::STATUS_INCLUDED)
            ->withCount('votes')
            ->orderByDesc('included_at')
            ->take(3)
            ->get();
    }

    public function render()
    {
        return view('livewire.feed-suggestions');
    }
}