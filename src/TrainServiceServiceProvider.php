<?php

namespace TrainVox\TrainService;

use Illuminate\Support\ServiceProvider;

class TrainServiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the TrainService as a singleton or bind it
        $this->app->singleton(TrainService::class, function ($app) {
            return new TrainService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Optional: Load routes, config, etc., if needed
    }
}
