<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\ComplexTeamBoardPost;
use App\Models\ComplexTournament;
use App\Models\Court;
use App\Models\Province;
use App\Models\Sport;
use App\Models\TournamentTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ComplexProfileTest extends TestCase
{
  use RefreshDatabase;

  public function test_guest_can_open_complex_profile_and_see_availability(): void
  {
    $targetDate = now()->addDays(1);

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
      'name' => 'Complejo Test Perfil',
      'slug' => 'complejo-test-perfil',
      'address_line' => 'Av. Test 123',
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

    Court::create([
      'complex_id' => $complex->id,
      'sport_id' => $sport->id,
      'name' => 'Cancha Perfil 5',
      'surface_type' => 'cesped_sintetico',
      'players_capacity' => 10,
      'slot_duration_minutes' => 60,
      'base_price' => 30000,
      'status' => Court::STATUS_ACTIVA,
    ]);

    $tournament = ComplexTournament::create([
      'complex_id' => $complex->id,
      'sport_id' => $sport->id,
      'name' => 'Copa Test Perfil',
      'category' => 'Libre',
      'format' => 'Grupos',
      'start_date' => $targetDate->toDateString(),
      'end_date' => $targetDate->copy()->addDays(7)->toDateString(),
      'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
      'max_teams' => 8,
      'entry_fee' => 50000,
      'prize' => 'Trofeo',
    ]);

    TournamentTeam::create([
      'tournament_id' => $tournament->id,
      'name' => 'Equipo Test A',
      'matches' => 3,
      'wins' => 2,
      'draws' => 1,
      'losses' => 0,
      'goal_diff' => 4,
      'points' => 7,
      'position' => 1,
    ]);

    ComplexTeamBoardPost::create([
      'complex_id' => $complex->id,
      'sport_id' => $sport->id,
      'kind' => ComplexTeamBoardPost::KIND_FALTA_JUGADOR,
      'title' => 'Equipo Test busca 1 jugador',
      'level' => 'Intermedio',
      'needed_players' => 1,
      'play_day' => 'Viernes',
      'play_time' => '21:00',
      'contact' => '381-555-0999',
      'status' => ComplexTeamBoardPost::STATUS_ACTIVO,
    ]);

    $response = $this->get('/complejos/' . $complex->slug . '?' . http_build_query([
      'sport_id' => $sport->id,
      'date' => $targetDate->toDateString(),
      'start_time' => '10:00',
    ]));

    $response->assertOk();
    $response->assertInertia(
      fn(Assert $page) => $page
        ->component('Complex/Show')
        ->where('complex.slug', $complex->slug)
        ->where('summary.total_courts', 1)
        ->has('courts', 1)
        ->has('courts.0.available_slots')
        ->has('community.tournaments', 1)
        ->where('community.tournaments.0.name', 'Copa Test Perfil')
        ->has('community.team_board', 1)
        ->where('community.team_board.0.title', 'Equipo Test busca 1 jugador')
        ->has('community.ranking', 1)
        ->where('community.ranking.0.team', 'Equipo Test A')
        ->has('community.padel_category_rankings')
        ->has('community.padel_individual_ranking')
    );
  }

  public function test_it_returns_padel_rankings_by_category_and_individual(): void
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

    $padel = Sport::create([
      'name' => 'Padel',
      'slug' => 'padel',
      'is_active' => true,
    ]);

    $complex = Complex::create([
      'city_id' => $city->id,
      'name' => 'Complejo Test Padel',
      'slug' => 'complejo-test-padel',
      'address_line' => 'Av. Test 456',
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

    Court::create([
      'complex_id' => $complex->id,
      'sport_id' => $padel->id,
      'name' => 'Cancha Padel Test',
      'surface_type' => 'cemento',
      'players_capacity' => 4,
      'slot_duration_minutes' => 90,
      'base_price' => 22000,
      'status' => Court::STATUS_ACTIVA,
    ]);

    $categoryTournament = ComplexTournament::create([
      'complex_id' => $complex->id,
      'sport_id' => $padel->id,
      'name' => 'Padel 6ta Test',
      'category' => '6ta',
      'format' => 'Round robin',
      'start_date' => $targetDate->toDateString(),
      'end_date' => $targetDate->copy()->addDays(15)->toDateString(),
      'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
      'max_teams' => 12,
      'entry_fee' => 70000,
      'prize' => 'Premio test',
    ]);

    TournamentTeam::create([
      'tournament_id' => $categoryTournament->id,
      'name' => 'Pareja Test A',
      'matches' => 4,
      'wins' => 3,
      'draws' => 0,
      'losses' => 1,
      'goal_diff' => 5,
      'points' => 9,
      'position' => 1,
    ]);

    $individualTournament = ComplexTournament::create([
      'complex_id' => $complex->id,
      'sport_id' => $padel->id,
      'name' => 'Ranking Individual Padel Test',
      'category' => 'Individual',
      'format' => 'Individual por puntos',
      'start_date' => $targetDate->toDateString(),
      'end_date' => $targetDate->copy()->addDays(30)->toDateString(),
      'status' => ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
      'max_teams' => 32,
      'entry_fee' => 30000,
      'prize' => 'Premio individual',
    ]);

    TournamentTeam::create([
      'tournament_id' => $individualTournament->id,
      'name' => 'Juan Perez',
      'matches' => 6,
      'wins' => 5,
      'draws' => 0,
      'losses' => 1,
      'goal_diff' => 8,
      'points' => 15,
      'position' => 1,
    ]);

    TournamentTeam::create([
      'tournament_id' => $individualTournament->id,
      'name' => 'Franco Diaz',
      'matches' => 6,
      'wins' => 4,
      'draws' => 0,
      'losses' => 2,
      'goal_diff' => 4,
      'points' => 12,
      'position' => 2,
    ]);

    $response = $this->get('/complejos/' . $complex->slug . '?' . http_build_query([
      'sport_id' => $padel->id,
      'date' => $targetDate->toDateString(),
      'start_time' => '10:00',
    ]));

    $response->assertOk();
    $response->assertInertia(
      fn(Assert $page) => $page
        ->component('Complex/Show')
        ->has('community.padel_category_rankings', 1)
        ->where('community.padel_category_rankings.0.category', '6ta')
        ->has('community.padel_individual_ranking', 2)
        ->where('community.padel_individual_ranking.0.team', 'Juan Perez')
    );
  }
}
