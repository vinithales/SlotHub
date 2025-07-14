<?php

namespace App\Listeners;

use App\Events\ReservationCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleCancellation implements ShouldQueue
{
    public function handle(ReservationCancelled $event): void
    {
        $reservation = $event->reservation;

        // 1. Reembolso se aplicável
        // PaymentService::processRefund($reservation);

        // 2. Atualizar calendário
        // CalendarService::removeEvent($reservation->calendar_event_id);

        // 3. Notificar equipe
        // $reservation->business->notify(new ReservationCancelledNotification($reservation));
    }
}
