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
use App\Models\RecurringReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\AvailabilitySearchService;
use Carbon\Carbon;

class AdminCanchaPanelController extends Controller
{
  public function index(Request $request, AvailabilitySearchService $availabilitySearchService): Response
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

    $complexesPayload = $complexes->map(function (Complex $complex) use ($selectedDate, $availabilitySearchService): array {
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

      // Merge with Recurring Reservations for this day
      $dayOfWeek = Carbon::parse($selectedDate)->dayOfWeekIso;
      $recurringForDay = RecurringReservation::query()
          ->with('court:id,name')
          ->where('complex_id', $complex->id)
          ->where('is_active', true)
          ->where('day_of_week', $dayOfWeek)
          ->where(function($q) use ($selectedDate) {
              $q->whereNull('start_date')->orWhere('start_date', '<=', $selectedDate);
          })
          ->where(function($q) use ($selectedDate) {
              $q->whereNull('end_date')->orWhere('end_date', '>=', $selectedDate);
          })
          ->get();

      $mappedRecurring = $recurringForDay->map(function($rr) use ($selectedDate) {
          return [
              'id' => 'recurring-' . $rr->id, // Virtual ID
              'is_recurring' => true,
              'code' => 'FIJO',
              'status' => 'confirmada',
              'is_paid' => $rr->is_paid,
              'start_at' => $selectedDate . ' ' . $rr->start_time,
              'end_at' => $selectedDate . ' ' . $rr->end_time,
              'client_name' => $rr->client_name,
              'client_phone' => $rr->client_phone,
              'court' => [
                  'name' => $rr->court->name,
              ],
              'client' => [
                  'name' => $rr->client_name,
                  'email' => '',
                  'phone' => $rr->client_phone,
              ]
          ];
      });

      $dailyReservations = $dailyReservations->toBase()->concat($mappedRecurring)->sortBy('start_at');

      // Get availability for the selected date
      $availability = $availabilitySearchService->searchForComplex($complex, [
          'sport_id' => null,
          'date' => $selectedDate,
          'start_time' => '00:00',
          'end_time' => null,
      ]);

      return [
        'id' => $complex->id,
        'name' => $complex->name,
        'slug' => $complex->slug,
        'address_line' => $complex->address_line,
        'description' => $complex->description,
        'phone_contact' => $complex->phone_contact,
        'instagram_url' => $complex->instagram_url,
        'facebook_url' => $complex->facebook_url,
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
          'most_rented_day' => $this->calculateMostRentedDay($confirmedQuery),
          'most_rented_time' => $this->calculateMostRentedTime($confirmedQuery),
        ],
        'monthly_reserved_dates' => (clone $confirmedQuery)->get(['start_at'])->map(fn($r) => $r->start_at->toDateString())->unique()->values()->all(),
        'daily_reservations' => $dailyReservations,
        'availability' => $availability,
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
        'recurring_reservations' => $complex->recurringReservations()->with('court')->get(),
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

  public function updateComplex(Request $request, Complex $complex): RedirectResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'name' => ['sometimes', 'string', 'max:150'],
      'address_line' => ['sometimes', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'phone_contact' => ['nullable', 'string', 'max:40'],
      'instagram_url' => ['nullable', 'string', 'max:255'],
      'facebook_url' => ['nullable', 'string', 'max:255'],
      'latitude' => ['nullable', 'numeric', 'between:-90,90'],
      'longitude' => ['nullable', 'numeric', 'between:-180,180'],
      'service_ids' => ['nullable', 'array'],
      'service_ids.*' => ['integer', 'exists:services_catalog,id'],
    ]);

    DB::transaction(function () use ($complex, $validated): void {
      if (isset($validated['name']) && $validated['name'] !== $complex->name) {
        $validated['slug'] = $this->buildUniqueSlug($validated['name'], $complex->id);
      }

      $complex->update([
        'name' => $validated['name'] ?? $complex->name,
        'slug' => $validated['slug'] ?? $complex->slug,
        'address_line' => $validated['address_line'] ?? $complex->address_line,
        'description' => array_key_exists('description', $validated) ? $validated['description'] : $complex->description,
        'phone_contact' => array_key_exists('phone_contact', $validated) ? $validated['phone_contact'] : $complex->phone_contact,
        'instagram_url' => array_key_exists('instagram_url', $validated) ? $validated['instagram_url'] : $complex->instagram_url,
        'facebook_url' => array_key_exists('facebook_url', $validated) ? $validated['facebook_url'] : $complex->facebook_url,
        'latitude' => array_key_exists('latitude', $validated) ? $validated['latitude'] : $complex->latitude,
        'longitude' => array_key_exists('longitude', $validated) ? $validated['longitude'] : $complex->longitude,
      ]);

      if (array_key_exists('service_ids', $validated)) {
        $complex->services()->sync($validated['service_ids'] ?? []);
      }
    });

    return redirect()->route('panel.admincancha')
      ->with('success', 'Complejo actualizado correctamente.');
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
      'price_30_min' => ['nullable', 'numeric', 'min:0'],
      'price_60_min' => ['nullable', 'numeric', 'min:0'],
      'price_90_min' => ['nullable', 'numeric', 'min:0'],
      'price_120_min' => ['nullable', 'numeric', 'min:0'],
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
      'price_30_min' => ['nullable', 'numeric', 'min:0'],
      'price_60_min' => ['nullable', 'numeric', 'min:0'],
      'price_90_min' => ['nullable', 'numeric', 'min:0'],
      'price_120_min' => ['nullable', 'numeric', 'min:0'],
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

  private function calculateMostRentedDay($query): string
  {
    $reservations = (clone $query)->get(['start_at']);
    if ($reservations->isEmpty()) return 'N/A';
    
    $byDay = $reservations->countBy(fn($r) => $r->start_at->isoWeekday());
    $topDay = $byDay->sortDesc()->keys()->first();
    $names = [1=>'Lunes', 2=>'Martes', 3=>'Miercoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sabado', 7=>'Domingo'];
    return $names[$topDay] ?? 'N/A';
  }

  private function calculateMostRentedTime($query): string
  {
    $reservations = (clone $query)->get(['start_at']);
    if ($reservations->isEmpty()) return 'N/A';
    
    $byTime = $reservations->countBy(fn($r) => $r->start_at->format('H:i'));
    return $byTime->sortDesc()->keys()->first() ?? 'N/A';
  }

  public function getCalendarDayDetails(Request $request, Complex $complex, string $date, AvailabilitySearchService $availabilitySearchService)
  {
    $this->authorizeComplex($request->user(), $complex);
    
    $filters = [
      'sport_id' => null,
      'date' => $date,
      'start_time' => '00:00',
      'end_time' => null,
    ];

    $availability = $availabilitySearchService->searchForComplex($complex, $filters);
    
    $dateStart = Carbon::parse($date)->startOfDay();
    $dateEnd = Carbon::parse($date)->endOfDay();

    $reservations = Reservation::query()
      ->with(['client:id,name,phone,email', 'court:id,name'])
      ->where('complex_id', $complex->id)
      ->whereBetween('start_at', [$dateStart, $dateEnd])
      ->whereIn('status', [Reservation::STATUS_CONFIRMADA, Reservation::STATUS_PENDIENTE_PAGO])
      ->get()
      ->map(function ($res) {
          return [
              'id' => $res->id,
              'court_id' => $res->court_id,
              'status' => $res->status,
              'start_time' => $res->start_at->format('H:i'),
              'end_time' => $res->end_at->format('H:i'),
              'client_name' => $res->client_name ?? $res->client?->name ?? 'Walk-in',
              'client_phone' => $res->client_phone ?? $res->client?->phone ?? '',
              'is_local' => !empty($res->client_name),
          ];
      });

    // Merge with Recurring Reservations
    $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;
    $recurringForDay = RecurringReservation::query()
        ->where('complex_id', $complex->id)
        ->where('is_active', true)
        ->where('day_of_week', $dayOfWeek)
        ->where(function($q) use ($date) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', $date);
        })
        ->where(function($q) use ($date) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
        })
        ->get()
        ->map(function ($rr) {
            return [
                'id' => 'recurring-' . $rr->id,
                'court_id' => $rr->court_id,
                'status' => 'confirmada',
                'start_time' => substr($rr->start_time, 0, 5),
                'end_time' => substr($rr->end_time, 0, 5),
                'client_name' => $rr->client_name,
                'client_phone' => $rr->client_phone,
                'is_local' => true,
                'is_recurring' => true,
            ];
        });

    $reservations = $reservations->concat($recurringForDay);

    return response()->json([
      'availability' => $availability,
      'reservations' => $reservations
    ]);
  }

  public function storeFastReservation(Request $request, Complex $complex)
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'court_id' => ['required', 'integer', 'exists:courts,id'],
      'date' => ['required', 'date_format:Y-m-d'],
      'start_time' => ['required', 'date_format:H:i'],
      'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
      'client_name' => ['required', 'string', 'max:100'],
      'client_phone' => ['nullable', 'string', 'max:50'],
      'client_user_id' => ['nullable', 'integer', 'exists:users,id'],
      'is_paid' => ['nullable', 'boolean'],
    ]);

    $court = Court::query()->where('complex_id', $complex->id)->findOrFail($validated['court_id']);

    $startAt = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['start_time']);
    $endAt = !empty($validated['end_time']) 
        ? Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['end_time']) 
        : $startAt->copy()->addMinutes($court->slot_duration_minutes);

    // Verify it doesn't overlap
    $overlaps = Reservation::query()
        ->where('court_id', $court->id)
        ->whereIn('status', [Reservation::STATUS_CONFIRMADA, Reservation::STATUS_PENDIENTE_PAGO])
        ->where(function($q) use ($startAt, $endAt) {
            $q->where('start_at', '<', $endAt)
              ->where('end_at', '>', $startAt);
        })->exists();

    if ($overlaps) {
        throw ValidationException::withMessages(['time' => 'El horario ya se encuentra ocupado.']);
    }

    // Client assignment / Auto-creation logic
    $clientId = $validated['client_user_id'] ?? null;
    
    if (!$clientId) {
        // Find existing client or create new one
        $existingClient = User::query()
            ->where('role', User::ROLE_CLIENTE)
            ->where('name', $validated['client_name'])
            ->first();

        if ($existingClient) {
            $clientId = $existingClient->id;
        } else {
            // Create user automatically
            $clientEmail = 'cliente.' . time() . rand(10,99) . '@canchaalquiler_auto.com';
            if ($validated['client_phone'] && preg_match('/^[0-9]+$/', preg_replace('/[^0-9]/', '', $validated['client_phone']))) {
                $uniquePhoneEmail = preg_replace('/[^0-9]/', '', $validated['client_phone']) . '@canchaalquiler_auto.com';
                if (!User::where('email', $uniquePhoneEmail)->exists()) {
                    $clientEmail = $uniquePhoneEmail;
                }
            }

            $newUser = User::create([
                'name' => $validated['client_name'],
                'email' => $clientEmail,
                'phone' => $validated['client_phone'],
                'password' => \Illuminate\Support\Facades\Hash::make('password_auto_' . rand(1000,9999)),
                'role' => User::ROLE_CLIENTE,
                'status' => User::STATUS_ACTIVO,
            ]);
            $clientId = $newUser->id;
        }
    }

    $reservation = Reservation::create([
        'code' => 'RES-ADM-' . strtoupper(Str::random(6)),
        'client_user_id' => $clientId, 
        'complex_id' => $complex->id,
        'court_id' => $court->id,
        'sport_id' => $court->sport_id,
        'start_at' => $startAt,
        'end_at' => $endAt,
        'total_amount' => 0, 
        'deposit_amount' => 0,
        'currency' => 'ARS',
        'status' => Reservation::STATUS_CONFIRMADA,
        'is_paid' => $validated['is_paid'] ?? false,
        'client_name' => $validated['client_name'],
        'client_phone' => $validated['client_phone'] ?? null,
    ]);

    \App\Models\ReservationStatusHistory::create([
        'reservation_id' => $reservation->id,
        'from_status' => null,
        'to_status' => Reservation::STATUS_CONFIRMADA,
        'changed_by_user_id' => $request->user()->id,
        'reason' => 'Reserva local rapida creada por administrador.',
        'created_at' => now(),
    ]);

    return redirect()->back()->with('success', 'Reserva local creada correctamente.');
  }

  public function getAdvancedReports(Request $request, Complex $complex)
  {
      $this->authorizeComplex($request->user(), $complex);

      $validated = $request->validate([
          'start_date' => ['required', 'date_format:Y-m-d'],
          'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
          'court_id' => ['nullable', 'integer', 'exists:courts,id'],
      ]);

      $startAt = \Carbon\Carbon::parse($validated['start_date'])->startOfDay();
      $endAt = \Carbon\Carbon::parse($validated['end_date'])->endOfDay();

      $query = \App\Models\Reservation::query()
          ->where('complex_id', $complex->id)
          ->whereBetween('start_at', [$startAt, $endAt])
          ->where('status', \App\Models\Reservation::STATUS_CONFIRMADA)
          ->when($validated['court_id'] ?? null, fn($q, $cid) => $q->where('court_id', $cid));

      $reservations = $query->with(['client', 'court'])->get();

      $totalRevenue = $reservations->sum('total_amount');
      $totalReservations = $reservations->count();

      // Daily Revenue Trend
      $dailyRevenue = $reservations->groupBy(fn($r) => $r->start_at->format('Y-m-d'))
          ->map(fn($group, $date) => ['date' => $date, 'amount' => $group->sum('total_amount')])
          ->values();

      // Hourly Distribution (Peak Hours)
      $hourlyDistribution = $reservations->groupBy(fn($r) => $r->start_at->format('H:00'))
          ->map(fn($group, $hour) => ['hour' => $hour, 'count' => $group->count()])
          ->sortBy('hour')
          ->values();

      // Client Retention (New vs Recurring)
      $clientReservationCounts = \App\Models\Reservation::where('complex_id', $complex->id)
          ->whereNotNull('client_user_id')
          ->groupBy('client_user_id')
          ->selectRaw('client_user_id, count(*) as count')
          ->get()
          ->pluck('count', 'client_user_id');

      $clientRetention = [
          'new' => $reservations->whereNotNull('client_user_id')->filter(fn($r) => ($clientReservationCounts[$r->client_user_id] ?? 0) <= 1)->count(),
          'returning' => $reservations->whereNotNull('client_user_id')->filter(fn($r) => ($clientReservationCounts[$r->client_user_id] ?? 0) > 1)->count(),
      ];

      $topClientRecords = $reservations->whereNotNull('client_user_id')->countBy('client_user_id')->sortDesc();
      $topClientUserId = $topClientRecords->keys()->first();
      $topClient = null;
      if ($topClientUserId) {
          $client = $reservations->firstWhere('client_user_id', $topClientUserId)->client;
          $topClient = $client ? [
              'name' => $client->name,
              'reservations_count' => $topClientRecords->first(),
          ] : null;
      }

      $topCourtRecords = $reservations->countBy('court_id')->sortDesc();
      $topCourtId = $topCourtRecords->keys()->first();
      $topCourt = null;
      if ($topCourtId) {
          $court = $reservations->firstWhere('court_id', $topCourtId)->court;
          $topCourt = $court ? [
              'name' => $court->name,
              'reservations_count' => $topCourtRecords->first(),
          ] : null;
      }

      return response()->json([
          'total_revenue' => $totalRevenue,
          'total_reservations' => $totalReservations,
          'daily_revenue' => $dailyRevenue,
          'hourly_distribution' => $hourlyDistribution,
          'client_retention' => $clientRetention,
          'top_client' => $topClient,
          'top_court' => $topCourt,
      ]);
  }

  public function cancelReservation(Request $request, Complex $complex, \App\Models\Reservation $reservation)
  {
      $this->authorizeComplex($request->user(), $complex);

      if ($reservation->complex_id !== $complex->id) {
          abort(403);
      }

      $reservation->update([
          'status' => \App\Models\Reservation::STATUS_CANCELADA,
          'canceled_at' => now(),
          'canceled_by_user_id' => $request->user()->id,
          'cancel_reason' => 'Cancelado por el administrador.'
      ]);

      return redirect()->back()->with('success', 'Reserva cancelada exitosamente.');
  }

  public function storeRecurringReservation(Request $request, Complex $complex)
  {
      $this->authorizeComplex($request->user(), $complex);
      
      $validated = $request->validate([
          'court_id' => 'required|exists:courts,id',
          'day_of_week' => 'required|integer|between:1,7',
          'start_time' => 'required|date_format:H:i',
          'end_time' => 'required|date_format:H:i|after:start_time',
          'client_name' => 'required|string|max:100',
          'client_phone' => 'nullable|string|max:50',
          'client_user_id' => 'nullable|exists:users,id',
          'is_paid' => 'nullable|boolean',
          'notes' => 'nullable|string',
      ]);
      
      // Check for overlaps with other recurring reservations
      $overlaps = $complex->recurringReservations()
          ->where('court_id', $validated['court_id'])
          ->where('day_of_week', $validated['day_of_week'])
          ->where('is_active', true)
          ->where(function($q) use ($validated) {
              $q->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time']);
          })->exists();
          
      if ($overlaps) {
          throw ValidationException::withMessages(['time' => 'Ya existe un turno fijo en ese horario.']);
      }
      
      $complex->recurringReservations()->create($validated);
      
      return redirect()->back()->with('success', 'Turno fijo creado correctamente.');
  }

  public function destroyRecurringReservation(Request $request, Complex $complex, RecurringReservation $recurringReservation)
  {
      $this->authorizeComplex($request->user(), $complex);
      
      if ($recurringReservation->complex_id !== $complex->id) {
          abort(403);
      }
      
      $recurringReservation->delete();
      
      return redirect()->back()->with('success', 'Turno fijo eliminado correctamente.');
  }

  public function updateReservation(Request $request, Complex $complex, Reservation $reservation)
  {
      $this->authorizeComplex($request->user(), $complex);
      if ($reservation->complex_id !== $complex->id) abort(403);

      $validated = $request->validate([
          'court_id' => ['required', 'integer', 'exists:courts,id'],
          'date' => ['required', 'date_format:Y-m-d'],
          'start_time' => ['required', 'date_format:H:i'],
          'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
          'client_name' => ['required', 'string', 'max:100'],
          'client_phone' => ['nullable', 'string', 'max:50'],
          'is_paid' => ['nullable', 'boolean'],
      ]);

      $startAt = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['start_time']);
      $endAt = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['end_time']);

      // Overlap check (excluding current reservation)
      $overlaps = Reservation::query()
          ->where('court_id', $validated['court_id'])
          ->where('id', '!=', $reservation->id)
          ->whereIn('status', [Reservation::STATUS_CONFIRMADA, Reservation::STATUS_PENDIENTE_PAGO])
          ->where(function($q) use ($startAt, $endAt) {
              $q->where('start_at', '<', $endAt)
                ->where('end_at', '>', $startAt);
          })->exists();

      if ($overlaps) throw ValidationException::withMessages(['time' => 'El horario ya se encuentra ocupado.']);

      $reservation->update([
          'court_id' => $validated['court_id'],
          'start_at' => $startAt,
          'end_at' => $endAt,
          'client_name' => $validated['client_name'],
          'client_phone' => $validated['client_phone'],
          'is_paid' => $validated['is_paid'] ?? false,
      ]);

      return redirect()->back()->with('success', 'Reserva actualizada correctamente.');
  }

  public function updateRecurringReservation(Request $request, Complex $complex, RecurringReservation $recurringReservation)
  {
      $this->authorizeComplex($request->user(), $complex);
      if ($recurringReservation->complex_id !== $complex->id) abort(403);

      $validated = $request->validate([
          'court_id' => 'required|exists:courts,id',
          'day_of_week' => 'required|integer|between:1,7',
          'start_time' => 'required|date_format:H:i',
          'end_time' => 'required|date_format:H:i|after:start_time',
          'client_name' => 'required|string|max:100',
          'client_phone' => 'nullable|string|max:50',
          'is_paid' => 'nullable|boolean',
          'notes' => 'nullable|string',
      ]);

      // Overlap check
      $overlaps = $complex->recurringReservations()
          ->where('id', '!=', $recurringReservation->id)
          ->where('court_id', $validated['court_id'])
          ->where('day_of_week', $validated['day_of_week'])
          ->where('is_active', true)
          ->where(function($q) use ($validated) {
              $q->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time']);
          })->exists();
          
      if ($overlaps) throw ValidationException::withMessages(['time' => 'Ya existe un turno fijo en ese horario.']);

      $recurringReservation->update($validated);

      return redirect()->back()->with('success', 'Turno fijo actualizado correctamente.');
  }
}
