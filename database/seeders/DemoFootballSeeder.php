<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\ComplexPolicy;
use App\Models\ComplexTeamBoardPost;
use App\Models\ComplexTournament;
use App\Models\ComplexUserAssignment;
use App\Models\Court;
use App\Models\Province;
use App\Models\Sport;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoFootballSeeder extends Seeder
{
    /**
     * Seed demo football data.
     */
    public function run(): void
    {
        $province = Province::query()->updateOrCreate(
            ['code' => 'TUC'],
            ['name' => 'Tucuman'],
        );

        $city = City::query()->updateOrCreate(
            [
                'province_id' => $province->id,
                'name' => 'San Miguel de Tucuman',
            ],
            [],
        );

        $sportsBySlug = [
            'futbol' => Sport::query()->updateOrCreate(['slug' => 'futbol'], ['name' => 'Futbol', 'is_active' => true]),
            'padel' => Sport::query()->updateOrCreate(['slug' => 'padel'], ['name' => 'Padel', 'is_active' => true]),
            'tenis' => Sport::query()->updateOrCreate(['slug' => 'tenis'], ['name' => 'Tenis', 'is_active' => true]),
            'basquet' => Sport::query()->updateOrCreate(['slug' => 'basquet'], ['name' => 'Basquet', 'is_active' => true]),
            'voley' => Sport::query()->updateOrCreate(['slug' => 'voley'], ['name' => 'Voley', 'is_active' => true]),
        ];

        $adminCancha = User::query()->where('email', 'admincancha@cancha.local')->first();

        $complexes = [
            [
                'slug' => 'complejo-futbol-demo',
                'name' => 'Complejo Futbol Demo',
                'address_line' => 'Av. Mate de Luna 1200',
                'latitude' => -26.8197000,
                'longitude' => -65.2199000,
                'description' => 'Complejo demo con canchas de Futbol 5 y Futbol 11.',
                'phone_contact' => '3815000000',
                'courts' => [
                    [
                        'name' => 'Cancha Futbol 5 - Norte',
                        'sport_slug' => 'futbol',
                        'surface_type' => 'cesped_sintetico',
                        'players_capacity' => 10,
                        'slot_duration_minutes' => 60,
                        'base_price' => 28000,
                    ],
                    [
                        'name' => 'Cancha Futbol 11 - Central',
                        'sport_slug' => 'futbol',
                        'surface_type' => 'cesped_sintetico',
                        'players_capacity' => 22,
                        'slot_duration_minutes' => 60,
                        'base_price' => 65000,
                    ],
                    [
                        'name' => 'Cancha Padel - 1',
                        'sport_slug' => 'padel',
                        'surface_type' => 'cemento',
                        'players_capacity' => 4,
                        'slot_duration_minutes' => 90,
                        'base_price' => 24000,
                    ],
                ],
                'tournaments' => [
                    [
                        'sport_slug' => 'futbol',
                        'name' => 'Copa Apertura Futbol 5',
                        'category' => 'Libre',
                        'format' => 'Fase de grupos + playoffs',
                        'start_date' => now()->addDays(8)->toDateString(),
                        'end_date' => now()->addDays(35)->toDateString(),
                        'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
                        'max_teams' => 16,
                        'entry_fee' => 120000,
                        'prize' => 'Trofeo + premios',
                        'notes' => 'Partidos nocturnos entre semana.',
                        'teams' => [
                            ['name' => 'Norte FC', 'matches' => 4, 'wins' => 3, 'draws' => 1, 'losses' => 0, 'goal_diff' => 8, 'points' => 10, 'position' => 1],
                            ['name' => 'Los Del Oeste', 'matches' => 4, 'wins' => 2, 'draws' => 1, 'losses' => 1, 'goal_diff' => 3, 'points' => 7, 'position' => 2],
                            ['name' => 'Cancheros United', 'matches' => 4, 'wins' => 2, 'draws' => 0, 'losses' => 2, 'goal_diff' => 0, 'points' => 6, 'position' => 3],
                            ['name' => 'Resto FC', 'matches' => 4, 'wins' => 1, 'draws' => 0, 'losses' => 3, 'goal_diff' => -5, 'points' => 3, 'position' => 4],
                        ],
                    ],
                    [
                        'sport_slug' => 'futbol',
                        'name' => 'Liga Futbol 11 Nocturna',
                        'category' => 'Primera',
                        'format' => 'Todos contra todos',
                        'start_date' => now()->addDays(12)->toDateString(),
                        'end_date' => now()->addDays(42)->toDateString(),
                        'status' => ComplexTournament::STATUS_CUPOS_LIMITADOS,
                        'max_teams' => 10,
                        'entry_fee' => 180000,
                        'prize' => 'Premio en efectivo + trofeo',
                        'notes' => 'Partidos viernes por la noche.',
                        'teams' => [
                            ['name' => 'Central 11', 'matches' => 5, 'wins' => 4, 'draws' => 0, 'losses' => 1, 'goal_diff' => 10, 'points' => 12, 'position' => 1],
                            ['name' => 'Barrio Unido', 'matches' => 5, 'wins' => 3, 'draws' => 1, 'losses' => 1, 'goal_diff' => 6, 'points' => 10, 'position' => 2],
                            ['name' => 'Atletico Luna', 'matches' => 5, 'wins' => 2, 'draws' => 2, 'losses' => 1, 'goal_diff' => 2, 'points' => 8, 'position' => 3],
                        ],
                    ],
                    [
                        'sport_slug' => 'padel',
                        'name' => 'Padel 6ta Categoria Apertura',
                        'category' => '6ta',
                        'format' => 'Eliminacion directa',
                        'start_date' => now()->addDays(18)->toDateString(),
                        'end_date' => now()->addDays(20)->toDateString(),
                        'status' => ComplexTournament::STATUS_CUPOS_LIMITADOS,
                        'max_teams' => 12,
                        'entry_fee' => 90000,
                        'prize' => 'Premio en efectivo',
                        'notes' => null,
                        'teams' => [
                            ['name' => 'Smash Team', 'matches' => 4, 'wins' => 3, 'draws' => 0, 'losses' => 1, 'goal_diff' => 5, 'points' => 9, 'position' => 1],
                            ['name' => 'Bandeja Pro', 'matches' => 4, 'wins' => 2, 'draws' => 1, 'losses' => 1, 'goal_diff' => 2, 'points' => 7, 'position' => 2],
                            ['name' => 'Vibora Pair', 'matches' => 4, 'wins' => 1, 'draws' => 1, 'losses' => 2, 'goal_diff' => -1, 'points' => 4, 'position' => 3],
                        ],
                    ],
                    [
                        'sport_slug' => 'padel',
                        'name' => 'Padel 7ma Categoria Clausura',
                        'category' => '7ma',
                        'format' => 'Round robin',
                        'start_date' => now()->addDays(25)->toDateString(),
                        'end_date' => now()->addDays(48)->toDateString(),
                        'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
                        'max_teams' => 14,
                        'entry_fee' => 75000,
                        'prize' => 'Indumentaria deportiva',
                        'notes' => 'Fecha doble los domingos.',
                        'teams' => [
                            ['name' => 'Globo Team', 'matches' => 5, 'wins' => 4, 'draws' => 0, 'losses' => 1, 'goal_diff' => 6, 'points' => 12, 'position' => 1],
                            ['name' => 'Paredon Duo', 'matches' => 5, 'wins' => 3, 'draws' => 1, 'losses' => 1, 'goal_diff' => 3, 'points' => 10, 'position' => 2],
                            ['name' => 'Topspin Bros', 'matches' => 5, 'wins' => 2, 'draws' => 1, 'losses' => 2, 'goal_diff' => 0, 'points' => 7, 'position' => 3],
                        ],
                    ],
                    [
                        'sport_slug' => 'padel',
                        'name' => 'Ranking Individual Padel',
                        'category' => 'Individual',
                        'format' => 'Individual por puntos',
                        'start_date' => now()->addDays(7)->toDateString(),
                        'end_date' => now()->addDays(60)->toDateString(),
                        'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
                        'max_teams' => 32,
                        'entry_fee' => 35000,
                        'prize' => 'Premio al top 3',
                        'notes' => 'Ranking individual actualizado semanalmente.',
                        'teams' => [
                            ['name' => 'Juan Perez', 'matches' => 8, 'wins' => 6, 'draws' => 0, 'losses' => 2, 'goal_diff' => 11, 'points' => 18, 'position' => 1],
                            ['name' => 'Franco Diaz', 'matches' => 8, 'wins' => 5, 'draws' => 1, 'losses' => 2, 'goal_diff' => 8, 'points' => 16, 'position' => 2],
                            ['name' => 'Lucas Rios', 'matches' => 8, 'wins' => 5, 'draws' => 0, 'losses' => 3, 'goal_diff' => 5, 'points' => 15, 'position' => 3],
                            ['name' => 'Mateo Sosa', 'matches' => 8, 'wins' => 4, 'draws' => 1, 'losses' => 3, 'goal_diff' => 2, 'points' => 13, 'position' => 4],
                        ],
                    ],
                ],
                'team_board_posts' => [
                    [
                        'sport_slug' => 'futbol',
                        'kind' => ComplexTeamBoardPost::KIND_FALTA_JUGADOR,
                        'title' => 'Los Pibes del Norte buscan 1 jugador',
                        'level' => 'Intermedio',
                        'needed_players' => 1,
                        'play_day' => 'Martes',
                        'play_time' => '21:00',
                        'contact' => '381-555-0101',
                        'notes' => 'Preferimos volante.',
                    ],
                    [
                        'sport_slug' => 'padel',
                        'kind' => ComplexTeamBoardPost::KIND_BUSCO_RIVAL,
                        'title' => 'Pareja de padel busca rival',
                        'level' => 'Intermedio',
                        'needed_players' => null,
                        'play_day' => 'Jueves',
                        'play_time' => '20:30',
                        'contact' => '381-555-0102',
                        'notes' => null,
                    ],
                ],
            ],
            [
                'slug' => 'complejo-barrio-norte',
                'name' => 'Complejo Barrio Norte',
                'address_line' => 'Av. Sarmiento 800',
                'latitude' => -26.8063000,
                'longitude' => -65.2119000,
                'description' => 'Predio con canchas rapidas para turnos entre semana y fin de semana.',
                'phone_contact' => '3815000011',
                'courts' => [
                    [
                        'name' => 'Cancha Futbol 5 - A',
                        'sport_slug' => 'futbol',
                        'surface_type' => 'cesped_sintetico',
                        'players_capacity' => 10,
                        'slot_duration_minutes' => 60,
                        'base_price' => 30000,
                    ],
                    [
                        'name' => 'Cancha Futbol 5 - B',
                        'sport_slug' => 'futbol',
                        'surface_type' => 'cesped_sintetico',
                        'players_capacity' => 10,
                        'slot_duration_minutes' => 60,
                        'base_price' => 32000,
                    ],
                    [
                        'name' => 'Cancha Tenis - Clay',
                        'sport_slug' => 'tenis',
                        'surface_type' => 'polvo_ladrillo',
                        'players_capacity' => 2,
                        'slot_duration_minutes' => 60,
                        'base_price' => 26000,
                    ],
                ],
                'tournaments' => [
                    [
                        'sport_slug' => 'tenis',
                        'name' => 'Master Tenis Barrio Norte',
                        'category' => 'Amateur',
                        'format' => 'Round robin',
                        'start_date' => now()->addDays(6)->toDateString(),
                        'end_date' => now()->addDays(28)->toDateString(),
                        'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
                        'max_teams' => 8,
                        'entry_fee' => 70000,
                        'prize' => 'Equipamiento deportivo',
                        'notes' => null,
                        'teams' => [
                            ['name' => 'Drive Club', 'matches' => 5, 'wins' => 4, 'draws' => 0, 'losses' => 1, 'goal_diff' => 9, 'points' => 12, 'position' => 1],
                            ['name' => 'Set Point', 'matches' => 5, 'wins' => 3, 'draws' => 0, 'losses' => 2, 'goal_diff' => 3, 'points' => 9, 'position' => 2],
                        ],
                    ],
                    [
                        'sport_slug' => null,
                        'name' => 'Copa Mixta Barrio Norte',
                        'category' => 'Libre',
                        'format' => 'Zona unica',
                        'start_date' => now()->addDays(14)->toDateString(),
                        'end_date' => now()->addDays(40)->toDateString(),
                        'status' => ComplexTournament::STATUS_CUPOS_LIMITADOS,
                        'max_teams' => 10,
                        'entry_fee' => 85000,
                        'prize' => 'Voucher + trofeo',
                        'notes' => 'Abierto a varios deportes del complejo.',
                        'teams' => [
                            ['name' => 'Mixto Norte', 'matches' => 3, 'wins' => 2, 'draws' => 0, 'losses' => 1, 'goal_diff' => 4, 'points' => 6, 'position' => 1],
                        ],
                    ],
                ],
                'team_board_posts' => [
                    [
                        'sport_slug' => 'tenis',
                        'kind' => ComplexTeamBoardPost::KIND_FALTA_EQUIPO,
                        'title' => 'Liga de tenis: falta 1 equipo',
                        'level' => 'Amateur',
                        'needed_players' => null,
                        'play_day' => 'Sabado',
                        'play_time' => '17:00',
                        'contact' => '381-555-0111',
                        'notes' => 'Se juega ida y vuelta.',
                    ],
                    [
                        'sport_slug' => null,
                        'kind' => ComplexTeamBoardPost::KIND_BUSCO_RIVAL,
                        'title' => 'Equipo mixto busca rival',
                        'level' => 'Libre',
                        'needed_players' => null,
                        'play_day' => 'Viernes',
                        'play_time' => '22:00',
                        'contact' => '381-555-0112',
                        'notes' => null,
                    ],
                ],
            ],
            [
                'slug' => 'complejo-sur-park',
                'name' => 'Complejo Sur Park',
                'address_line' => 'Av. Roca 2100',
                'latitude' => -26.8452000,
                'longitude' => -65.2165000,
                'description' => 'Canchas amplias con iluminacion nocturna para partidos de Futbol 11.',
                'phone_contact' => '3815000022',
                'courts' => [
                    [
                        'name' => 'Cancha Futbol 11 - Sur',
                        'sport_slug' => 'futbol',
                        'surface_type' => 'cesped_sintetico',
                        'players_capacity' => 22,
                        'slot_duration_minutes' => 60,
                        'base_price' => 70000,
                    ],
                    [
                        'name' => 'Cancha Futbol 5 - Express',
                        'sport_slug' => 'futbol',
                        'surface_type' => 'cesped_sintetico',
                        'players_capacity' => 10,
                        'slot_duration_minutes' => 60,
                        'base_price' => 29000,
                    ],
                    [
                        'name' => 'Cancha Basquet - Central',
                        'sport_slug' => 'basquet',
                        'surface_type' => 'cemento',
                        'players_capacity' => 10,
                        'slot_duration_minutes' => 60,
                        'base_price' => 31000,
                    ],
                    [
                        'name' => 'Cancha Voley - Arena',
                        'sport_slug' => 'voley',
                        'surface_type' => 'otro',
                        'players_capacity' => 12,
                        'slot_duration_minutes' => 60,
                        'base_price' => 27000,
                    ],
                ],
                'tournaments' => [
                    [
                        'sport_slug' => 'basquet',
                        'name' => 'Liga Basquet Sur',
                        'category' => 'Primera',
                        'format' => 'Todos contra todos',
                        'start_date' => now()->addDays(5)->toDateString(),
                        'end_date' => now()->addDays(32)->toDateString(),
                        'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
                        'max_teams' => 12,
                        'entry_fee' => 100000,
                        'prize' => 'Premio al campeon y MVP',
                        'notes' => null,
                        'teams' => [
                            ['name' => 'Triple Team', 'matches' => 6, 'wins' => 5, 'draws' => 0, 'losses' => 1, 'goal_diff' => 18, 'points' => 15, 'position' => 1],
                            ['name' => 'Aro Sur', 'matches' => 6, 'wins' => 4, 'draws' => 0, 'losses' => 2, 'goal_diff' => 9, 'points' => 12, 'position' => 2],
                        ],
                    ],
                    [
                        'sport_slug' => 'voley',
                        'name' => 'Copa Voley Weekend',
                        'category' => 'Mixto',
                        'format' => 'Eliminacion directa',
                        'start_date' => now()->addDays(11)->toDateString(),
                        'end_date' => now()->addDays(13)->toDateString(),
                        'status' => ComplexTournament::STATUS_CUPOS_LIMITADOS,
                        'max_teams' => 10,
                        'entry_fee' => 65000,
                        'prize' => 'Set de camisetas',
                        'notes' => null,
                        'teams' => [
                            ['name' => 'Bloqueo Total', 'matches' => 4, 'wins' => 3, 'draws' => 0, 'losses' => 1, 'goal_diff' => 7, 'points' => 9, 'position' => 1],
                        ],
                    ],
                ],
                'team_board_posts' => [
                    [
                        'sport_slug' => 'basquet',
                        'kind' => ComplexTeamBoardPost::KIND_FALTA_JUGADOR,
                        'title' => 'Equipo de basquet busca base titular',
                        'level' => 'Competitivo',
                        'needed_players' => 1,
                        'play_day' => 'Miercoles',
                        'play_time' => '22:00',
                        'contact' => '381-555-0121',
                        'notes' => null,
                    ],
                    [
                        'sport_slug' => 'voley',
                        'kind' => ComplexTeamBoardPost::KIND_BUSCO_RIVAL,
                        'title' => 'Equipo de voley busca amistoso',
                        'level' => 'Intermedio',
                        'needed_players' => null,
                        'play_day' => 'Domingo',
                        'play_time' => '19:00',
                        'contact' => '381-555-0122',
                        'notes' => 'Preferencia por equipos mixtos.',
                    ],
                ],
            ],
        ];

        foreach ($complexes as $complexData) {
            $complex = Complex::query()->updateOrCreate(
                ['slug' => $complexData['slug']],
                [
                    'city_id' => $city->id,
                    'name' => $complexData['name'],
                    'address_line' => $complexData['address_line'],
                    'latitude' => $complexData['latitude'],
                    'longitude' => $complexData['longitude'],
                    'description' => $complexData['description'],
                    'phone_contact' => $complexData['phone_contact'],
                    'status' => Complex::STATUS_ACTIVO,
                    'booking_enabled' => true,
                ],
            );

            foreach ($complexData['courts'] as $courtData) {
                $sport = $sportsBySlug[$courtData['sport_slug']] ?? null;

                if (!$sport) {
                    continue;
                }

                Court::query()->updateOrCreate(
                    [
                        'complex_id' => $complex->id,
                        'name' => $courtData['name'],
                    ],
                    [
                        'sport_id' => $sport->id,
                        'surface_type' => $courtData['surface_type'],
                        'players_capacity' => $courtData['players_capacity'],
                        'slot_duration_minutes' => $courtData['slot_duration_minutes'],
                        'base_price' => $courtData['base_price'],
                        'status' => Court::STATUS_ACTIVA,
                    ],
                );
            }

            $this->seedCommunityData(
                $complex,
                $complexData,
                $sportsBySlug,
                $adminCancha,
            );

            $this->seedOpeningHours($complex);
            $this->seedPolicy($complex);
            $this->assignAdminCancha($complex, $adminCancha);
        }
    }

    /**
     * @param  array<string, mixed>  $complexData
     * @param  array<string, Sport>  $sportsBySlug
     */
    private function seedCommunityData(
        Complex $complex,
        array $complexData,
        array $sportsBySlug,
        ?User $adminCancha,
    ): void {
        foreach ($complexData['tournaments'] ?? [] as $tournamentData) {
            $tournamentSport = $tournamentData['sport_slug']
                ? ($sportsBySlug[$tournamentData['sport_slug']] ?? null)
                : null;

            $tournament = ComplexTournament::query()->updateOrCreate(
                [
                    'complex_id' => $complex->id,
                    'name' => $tournamentData['name'],
                ],
                [
                    'sport_id' => $tournamentSport?->id,
                    'category' => $tournamentData['category'] ?? null,
                    'format' => $tournamentData['format'] ?? null,
                    'start_date' => $tournamentData['start_date'],
                    'end_date' => $tournamentData['end_date'],
                    'status' => $tournamentData['status'] ?? ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
                    'max_teams' => $tournamentData['max_teams'] ?? 16,
                    'entry_fee' => $tournamentData['entry_fee'] ?? 0,
                    'prize' => $tournamentData['prize'] ?? null,
                    'notes' => $tournamentData['notes'] ?? null,
                    'created_by_user_id' => $adminCancha?->id,
                ],
            );

            foreach ($tournamentData['teams'] ?? [] as $index => $teamData) {
                TournamentTeam::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'name' => $teamData['name'],
                    ],
                    [
                        'matches' => $teamData['matches'] ?? 0,
                        'wins' => $teamData['wins'] ?? 0,
                        'draws' => $teamData['draws'] ?? 0,
                        'losses' => $teamData['losses'] ?? 0,
                        'goal_diff' => $teamData['goal_diff'] ?? 0,
                        'points' => $teamData['points'] ?? 0,
                        'position' => $teamData['position'] ?? ($index + 1),
                    ],
                );
            }
        }

        foreach ($complexData['team_board_posts'] ?? [] as $postData) {
            $postSport = $postData['sport_slug']
                ? ($sportsBySlug[$postData['sport_slug']] ?? null)
                : null;

            ComplexTeamBoardPost::query()->updateOrCreate(
                [
                    'complex_id' => $complex->id,
                    'title' => $postData['title'],
                ],
                [
                    'sport_id' => $postSport?->id,
                    'kind' => $postData['kind'],
                    'level' => $postData['level'] ?? null,
                    'needed_players' => $postData['needed_players'] ?? null,
                    'play_day' => $postData['play_day'] ?? null,
                    'play_time' => $postData['play_time'] ?? null,
                    'contact' => $postData['contact'],
                    'notes' => $postData['notes'] ?? null,
                    'status' => ComplexTeamBoardPost::STATUS_ACTIVO,
                    'created_by_user_id' => $adminCancha?->id,
                ],
            );
        }
    }

    private function seedOpeningHours(Complex $complex): void
    {
        foreach ([1, 2, 3, 4, 5, 6, 7] as $dayOfWeek) {
            ComplexOpeningHour::query()->updateOrCreate(
                [
                    'complex_id' => $complex->id,
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '23:00',
                ],
            );
        }
    }

    private function seedPolicy(Complex $complex): void
    {
        ComplexPolicy::query()->updateOrCreate(
            ['complex_id' => $complex->id],
            [
                'deposit_percent' => 30,
                'cancel_limit_minutes' => 180,
                'refund_percent_before_limit' => 100,
                'no_show_penalty_percent' => 100,
            ],
        );
    }

    private function assignAdminCancha(Complex $complex, ?User $adminCancha): void
    {
        if (!$adminCancha) {
            return;
        }

        ComplexUserAssignment::query()->updateOrCreate(
            [
                'complex_id' => $complex->id,
                'user_id' => $adminCancha->id,
            ],
            [
                'assignment_type' => ComplexUserAssignment::TYPE_OWNER,
                'is_primary' => true,
            ],
        );
    }
}
