<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Reservation;
use App\Models\AvailabilitySchedule;
use Illuminate\Support\Facades\Event;
use App\Events\ReservationCreated;
use App\Events\ReservationCancelled;
use App\Events\ReservationConfirmation;
use App\Observers\ReservationObserver;

class ReservationObserverTest extends TestCase
{
    /** @test */
    public function it_dispatches_event_and_updates_slot_on_creation()
    {
        Event::fake();

        $slot = AvailabilitySchedule::factory()->create(['status' => 'available']);
        $reservation = Reservation::factory()->create([
            'availability_schedule_id' => $slot->id
        ]);

        Event::assertDispatched(ReservationCreated::class, function ($event) use ($reservation) {
            return $event->reservation->id === $reservation->id;
        });

        $this->assertDatabaseHas('availability_schedules', [
            'id' => $slot->id,
            'status' => 'booked'
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Reservation::class,
            'subject_id' => $reservation->id
        ]);
    }

    /** @test */
    public function it_handles_cancellation_correctly()
    {
        Event::fake();

        $slot = AvailabilitySchedule::factory()->create(['status' => 'booked']);
        $reservation = Reservation::factory()->create([
            'availability_schedule_id' => $slot->id,
            'status' => 'confirmed'
        ]);

        // Simula cancelamento
        $reservation->update(['status' => 'cancelled']);

        // Verifica se o slot foi liberado
        $this->assertDatabaseHas('availability_schedules', [
            'id' => $slot->id,
            'status' => 'available'
        ]);

        // Verifica se o evento de cancelamento foi disparado
        Event::assertDispatched(ReservationCancelled::class);
    }

    /** @test */
    public function it_frees_slot_when_reservation_is_deleted()
    {
        $slot = AvailabilitySchedule::factory()->create(['status' => 'booked']);
        $reservation = Reservation::factory()->create([
            'availability_schedule_id' => $slot->id
        ]);

        $reservation->delete();

        $this->assertDatabaseHas('availability_schedules', [
            'id' => $slot->id,
            'status' => 'available'
        ]);
    }

    /** @test */
    public function it_triggers_notifications_when_reservation_is_created()
    {
        Notification::fake();
        Mail::fake();

        $admin = User::factory()->create();
        $business = Business::factory()->create();
        $business->administrators()->attach($admin);

        $slot = AvailabilitySchedule::factory()->create([
            'business_id' => $business->id
        ]);

        $reservation = Reservation::factory()->create([
            'availability_schedule_id' => $slot->id,
            'customer_email' => 'test@example.com'
        ]);

        // Verifica notificação
        Notification::assertSentTo(
            [$admin],
            \App\Notifications\NewReservation::class
        );

        // Verifica email
        Mail::assertSent(ReservationConfirmation::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }
    /** @test */
    public function it_handles_slot_update_failures_gracefully()
    {
        // Mock para simular falha
        $mock = $this->partialMock(AvailabilitySchedule::class, function ($mock) {
            $mock->shouldReceive('update')->andThrow(new \Exception('Database error'));
        });

        $reservation = Reservation::factory()->create([
            'availability_schedule_id' => $mock->id
        ]);

        // Verifica se o sistema continua funcionando apesar da falha
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id
        ]);

        // Verifica se o erro foi logado
        Log::assertLogged('error', function ($message) {
            return str_contains($message, 'Failed to update slot status');
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Configura fuso horário consistente para testes
        config(['app.timezone' => 'UTC']);

        // Prepara o observer
        $this->observer = new ReservationObserver();

        // Configura o mock de log
        Log::swap(new \Illuminate\Log\LogManager(app()));
    }

    /** @test */
public function it_correctly_identifies_status_changes()
{
    $observer = new class extends ReservationObserver {
        public function publicIsStatusChanged(Reservation $reservation, string $status)
        {
            return $this->isStatusChanged($reservation, $status);
        }
    };

    $reservation = Reservation::factory()->create(['status' => 'confirmed']);
    $reservation->status = 'cancelled';

    $this->assertTrue($observer->publicIsStatusChanged($reservation, 'cancelled'));
    $this->assertFalse($observer->publicIsStatusChanged($reservation, 'confirmed'));
}
}
