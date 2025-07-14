<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use App\Events\ReservationCreated;
use App\Events\ReservationCancelled;
use App\Listeners\SendReservationNotification;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ReservationCreated::class => [
            SendReservationNotification::class,
            \App\Listeners\UpdateCalendarIntegration::class,
        ],
        ReservationCancelled::class => [
            \App\Listeners\HandleCancellation::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
