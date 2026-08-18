<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\Wirechat\AdminChatsPanelProvider;
use App\Providers\Wirechat\ChatsPanelProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    BroadcastServiceProvider::class,
    ChatsPanelProvider::class,
    AdminChatsPanelProvider::class,
];
