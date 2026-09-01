<?php

namespace App\Livewire;

use App\Services\TrendingService;
use Livewire\Component;

class HeaderTopTicker extends Component
{
    public function topEngineers()
    {
        return app(TrendingService::class)->topEngineers(10);
    }

    public function render()
    {
        return view('livewire.header-top-ticker');
    }
}
