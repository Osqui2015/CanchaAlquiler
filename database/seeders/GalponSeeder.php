<?php

namespace Database\Seeders;

use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\ComplexPolicy;
use App\Models\ComplexUserAssignment;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GalponSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'galpon@canchas.local'],
            [
                'name' => 'El Galpón Admin',
                'role' => User::ROLE_ADMIN_CANCHA,
                'password' => 'password',
                'status' => User::STATUS_ACTIVO,
            ],
        );

        // Find or create a City
        $city = City::first();

        // Find or create Sport Pádel
        $padel = Sport::query()->updateOrCreate(
            ['slug' => 'padel'],
            ['name' => 'Pádel', 'is_active' => true]
        );

        $complex = Complex::query()->updateOrCreate(
            ['slug' => 'el-galpon'],
            [
                'city_id' => $city->id ?? 1,
                'name' => 'El Galpón Pádel',
                'address_line' => 'Calle Falsa 123 (Demo)',
                'phone_contact' => '1122334455',
                'status' => Complex::STATUS_ACTIVO,
                'booking_enabled' => true,
            ]
        );

        ComplexPolicy::query()->updateOrCreate(
            ['complex_id' => $complex->id],
            [
                'deposit_percent' => 30,
                'cancel_limit_minutes' => 120,
                'refund_percent_before_limit' => 100,
                'no_show_penalty_percent' => 20,
            ]
        );

        ComplexUserAssignment::query()->updateOrCreate([
            'complex_id' => $complex->id,
            'user_id' => $user->id,
        ]);

        // Horarios: Todos los días de 09:00 a 01:00 (cierre de madrugada)
        for ($day = 1; $day <= 7; $day++) {
            ComplexOpeningHour::query()->updateOrCreate(
                ['complex_id' => $complex->id, 'day_of_week' => $day],
                [
                    'is_open' => true,
                    'open_time' => '09:00:00',
                    'close_time' => '01:00:00',
                ]
            );
        }

        // Crear las 4 canchas
        for ($i = 1; $i <= 4; $i++) {
            Court::query()->updateOrCreate(
                ['complex_id' => $complex->id, 'name' => 'Cancha ' . $i],
                [
                    'sport_id' => $padel->id,
                    'surface_type' => 'cesped_sintetico',
                    'players_capacity' => 4,
                    'slot_duration_minutes' => 90,
                    'base_price' => 10000.00,
                    'status' => Court::STATUS_ACTIVA,
                ]
            );
        }
    }
}
