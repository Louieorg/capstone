<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('isAdmin', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('isAdviser', function ($user) {
            return $user->role === 'adviser';
        });

        // Fix SSL issue for local development
        $this->app->singleton(Client::class, function () {
            return new Client([
                'verify' => false,
            ]);
        });
    }
}