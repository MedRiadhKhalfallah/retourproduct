<?php

namespace App\Providers;

use App\Models\Item;
use App\Observers\ItemObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
// Force HTTPS en production (derrière le proxy de Render)
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
}
