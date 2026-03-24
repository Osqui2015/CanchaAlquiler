<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\City;
use App\Models\Sport;
use App\Models\Complex;
use App\Models\Court;
use App\Models\User;
use App\Models\RecurringReservation;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TestRecurringReservationSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Province and city (create if not exists)
    $province = Province::firstOrCreate(
      ['name' => 'Provincia Demo'],
      ['code' => 'PD']
    );

    $city = City::firstOrCreate(
      ['province_id' => $province->id, 'name' => 'Ciudad Demo']
    );

    // Sport: Padel
    $sport = Sport::firstOrCreate(
      ['slug' => 'padel'],
      ['name' => 'Padel', 'is_active' => true]
    );

    // Complex: Del Galpon
    $complex = Complex::firstOrCreate(
      ['slug' => 'del-galpon'],
      [
        'city_id' => $city->id,
        'name' => 'Del Galpon',
        'address_line' => 'Av. Demo 123',
        'description' => 'Complejo Del Galpon - para pruebas',
        'phone_contact' => '+549000000000',
        'status' => Complex::STATUS_ACTIVO,
        'booking_enabled' => true,
      ]
    );

    // Court: Padel court in complex
    $court = Court::firstOrCreate(
      ['complex_id' => $complex->id, 'sport_id' => $sport->id, 'name' => 'Cancha Padel 1'],
      [
        'surface_type' => 'cesped_sintetico',
        'players_capacity' => 4,
        'slot_duration_minutes' => 60,
        'base_price' => 1000,
        'status' => Court::STATUS_ACTIVA,
      ]
    );

    // Test user
    $email = 'test.user+padel@example.com';
    $password = 'Secret1234!';

    $user = User::firstOrCreate(
      ['email' => $email],
      [
        'name' => 'Usuario Padel Test',
        'phone' => '+5491111111111',
        'password' => $password,
        'role' => User::ROLE_CLIENTE,
        'status' => User::STATUS_ACTIVO,
      ]
    );

    // Create recurring reservation: Mondays 20:00 - 22:00 (ISO weekday 1)
    $startDate = Carbon::today();
    $endDate = Carbon::today()->addMonths(6);

    RecurringReservation::updateOrCreate(
      [
        'complex_id' => $complex->id,
        'court_id' => $court->id,
        'client_user_id' => $user->id,
        'day_of_week' => 1,
        'start_time' => '20:00:00',
        'end_time' => '22:00:00',
      ],
      [
        'client_name' => $user->name,
        'client_phone' => $user->phone,
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
        'is_active' => true,
        'is_paid' => false,
        'notes' => 'Turno fijo generado por TestRecurringReservationSeeder',
      ]
    );

    // Output credentials to console when seeding interactively
    $this->command->info("Test user created: {$email} / {$password}");
  }
}
