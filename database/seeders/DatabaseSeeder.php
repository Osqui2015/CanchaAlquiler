<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);
        $this->call(DemoFootballSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'role' => User::ROLE_CLIENTE,
                'password' => 'password',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@cancha.local'],
            [
                'name' => 'Super Admin',
                'role' => User::ROLE_SUPER_ADMIN,
                'password' => 'password',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admincancha@cancha.local'],
            [
                'name' => 'Admin Cancha Demo',
                'role' => User::ROLE_ADMIN_CANCHA,
                'password' => 'password',
            ],
        );
    }
}
