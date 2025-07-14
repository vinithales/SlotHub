<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use App\Mail\ReservationConfirmation;
use App\Notifications\NewReservation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Mail\Mailable;

class SendReservationNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
     public function handle(ReservationCreated $event): void
    {
        $reservation = $event->reservation;

        // Envia email para o cliente
        Mail::to($reservation->customer_email)
            ->send(new ReservationConfirmation($reservation));

        // Notifica administradores
        Notification::send(
            $reservation->business->administrators,
            new NewReservation($reservation)
        );
    }
}
