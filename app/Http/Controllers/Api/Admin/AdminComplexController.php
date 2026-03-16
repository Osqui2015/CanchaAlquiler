<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\ComplexPolicy;
use App\Models\ComplexUserAssignment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminComplexController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $user = $request->user();

    $complexes = Complex::query()
      ->with(['city.province', 'services', 'courts.sport', 'openingHours', 'policies'])
      ->when(!$user->isSuperAdmin(), function ($query) use ($user): void {
        $query->whereHas('assignments', function ($assignmentQuery) use ($user): void {
          $assignmentQuery->where('user_id', $user->id);
        });
      })
      ->orderBy('name')
      ->get();

    return response()->json([
      'data' => $complexes,
    ]);
  }

  public function store(Request $request): JsonResponse
  {
    $user = $request->user();

    $validated = $request->validate([
      'city_id' => ['required', 'integer', 'exists:cities,id'],
      'name' => ['required', 'string', 'max:150'],
      'address_line' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'phone_contact' => ['nullable', 'string', 'max:40'],
      'latitude' => ['nullable', 'numeric', 'between:-90,90'],
      'longitude' => ['nullable', 'numeric', 'between:-180,180'],
      'status' => ['nullable', 'in:activo,suspendido'],
      'booking_enabled' => ['nullable', 'boolean'],
      'service_ids' => ['nullable', 'array'],
      'service_ids.*' => ['integer', 'exists:services_catalog,id'],
    ]);

    $complex = DB::transaction(function () use ($user, $validated): Complex {
      $complex = Complex::create([
        'city_id' => $validated['city_id'],
        'name' => $validated['name'],
        'slug' => $this->buildUniqueSlug($validated['name']),
        'address_line' => $validated['address_line'],
        'description' => $validated['description'] ?? null,
        'phone_contact' => $validated['phone_contact'] ?? null,
        'latitude' => $validated['latitude'] ?? null,
        'longitude' => $validated['longitude'] ?? null,
        'status' => $validated['status'] ?? Complex::STATUS_ACTIVO,
        'booking_enabled' => $validated['booking_enabled'] ?? true,
      ]);

      if (isset($validated['service_ids'])) {
        $complex->services()->sync($validated['service_ids']);
      }

      if ($user->isAdminCancha()) {
        ComplexUserAssignment::updateOrCreate(
          [
            'complex_id' => $complex->id,
            'user_id' => $user->id,
          ],
          [
            'assignment_type' => ComplexUserAssignment::TYPE_OWNER,
            'is_primary' => true,
          ],
        );
      }

      return $complex;
    });

    return response()->json([
      'message' => 'Complejo creado correctamente.',
      'data' => $complex->load(['city.province', 'services']),
    ], 201);
  }

  public function update(Request $request, Complex $complex): JsonResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
      'name' => ['sometimes', 'string', 'max:150'],
      'address_line' => ['sometimes', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'phone_contact' => ['nullable', 'string', 'max:40'],
      'latitude' => ['nullable', 'numeric', 'between:-90,90'],
      'longitude' => ['nullable', 'numeric', 'between:-180,180'],
      'status' => ['sometimes', 'in:activo,suspendido'],
      'booking_enabled' => ['sometimes', 'boolean'],
      'service_ids' => ['nullable', 'array'],
      'service_ids.*' => ['integer', 'exists:services_catalog,id'],
    ]);

    if (isset($validated['name']) && $validated['name'] !== $complex->name) {
      $validated['slug'] = $this->buildUniqueSlug($validated['name'], $complex->id);
    }

    $complex->update($validated);

    if (array_key_exists('service_ids', $validated)) {
      $complex->services()->sync($validated['service_ids'] ?? []);
    }

    return response()->json([
      'message' => 'Complejo actualizado correctamente.',
      'data' => $complex->load(['city.province', 'services']),
    ]);
  }

  public function upsertOpeningHours(Request $request, Complex $complex): JsonResponse
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

    return response()->json([
      'message' => 'Horarios actualizados correctamente.',
      'data' => $complex->openingHours()->orderBy('day_of_week')->get(),
    ]);
  }

  public function upsertPolicy(Request $request, Complex $complex): JsonResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'deposit_percent' => ['required', 'integer', 'min:0', 'max:100'],
      'cancel_limit_minutes' => ['required', 'integer', 'min:0'],
      'refund_percent_before_limit' => ['required', 'integer', 'min:0', 'max:100'],
      'no_show_penalty_percent' => ['required', 'integer', 'min:0', 'max:100'],
    ]);

    $policy = ComplexPolicy::updateOrCreate(
      ['complex_id' => $complex->id],
      $validated,
    );

    return response()->json([
      'message' => 'Politica de reservas actualizada.',
      'data' => $policy,
    ]);
  }

  public function reservationsGrid(Request $request, Complex $complex): JsonResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'from' => ['required', 'date_format:Y-m-d'],
      'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
    ]);

    $from = $validated['from'] . ' 00:00:00';
    $to = $validated['to'] . ' 23:59:59';

    $reservations = Reservation::query()
      ->with(['client:id,name,email,phone', 'court:id,name,complex_id'])
      ->where('complex_id', $complex->id)
      ->whereBetween('start_at', [$from, $to])
      ->orderBy('start_at')
      ->get();

    return response()->json([
      'data' => $reservations,
    ]);
  }

  public function dashboard(Request $request, Complex $complex): JsonResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $monthStart = now()->startOfMonth();
    $monthEnd = now()->endOfMonth();

    $confirmed = Reservation::query()
      ->where('complex_id', $complex->id)
      ->where('status', Reservation::STATUS_CONFIRMADA)
      ->whereBetween('start_at', [$monthStart, $monthEnd]);

    $income = (float) (clone $confirmed)->sum('total_amount');
    $reservationCount = (clone $confirmed)->count();

    $topCourts = Reservation::query()
      ->select('court_id', DB::raw('COUNT(*) as reservations_count'))
      ->where('complex_id', $complex->id)
      ->where('status', Reservation::STATUS_CONFIRMADA)
      ->whereBetween('start_at', [$monthStart, $monthEnd])
      ->groupBy('court_id')
      ->orderByDesc('reservations_count')
      ->with('court:id,name')
      ->limit(5)
      ->get();

    return response()->json([
      'data' => [
        'month' => $monthStart->format('Y-m'),
        'income' => $income,
        'confirmed_reservations' => $reservationCount,
        'top_courts' => $topCourts,
      ],
    ]);
  }

  private function authorizeComplex(User $user, Complex $complex): void
  {
    if ($user->isSuperAdmin()) {
      return;
    }

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
