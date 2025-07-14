<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCalendarIntegration implements ShouldQueue
{
    public function handle(ReservationCreated $event): void
    {
        $reservation = $event->reservation;

        // Aqui você integraria com Google Calendar, Outlook, etc.
        // Exemplo:
        // CalendarService::addEvent(
        //     $reservation->start_time,
        //     $reservation->end_time,
        //     "Reserva: {$reservation->customer_name}"
        // );
    }
}
