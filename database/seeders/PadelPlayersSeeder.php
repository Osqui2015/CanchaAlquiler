<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sport;
use App\Models\UserRanking;
use Illuminate\Database\Seeder;

class PadelPlayersSeeder extends Seeder
{
  public function run(): void
  {
    $sport = Sport::query()->where('slug', 'padel')->first();
    if (! $sport) {
      $this->command->warn('Sport "padel" not found, skipping PadelPlayersSeeder');
      return;
    }

    $players = [
      ['name' => 'Martín Díaz', 'email' => 'martin.diaz+padel@example.com'],
      ['name' => 'Lucía Pérez', 'email' => 'lucia.perez+padel@example.com'],
      ['name' => 'Santiago Gómez', 'email' => 'santiago.gomez+padel@example.com'],
      ['name' => 'Valentina Ruiz', 'email' => 'valentina.ruiz+padel@example.com'],
      ['name' => 'Diego Herrera', 'email' => 'diego.herrera+padel@example.com'],
      ['name' => 'María López', 'email' => 'maria.lopez+padel@example.com'],
      ['name' => 'Pablo Fernández', 'email' => 'pablo.fernandez+padel@example.com'],
      ['name' => 'Ana Morales', 'email' => 'ana.morales+padel@example.com'],
    ];

    $rank = 1;
    foreach ($players as $p) {
      $user = User::query()->updateOrCreate(
        ['email' => $p['email']],
        [
          'name' => $p['name'],
          'role' => User::ROLE_CLIENTE,
          'password' => 'password',
          'status' => User::STATUS_ACTIVO,
        ],
      );

      // create or update a ranking entry for padel (DB currently stores only rankid and points)
      UserRanking::query()->updateOrCreate(
        [
          'user_id' => $user->id,
          'sport_id' => $sport->id,
        ],
        [
          'rankid' => sprintf('PAD-%03d', $rank),
          'points' => max(0, 1000 - ($rank - 1) * 50),
        ],
      );

      $rank++;
    }
  }
}
