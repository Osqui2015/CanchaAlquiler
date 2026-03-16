<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\Court;
use App\Models\Province;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AvailabilitySearchTest extends TestCase
{
  use RefreshDatabase;

  public function test_it_returns_only_courts_with_real_availability(): void
  {
    $targetDate = now()->addDays(2);

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
      'name' => 'Complejo Norte',
      'slug' => 'complejo-norte',
      'address_line' => 'Av. Siempreviva 123',
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

    $availableCourt = Court::create([
      'complex_id' => $complex->id,
      'sport_id' => $sport->id,
      'name' => 'Cancha 1',
      'surface_type' => 'cesped_sintetico',
      'players_capacity' => 10,
      'slot_duration_minutes' => 60,
      'base_price' => 35000,
      'status' => Court::STATUS_ACTIVA,
    ]);

    $busyCourt = Court::create([
      'complex_id' => $complex->id,
      'sport_id' => $sport->id,
      'name' => 'Cancha 2',
      'surface_type' => 'cesped_sintetico',
      'players_capacity' => 10,
      'slot_duration_minutes' => 60,
      'base_price' => 35000,
      'status' => Court::STATUS_ACTIVA,
    ]);

    $client = User::factory()->create();

    Reservation::create([
      'code' => 'RES-BUSY-001',
      'client_user_id' => $client->id,
      'complex_id' => $complex->id,
      'court_id' => $busyCourt->id,
      'sport_id' => $sport->id,
      'start_at' => $targetDate->copy()->setTime(20, 0),
      'end_at' => $targetDate->copy()->setTime(21, 0),
      'total_amount' => 35000,
      'deposit_amount' => 10500,
      'currency' => 'ARS',
      'status' => Reservation::STATUS_CONFIRMADA,
    ]);

    $response = $this->get('/?' . http_build_query([
      'province_id' => $province->id,
      'sport_id' => $sport->id,
      'city_id' => $city->id,
      'date' => $targetDate->toDateString(),
      'start_time' => '20:00',
      'end_time' => '21:00',
    ]));

    $response->assertOk();
    $response->assertInertia(
      fn(Assert $page) => $page
        ->component('Home')
        ->has('availability', 1)
        ->where('availability.0.complex.id', $complex->id)
        ->has('availability.0.courts', 1)
        ->where('availability.0.courts.0.id', $availableCourt->id)
    );
  }
}
