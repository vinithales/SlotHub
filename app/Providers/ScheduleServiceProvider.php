<?php

namespace App\Providers;

use App\Services\ScheduleGenerator;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ScheduleGenerator::class, function ($app) {
            return new ScheduleGenerator(
                config('schedule.default_interval'),
                $app->make('cache.store')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
