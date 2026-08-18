<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\Ad;
use App\Models\Sponsor;
use App\Models\User;
use App\Services\TrendingService;
use App\Support\FeatureFlags;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Wirechat\Wirechat\Enums\ConversationType;

class RightPanel extends Component
{
    #[Computed]
    public function ads(): Collection
    {
        return Ad::query()
            ->where('is_active', true)
            ->running()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
    }

    #[Computed]
    public function trendingProjects()
    {
        return app(TrendingService::class)->trendingProjects();
    }

    #[Computed]
    public function topEngineers()
    {
        return app(TrendingService::class)->topEngineers();
    }

    #[Computed]
    public function topHundred(): Collection
    {
        return app(TrendingService::class)->topEngineers(100);
    }

    #[Computed]
    public function onlineUsers(): Collection
    {
        if (! FeatureFlags::publicPresenceEnabled()) {
            return collect();
        }

        return User::query()
            ->visibleToPublic()
            ->where('last_activity_at', '>', now()->subMinutes(5))
            ->where('role', UserRole::Developer)
            ->orderByDesc('last_activity_at')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function sponsors(): Collection
    {
        return Sponsor::query()
            ->where('is_active', true)
            ->running()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * IDs of users the viewer already has a private conversation with,
     * so leaderboard rows can flag existing chats.
     */
    #[Computed]
    public function chatPeerIds(): Collection
    {
        if (! auth()->check()) {
            return collect();
        }

        return auth()->user()
            ->conversations()
            ->where('type', ConversationType::PRIVATE)
            ->with('participants')
            ->get()
            ->flatMap(fn ($conversation) => $conversation->participants->pluck('participantable_id'))
            ->unique()
            ->values();
    }

    public function connect(int $userId): void
    {
        $viewer = auth()->user();

        // Chat connections are only available to verified users.
        if (! $viewer?->isVerified()) {
            abort(403);
        }

        if ($userId === $viewer->id) {
            abort(403);
        }

        $conversation = $viewer->createConversationWith(User::findOrFail($userId));

        if (! $conversation) {
            Flux::toast(variant: 'danger', text: 'Could not start a conversation right now.');

            return;
        }

        $this->redirectRoute('wirechat.chats.chat', $conversation, navigate: true);
    }

    #[On('echo:feed,feed-event')]
    public function refresh(): void
    {
        unset(
            $this->ads,
            $this->trendingProjects,
            $this->topEngineers,
            $this->topHundred,
            $this->onlineUsers,
            $this->sponsors,
            $this->chatPeerIds,
        );
    }

    public function render(): View
    {
        return view('livewire.right-panel');
    }
}
