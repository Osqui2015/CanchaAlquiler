<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use App\Models\ServiceCatalog;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $sports = [
      ['name' => 'Futbol', 'slug' => 'futbol'],
      ['name' => 'Tenis', 'slug' => 'tenis'],
      ['name' => 'Padel', 'slug' => 'padel'],
      ['name' => 'Basquet', 'slug' => 'basquet'],
      ['name' => 'Voley', 'slug' => 'voley'],
    ];

    foreach ($sports as $sport) {
      Sport::query()->updateOrCreate(
        ['slug' => $sport['slug']],
        [
          'name' => $sport['name'],
          'is_active' => true,
        ],
      );
    }

    $services = [
      ['name' => 'Estacionamiento', 'slug' => 'estacionamiento', 'icon' => 'parking'],
      ['name' => 'Vestuarios', 'slug' => 'vestuarios', 'icon' => 'locker'],
      ['name' => 'Bufet', 'slug' => 'bufet', 'icon' => 'utensils'],
      ['name' => 'Iluminacion nocturna', 'slug' => 'iluminacion-nocturna', 'icon' => 'lightbulb'],
      ['name' => 'Duchas', 'slug' => 'duchas', 'icon' => 'shower'],
    ];

    foreach ($services as $service) {
      ServiceCatalog::query()->updateOrCreate(
        ['slug' => $service['slug']],
        [
          'name' => $service['name'],
          'icon' => $service['icon'],
          'is_active' => true,
        ],
      );
    }

    $province = Province::query()->updateOrCreate(
      ['code' => 'TUC'],
      ['name' => 'Tucuman'],
    );

    City::query()->updateOrCreate(
      [
        'province_id' => $province->id,
        'name' => 'San Miguel de Tucuman',
      ],
      [],
    );
  }
}
