<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);

        Broadcast::channel('user.{id}', fn ($user, $id) => (int) $user->id === (int) $id);

        Broadcast::channel('feed', fn () => true);
    }
}
