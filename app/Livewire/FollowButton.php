<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class FollowButton extends Component
{
    public int $userId;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
    }

    public function toggle(): void
    {
        $viewer = auth()->user();
        $target = User::findOrFail($this->userId);

        if ($viewer->id === $target->id) {
            return;
        }

        if ($viewer->isFollowing($target)) {
            $viewer->unfollow($target);
        } else {
            $viewer->follow($target);
        }
    }

    public function render()
    {
        $viewer = auth()->user();
        $target = User::find($this->userId);
        $isFollowing = $viewer ? $viewer->isFollowing($target) : false;

        return view('livewire.follow-button', [
            'isFollowing' => $isFollowing,
            'target' => $target,
        ]);
    }
}
