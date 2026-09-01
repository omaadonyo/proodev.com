<?php

namespace App\Livewire;

use App\Services\TwoHourStreakService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TwoHourStreakWidget extends Component
{
    #[Computed]
    public function snapshot(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [
                'streak' => 0,
                'earned_xp' => 0,
                'can_earn' => false,
                'minutes_until_next' => 120,
                'progress' => 0,
                'xp_per_reward' => 10,
                'next_xp' => 10,
            ];
        }

        return app(TwoHourStreakService::class)->snapshot($user->fresh());
    }

    public function claim(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $xp = app(TwoHourStreakService::class)->tryAward($user->fresh());

        if ($xp > 0) {
            $this->dispatch('streak-claimed', xp: $xp);
            unset($this->snapshot);
        }
    }

    public function render()
    {
        return view('livewire.two-hour-streak-widget');
    }
}
