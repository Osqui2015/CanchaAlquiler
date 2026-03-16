<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\ComplexTeamBoardPost;
use App\Models\ComplexTournament;
use App\Models\Sport;
use App\Models\TournamentTeam;
use App\Services\AvailabilitySearchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ComplexProfileController extends Controller
{
  public function show(Request $request, string $slug, AvailabilitySearchService $availabilitySearchService): Response
  {
    $complex = Complex::query()
      ->where('slug', $slug)
      ->where('status', Complex::STATUS_ACTIVO)
      ->where('booking_enabled', true)
      ->firstOrFail();

    $validated = $request->validate([
      'sport_id' => ['nullable', 'integer', 'exists:sports,id'],
      'date' => ['nullable', 'date_format:Y-m-d'],
      'start_time' => ['nullable', 'date_format:H:i'],
      'end_time' => ['nullable', 'date_format:H:i'],
    ]);

    $filters = [
      'sport_id' => $validated['sport_id'] ?? null,
      'date' => $validated['date'] ?? now()->toDateString(),
      'start_time' => $validated['start_time'] ?? '10:00',
      'end_time' => $validated['end_time'] ?? null,
    ];

    if ($filters['end_time']) {
      $startTime = Carbon::createFromFormat('H:i', $filters['start_time']);
      $endTime = Carbon::createFromFormat('H:i', $filters['end_time']);

      if ($endTime->lessThanOrEqualTo($startTime)) {
        throw ValidationException::withMessages([
          'end_time' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);
      }
    }

    $profile = $availabilitySearchService->searchForComplex($complex, $filters);
    $community = $this->buildCommunityPayload($complex, $filters);

    return Inertia::render('Complex/Show', [
      'appName' => config('app.name'),
      'complex' => $profile['complex'],
      'summary' => $profile['summary'],
      'filters' => $filters,
      'courts' => $profile['courts'],
      'community' => $community,
    ]);
  }

  /**
   * @param  array<string, mixed>  $filters
   * @return array<string, mixed>
   */
  private function buildCommunityPayload(Complex $complex, array $filters): array
  {
    $sportId = isset($filters['sport_id']) && $filters['sport_id'] !== null
      ? (int) $filters['sport_id']
      : null;
    $selectedSportSlug = $sportId
      ? Sport::query()->whereKey($sportId)->value('slug')
      : null;

    $tournamentsQuery = ComplexTournament::query()
      ->with(['sport:id,name,slug', 'teams'])
      ->where('complex_id', $complex->id)
      ->orderByDesc('start_date')
      ->orderByDesc('id');

    if ($sportId) {
      $tournamentsQuery->where(function ($query) use ($sportId): void {
        $query->where('sport_id', $sportId)
          ->orWhereNull('sport_id');
      });
    }

    $tournaments = $tournamentsQuery->get();

    $tournamentsPayload = $tournaments->map(function (ComplexTournament $tournament): array {
      return [
        'id' => $tournament->id,
        'name' => $tournament->name,
        'sport' => $tournament->sport?->name,
        'category' => $tournament->category,
        'format' => $tournament->format,
        'start_date' => $tournament->start_date?->toDateString(),
        'end_date' => $tournament->end_date?->toDateString(),
        'status' => $tournament->status,
        'teams_registered' => $tournament->teams->count(),
        'max_teams' => $tournament->max_teams,
        'entry_fee' => (float) $tournament->entry_fee,
        'prize' => $tournament->prize,
        'notes' => $tournament->notes,
      ];
    })->values()->all();

    $teamBoardQuery = ComplexTeamBoardPost::query()
      ->with(['sport:id,name,slug'])
      ->where('complex_id', $complex->id)
      ->where('status', ComplexTeamBoardPost::STATUS_ACTIVO)
      ->orderByDesc('created_at')
      ->orderByDesc('id');

    if ($sportId) {
      $teamBoardQuery->where(function ($query) use ($sportId): void {
        $query->where('sport_id', $sportId)
          ->orWhereNull('sport_id');
      });
    }

    $teamBoardPayload = $teamBoardQuery->get()
      ->map(function (ComplexTeamBoardPost $post): array {
        return [
          'id' => $post->id,
          'kind' => $post->kind,
          'title' => $post->title,
          'sport' => $post->sport?->name ?? 'Todos los deportes',
          'level' => $post->level,
          'needed_players' => $post->needed_players ?? 0,
          'play_day' => $post->play_day,
          'play_time' => $post->play_time,
          'contact' => $post->contact,
          'notes' => $post->notes,
        ];
      })
      ->values()
      ->all();

    $rankingTournament = $this->resolveRankingTournament($tournaments, $sportId);
    $rankingPayload = [];

    if ($rankingTournament) {
      $rankingPayload = $this->buildRankingRows($rankingTournament->teams);
    }

    $padelCategoryRankings = [];
    $padelIndividualTournament = null;
    $padelIndividualRanking = [];

    if ($selectedSportSlug === 'padel') {
      $padelTournaments = $tournaments->filter(function (ComplexTournament $tournament): bool {
        return $tournament->sport?->slug === 'padel' && $tournament->teams->isNotEmpty();
      });

      foreach ($padelTournaments as $tournament) {
        $haystack = strtolower(
          ($tournament->name ?? '') . ' ' .
            ($tournament->category ?? '') . ' ' .
            ($tournament->format ?? '')
        );
        $isIndividual = str_contains($haystack, 'individual');

        if ($isIndividual && !$padelIndividualTournament) {
          $padelIndividualTournament = [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'category' => $tournament->category,
          ];
          $padelIndividualRanking = $this->buildRankingRows($tournament->teams);
          continue;
        }

        $padelCategoryRankings[] = [
          'tournament_id' => $tournament->id,
          'tournament_name' => $tournament->name,
          'category' => $tournament->category ?: 'General',
          'ranking' => $this->buildRankingRows($tournament->teams),
        ];
      }
    }

    return [
      'tournaments' => $tournamentsPayload,
      'team_board' => $teamBoardPayload,
      'ranking_tournament' => $rankingTournament ? [
        'id' => $rankingTournament->id,
        'name' => $rankingTournament->name,
        'sport' => $rankingTournament->sport?->name,
      ] : null,
      'ranking' => $rankingPayload,
      'padel_category_rankings' => $padelCategoryRankings,
      'padel_individual_tournament' => $padelIndividualTournament,
      'padel_individual_ranking' => $padelIndividualRanking,
    ];
  }

  /**
   * @param  Collection<int, TournamentTeam>  $teams
   * @return array<int, array<string, int|string>>
   */
  private function buildRankingRows(Collection $teams): array
  {
    return $teams
      ->sort(function (TournamentTeam $a, TournamentTeam $b): int {
        if ($a->points !== $b->points) {
          return $b->points <=> $a->points;
        }

        if ($a->goal_diff !== $b->goal_diff) {
          return $b->goal_diff <=> $a->goal_diff;
        }

        if ($a->wins !== $b->wins) {
          return $b->wins <=> $a->wins;
        }

        return strcmp($a->name, $b->name);
      })
      ->values()
      ->map(function (TournamentTeam $team, int $index): array {
        return [
          'position' => $team->position ?: ($index + 1),
          'team' => $team->name,
          'matches' => $team->matches,
          'wins' => $team->wins,
          'draws' => $team->draws,
          'losses' => $team->losses,
          'goal_diff' => $team->goal_diff,
          'points' => $team->points,
        ];
      })
      ->all();
  }

  /**
   * @param  Collection<int, ComplexTournament>  $tournaments
   */
  private function resolveRankingTournament(Collection $tournaments, ?int $sportId): ?ComplexTournament
  {
    $withTeams = $tournaments->filter(fn(ComplexTournament $tournament): bool => $tournament->teams->isNotEmpty());

    if ($withTeams->isEmpty()) {
      return null;
    }

    if ($sportId) {
      $sportSpecific = $withTeams->first(function (ComplexTournament $tournament) use ($sportId): bool {
        return (int) $tournament->sport_id === $sportId;
      });

      if ($sportSpecific) {
        return $sportSpecific;
      }
    }

    return $withTeams->first();
  }
}
