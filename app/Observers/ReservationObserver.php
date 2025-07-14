<?php

namespace App\Observers;

namespace App\Observers;

use App\Models\Reservation;
use App\Events\ReservationCreated;
use App\Events\ReservationUpdated;
use App\Events\ReservationCancelled;

class ReservationObserver
{
    public function created(Reservation $reservation): void
    {
        // Dispara evento de criação
        event(new ReservationCreated($reservation));

        // Atualiza status do slot relacionado
        $reservation->availabilitySchedule()->update(['status' => 'booked']);

        // Log de auditoria
        activity()
            ->performedOn($reservation)
            ->withProperties(['customer' => $reservation->customer_email])
            ->log('Nova reserva criada');
    }

    public function updated(Reservation $reservation): void
    {
        // Verifica se o status mudou para "cancelled"
        if ($reservation->isDirty('status') && $reservation->status === 'cancelled') {
            event(new ReservationCancelled($reservation));

            // Libera o slot
            $reservation->availabilitySchedule()->update(['status' => 'available']);
        } else {
            event(new ReservationUpdated($reservation));
        }
    }

    public function deleted(Reservation $reservation): void
    {
        // Libera o slot ao deletar
        $reservation->availabilitySchedule()->update(['status' => 'available']);
    }


    /**
     * Handle the Reservation "restored" event.
     */
    public function restored(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "force deleted" event.
     */
    public function forceDeleted(Reservation $reservation): void
    {
        //
    }
}
