<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        Gate::before(function (User $user) {
            return $user->isAdmin() ? true : null;
        });
    }
}
