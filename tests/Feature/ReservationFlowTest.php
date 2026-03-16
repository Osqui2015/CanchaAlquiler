<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\ComplexPolicy;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Province;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
  use RefreshDatabase;

  public function test_it_prevents_double_booking_on_same_court(): void
  {
    $targetDate = now()->addDays(3);

    [$city, $sport, $complex, $court] = $this->createBaseScenario($targetDate);

    $client = User::factory()->create([
      'role' => User::ROLE_CLIENTE,
    ]);

    $payload = [
      'court_id' => $court->id,
      'date' => $targetDate->toDateString(),
      'start_time' => '19:00',
      'end_time' => '20:00',
    ];

    $this->actingAs($client)
      ->post('/panel/cliente/reservas', $payload)
      ->assertRedirect('/panel/cliente');

    $this->actingAs($client)
      ->post('/panel/cliente/reservas', $payload)
      ->assertSessionHasErrors('court_id');
  }

  public function test_client_cannot_cancel_when_policy_window_expired(): void
  {
    $targetDate = now()->addHour();
    $reservationStart = now()->addMinutes(60);
    $reservationEnd = $reservationStart->copy()->addHour();

    [, $sport, $complex, $court] = $this->createBaseScenario($targetDate);

    ComplexPolicy::create([
      'complex_id' => $complex->id,
      'deposit_percent' => 30,
      'cancel_limit_minutes' => 120,
      'refund_percent_before_limit' => 100,
      'no_show_penalty_percent' => 100,
    ]);

    $client = User::factory()->create([
      'role' => User::ROLE_CLIENTE,
    ]);

    $reservation = Reservation::create([
      'code' => 'RES-CANCEL-001',
      'client_user_id' => $client->id,
      'complex_id' => $complex->id,
      'court_id' => $court->id,
      'sport_id' => $sport->id,
      'start_at' => $reservationStart,
      'end_at' => $reservationEnd,
      'total_amount' => 35000,
      'deposit_amount' => 10500,
      'currency' => 'ARS',
      'status' => Reservation::STATUS_CONFIRMADA,
    ]);

    $this->actingAs($client)
      ->post('/panel/cliente/reservas/' . $reservation->id . '/cancelar', [
        'reason' => 'No puedo asistir',
      ])
      ->assertSessionHasErrors('reservation');
  }

  public function test_client_can_cancel_pending_payment_even_when_policy_window_expired(): void
  {
    $targetDate = now()->addHour();
    $reservationStart = now()->addMinutes(60);
    $reservationEnd = $reservationStart->copy()->addHour();

    [, $sport, $complex, $court] = $this->createBaseScenario($targetDate);

    ComplexPolicy::create([
      'complex_id' => $complex->id,
      'deposit_percent' => 30,
      'cancel_limit_minutes' => 120,
      'refund_percent_before_limit' => 100,
      'no_show_penalty_percent' => 100,
    ]);

    $client = User::factory()->create([
      'role' => User::ROLE_CLIENTE,
    ]);

    $reservation = Reservation::create([
      'code' => 'RES-CANCEL-002',
      'client_user_id' => $client->id,
      'complex_id' => $complex->id,
      'court_id' => $court->id,
      'sport_id' => $sport->id,
      'start_at' => $reservationStart,
      'end_at' => $reservationEnd,
      'total_amount' => 35000,
      'deposit_amount' => 10500,
      'currency' => 'ARS',
      'status' => Reservation::STATUS_PENDIENTE_PAGO,
    ]);

    $this->actingAs($client)
      ->post('/panel/cliente/reservas/' . $reservation->id . '/cancelar', [
        'reason' => 'Cambio de planes',
      ])
      ->assertRedirect('/panel/cliente');

    $this->assertDatabaseHas('reservations', [
      'id' => $reservation->id,
      'status' => Reservation::STATUS_CANCELADA,
    ]);
  }

  public function test_approved_webhook_confirms_pending_reservation(): void
  {
    $targetDate = now()->addDays(4);

    [,,, $court] = $this->createBaseScenario($targetDate);

    $client = User::factory()->create([
      'role' => User::ROLE_CLIENTE,
    ]);

    $this->actingAs($client)
      ->post('/panel/cliente/reservas', [
        'court_id' => $court->id,
        'date' => $targetDate->toDateString(),
        'start_time' => '20:00',
        'end_time' => '21:00',
      ])
      ->assertRedirect('/panel/cliente');

    $reservationId = Reservation::query()->latest('id')->value('id');

    $this->actingAs($client)
      ->post('/panel/cliente/reservas/' . $reservationId . '/checkout')
      ->assertRedirect('/panel/cliente');

    $providerPaymentId = Payment::query()
      ->latest('id')
      ->value('provider_payment_id');

    $this->actingAs($client)
      ->post('/panel/cliente/reservas/' . $reservationId . '/checkout/aprobar-demo')
      ->assertRedirect('/panel/cliente');

    $this->assertDatabaseHas('reservations', [
      'id' => $reservationId,
      'status' => Reservation::STATUS_CONFIRMADA,
    ]);

    $this->assertDatabaseHas('payments', [
      'provider_payment_id' => $providerPaymentId,
      'status' => Payment::STATUS_APPROVED,
    ]);
  }

  /**
   * @return array{City, Sport, Complex, Court}
   */
  private function createBaseScenario($targetDate): array
  {
    $province = Province::create([
      'name' => 'Tucuman',
      'code' => 'TUC',
    ]);

    $city = City::create([
      'province_id' => $province->id,
      'name' => 'San Miguel de Tucuman',
    ]);

    $sport = Sport::create([
      'name' => 'Futbol',
      'slug' => 'futbol',
      'is_active' => true,
    ]);

    $complex = Complex::create([
      'city_id' => $city->id,
      'name' => 'Complejo Central',
      'slug' => 'complejo-central',
      'address_line' => 'Av. Belgrano 500',
      'status' => Complex::STATUS_ACTIVO,
      'booking_enabled' => true,
    ]);

    ComplexOpeningHour::create([
      'complex_id' => $complex->id,
      'day_of_week' => $targetDate->isoWeekday(),
      'is_open' => true,
      'open_time' => '08:00',
      'close_time' => '23:00',
    ]);

    $court = Court::create([
      'complex_id' => $complex->id,
      'sport_id' => $sport->id,
      'name' => 'Cancha Principal',
      'surface_type' => 'cesped_sintetico',
      'players_capacity' => 10,
      'slot_duration_minutes' => 60,
      'base_price' => 35000,
      'status' => Court::STATUS_ACTIVA,
    ]);

    return [$city, $sport, $complex, $court];
  }
}
