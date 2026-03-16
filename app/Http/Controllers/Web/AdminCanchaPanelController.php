<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\ComplexPolicy;
use App\Models\ComplexTeamBoardPost;
use App\Models\ComplexTournament;
use App\Models\ComplexUserAssignment;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\ServiceCatalog;
use App\Models\Sport;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminCanchaPanelController extends Controller
{
  public function index(Request $request): Response
  {
    $user = $request->user();
    $selectedDate = $request->query('date', now()->toDateString());

    $complexes = Complex::query()
      ->with([
        'city.province',
        'services',
        'courts.sport',
        'openingHours',
        'policies',
        'tournaments.sport',
        'tournaments.teams',
        'teamBoardPosts.sport',
      ])
      ->whereHas('assignments', function ($query) use ($user): void {
        $query->where('user_id', $user->id);
      })
      ->orderBy('name')
      ->get();

    $complexesPayload = $complexes->map(function (Complex $complex) use ($selectedDate): array {
      $monthStart = now()->startOfMonth();
      $monthEnd = now()->endOfMonth();

      $confirmedQuery = Reservation::query()
        ->where('complex_id', $complex->id)
        ->where('status', Reservation::STATUS_CONFIRMADA)
        ->whereBetween('start_at', [$monthStart, $monthEnd]);

      $dailyReservations = Reservation::query()
        ->with(['client:id,name,email,phone', 'court:id,name,complex_id'])
        ->where('complex_id', $complex->id)
        ->whereDate('start_at', $selectedDate)
        ->orderBy('start_at')
        ->get();

      return [
        'id' => $complex->id,
        'name' => $complex->name,
        'slug' => $complex->slug,
        'address_line' => $complex->address_line,
        'description' => $complex->description,
        'phone_contact' => $complex->phone_contact,
        'status' => $complex->status,
        'booking_enabled' => $complex->booking_enabled,
        'city' => $complex->city,
        'services' => $complex->services,
        'courts' => $complex->courts,
        'opening_hours' => $complex->openingHours,
        'policy' => $complex->policies,
        'stats' => [
          'income_month' => (float) (clone $confirmedQuery)->sum('total_amount'),
          'reservations_confirmed_month' => (clone $confirmedQuery)->count(),
        ],
        'daily_reservations' => $dailyReservations,
        'tournaments' => $complex->tournaments
          ->sortByDesc('start_date')
          ->map(function (ComplexTournament $tournament): array {
            return [
              'id' => $tournament->id,
              'sport_id' => $tournament->sport_id,
              'sport' => $tournament->sport ? [
                'id' => $tournament->sport->id,
                'name' => $tournament->sport->name,
              ] : null,
              'name' => $tournament->name,
              'category' => $tournament->category,
              'format' => $tournament->format,
              'start_date' => $tournament->start_date?->toDateString(),
              'end_date' => $tournament->end_date?->toDateString(),
              'status' => $tournament->status,
              'max_teams' => $tournament->max_teams,
              'entry_fee' => (float) $tournament->entry_fee,
              'prize' => $tournament->prize,
              'notes' => $tournament->notes,
              'teams' => $tournament->teams
                ->sortBy('position')
                ->map(function (TournamentTeam $team): array {
                  return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'matches' => $team->matches,
                    'wins' => $team->wins,
                    'draws' => $team->draws,
                    'losses' => $team->losses,
                    'goal_diff' => $team->goal_diff,
                    'points' => $team->points,
                    'position' => $team->position,
                  ];
                })
                ->values()
                ->all(),
            ];
          })
          ->values()
          ->all(),
        'team_board_posts' => $complex->teamBoardPosts
          ->sortByDesc('created_at')
          ->map(function (ComplexTeamBoardPost $post): array {
            return [
              'id' => $post->id,
              'sport_id' => $post->sport_id,
              'sport' => $post->sport ? [
                'id' => $post->sport->id,
                'name' => $post->sport->name,
              ] : null,
              'kind' => $post->kind,
              'title' => $post->title,
              'level' => $post->level,
              'needed_players' => $post->needed_players,
              'play_day' => $post->play_day,
              'play_time' => $post->play_time,
              'contact' => $post->contact,
              'notes' => $post->notes,
              'status' => $post->status,
            ];
          })
          ->values()
          ->all(),
      ];
    })->values();

    return Inertia::render('AdminCancha/Dashboard', [
      'selectedDate' => $selectedDate,
      'catalogs' => [
        'sports' => Sport::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
        'cities' => City::query()->with('province:id,name')->orderBy('name')->get(['id', 'name', 'province_id']),
        'services' => ServiceCatalog::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'icon']),
      ],
      'complexes' => $complexesPayload,
    ]);
  }

  public function storeComplex(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'city_id' => ['required', 'integer', 'exists:cities,id'],
      'name' => ['required', 'string', 'max:150'],
      'address_line' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'phone_contact' => ['nullable', 'string', 'max:40'],
      'latitude' => ['nullable', 'numeric', 'between:-90,90'],
      'longitude' => ['nullable', 'numeric', 'between:-180,180'],
      'service_ids' => ['nullable', 'array'],
      'service_ids.*' => ['integer', 'exists:services_catalog,id'],
    ]);

    DB::transaction(function () use ($request, $validated): void {
      $complex = Complex::create([
        'city_id' => $validated['city_id'],
        'name' => $validated['name'],
        'slug' => $this->buildUniqueSlug($validated['name']),
        'address_line' => $validated['address_line'],
        'description' => $validated['description'] ?? null,
        'phone_contact' => $validated['phone_contact'] ?? null,
        'latitude' => $validated['latitude'] ?? null,
        'longitude' => $validated['longitude'] ?? null,
        'status' => Complex::STATUS_ACTIVO,
        'booking_enabled' => true,
      ]);

      $complex->services()->sync($validated['service_ids'] ?? []);

      ComplexUserAssignment::updateOrCreate(
        [
          'complex_id' => $complex->id,
          'user_id' => $request->user()->id,
        ],
        [
          'assignment_type' => ComplexUserAssignment::TYPE_OWNER,
          'is_primary' => true,
        ],
      );
    });

    return redirect()->route('panel.admincancha')
      ->with('success', 'Complejo creado correctamente.');
  }

  public function storeCourt(Request $request, Complex $complex): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'sport_id' => ['required', 'integer', 'exists:sports,id'],
      'name' => ['required', 'string', 'max:150'],
      'surface_type' => ['required', 'in:cesped_sintetico,cemento,polvo_ladrillo,otro'],
      'players_capacity' => ['required', 'integer', 'min:2', 'max:50'],
      'slot_duration_minutes' => ['required', 'integer', 'min:30', 'max:240'],
      'base_price' => ['required', 'numeric', 'min:0'],
    ]);

    $complex->courts()->create([
      ...$validated,
      'status' => Court::STATUS_ACTIVA,
    ]);

    return redirect()->route('panel.admincancha')
      ->with('success', 'Cancha creada correctamente.');
  }

  public function updateCourt(Request $request, Court $court): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $court->complex);

    $validated = $request->validate([
      'sport_id' => ['sometimes', 'integer', 'exists:sports,id'],
      'name' => ['sometimes', 'string', 'max:150'],
      'surface_type' => ['sometimes', 'in:cesped_sintetico,cemento,polvo_ladrillo,otro'],
      'players_capacity' => ['sometimes', 'integer', 'min:2', 'max:50'],
      'slot_duration_minutes' => ['sometimes', 'integer', 'min:30', 'max:240'],
      'base_price' => ['sometimes', 'numeric', 'min:0'],
      'status' => ['sometimes', 'in:activa,inactiva,mantenimiento'],
    ]);

    $court->update($validated);

    return redirect()->route('panel.admincancha')
      ->with('success', 'Cancha actualizada correctamente.');
  }

  public function upsertOpeningHours(Request $request, Complex $complex): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'days' => ['required', 'array', 'min:1'],
      'days.*.day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
      'days.*.is_open' => ['required', 'boolean'],
      'days.*.open_time' => ['nullable', 'date_format:H:i'],
      'days.*.close_time' => ['nullable', 'date_format:H:i'],
    ]);

    DB::transaction(function () use ($complex, $validated): void {
      foreach ($validated['days'] as $day) {
        if ($day['is_open'] && (!$day['open_time'] || !$day['close_time'] || $day['close_time'] <= $day['open_time'])) {
          throw ValidationException::withMessages([
            'days' => 'Cada dia abierto debe tener horarios validos.',
          ]);
        }

        ComplexOpeningHour::updateOrCreate(
          [
            'complex_id' => $complex->id,
            'day_of_week' => $day['day_of_week'],
          ],
          [
            'is_open' => $day['is_open'],
            'open_time' => $day['open_time'],
            'close_time' => $day['close_time'],
          ],
        );
      }
    });

    return redirect()->route('panel.admincancha')
      ->with('success', 'Horarios actualizados correctamente.');
  }

  public function upsertPolicy(Request $request, Complex $complex): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'deposit_percent' => ['required', 'integer', 'min:0', 'max:100'],
      'cancel_limit_minutes' => ['required', 'integer', 'min:0'],
      'refund_percent_before_limit' => ['required', 'integer', 'min:0', 'max:100'],
      'no_show_penalty_percent' => ['required', 'integer', 'min:0', 'max:100'],
    ]);

    ComplexPolicy::updateOrCreate(
      ['complex_id' => $complex->id],
      $validated,
    );

    return redirect()->route('panel.admincancha')
      ->with('success', 'Politica de reservas actualizada.');
  }

  public function storeTournament(Request $request, Complex $complex): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'sport_id' => ['nullable', 'integer', 'exists:sports,id'],
      'name' => ['required', 'string', 'max:150'],
      'category' => ['nullable', 'string', 'max:80'],
      'format' => ['nullable', 'string', 'max:120'],
      'start_date' => ['required', 'date'],
      'end_date' => ['required', 'date', 'after_or_equal:start_date'],
      'status' => ['nullable', 'in:inscripciones_abiertas,cupos_limitados,cerrado'],
      'max_teams' => ['required', 'integer', 'min:2', 'max:64'],
      'entry_fee' => ['required', 'numeric', 'min:0'],
      'prize' => ['nullable', 'string', 'max:255'],
      'notes' => ['nullable', 'string'],
    ]);

    $complex->tournaments()->create([
      ...$validated,
      'status' => $validated['status'] ?? ComplexTournament::STATUS_INSCRIPCIONES_ABIERTAS,
      'created_by_user_id' => $request->user()->id,
    ]);

    return redirect()->route('panel.admincancha')
      ->with('success', 'Torneo creado correctamente.');
  }

  public function storeTournamentTeam(Request $request, Complex $complex, ComplexTournament $tournament): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    if ((int) $tournament->complex_id !== (int) $complex->id) {
      abort(404, 'El torneo no pertenece al complejo.');
    }

    $validated = $request->validate([
      'name' => ['required', 'string', 'max:120'],
      'matches' => ['required', 'integer', 'min:0', 'max:100'],
      'wins' => ['required', 'integer', 'min:0', 'max:100'],
      'draws' => ['required', 'integer', 'min:0', 'max:100'],
      'losses' => ['required', 'integer', 'min:0', 'max:100'],
      'goal_diff' => ['required', 'integer', 'min:-500', 'max:500'],
      'points' => ['nullable', 'integer', 'min:0', 'max:300'],
      'position' => ['nullable', 'integer', 'min:1', 'max:64'],
    ]);

    if (($validated['wins'] + $validated['draws'] + $validated['losses']) > $validated['matches']) {
      throw ValidationException::withMessages([
        'matches' => 'PG + PE + PP no puede superar los partidos jugados.',
      ]);
    }

    $computedPoints = $validated['points'] ?? ($validated['wins'] * 3 + $validated['draws']);
    $position = $validated['position'] ?? (($tournament->teams()->max('position') ?? 0) + 1);

    TournamentTeam::query()->updateOrCreate(
      [
        'tournament_id' => $tournament->id,
        'name' => $validated['name'],
      ],
      [
        'matches' => $validated['matches'],
        'wins' => $validated['wins'],
        'draws' => $validated['draws'],
        'losses' => $validated['losses'],
        'goal_diff' => $validated['goal_diff'],
        'points' => $computedPoints,
        'position' => $position,
      ],
    );

    return redirect()->route('panel.admincancha')
      ->with('success', 'Equipo del torneo guardado correctamente.');
  }

  public function storeTeamBoardPost(Request $request, Complex $complex): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'sport_id' => ['nullable', 'integer', 'exists:sports,id'],
      'kind' => ['required', 'in:falta_jugador,busco_rival,falta_equipo'],
      'title' => ['required', 'string', 'max:160'],
      'level' => ['nullable', 'string', 'max:80'],
      'needed_players' => ['nullable', 'integer', 'min:1', 'max:30'],
      'play_day' => ['nullable', 'string', 'max:40'],
      'play_time' => ['nullable', 'date_format:H:i'],
      'contact' => ['required', 'string', 'max:120'],
      'notes' => ['nullable', 'string'],
      'status' => ['nullable', 'in:activo,cerrado'],
    ]);

    if ($validated['kind'] === ComplexTeamBoardPost::KIND_FALTA_JUGADOR && empty($validated['needed_players'])) {
      throw ValidationException::withMessages([
        'needed_players' => 'Debes indicar cuantos jugadores faltan.',
      ]);
    }

    if ($validated['kind'] !== ComplexTeamBoardPost::KIND_FALTA_JUGADOR) {
      $validated['needed_players'] = null;
    }

    $complex->teamBoardPosts()->create([
      ...$validated,
      'status' => $validated['status'] ?? ComplexTeamBoardPost::STATUS_ACTIVO,
      'created_by_user_id' => $request->user()->id,
    ]);

    return redirect()->route('panel.admincancha')
      ->with('success', 'Publicacion comunitaria creada correctamente.');
  }

  private function authorizeComplex(User $user, Complex $complex): void
  {
    $hasAssignment = $complex->assignments()
      ->where('user_id', $user->id)
      ->exists();

    if (!$hasAssignment) {
      abort(403, 'No tienes permisos sobre este complejo.');
    }
  }

  private function buildUniqueSlug(string $name, ?int $ignoreComplexId = null): string
  {
    $baseSlug = Str::slug($name);
    $slug = $baseSlug;
    $counter = 1;

    while (Complex::query()
      ->when($ignoreComplexId, fn($query) => $query->whereKeyNot($ignoreComplexId))
      ->where('slug', $slug)
      ->exists()
    ) {
      $counter++;
      $slug = $baseSlug . '-' . $counter;
    }

    return $slug;
  }
}
