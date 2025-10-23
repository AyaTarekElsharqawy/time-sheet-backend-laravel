<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register application services, bindings or singletons here if needed.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bootstrap logic, e.g., custom macros or model observers, can be placed here.
    }
}
