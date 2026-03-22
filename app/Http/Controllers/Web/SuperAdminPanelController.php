<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\ComplexUserAssignment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\City;
use App\Models\ServiceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminPanelController extends Controller
{
  public function index(): Response
  {
    $monthStart = now()->startOfMonth();
    $monthEnd = now()->endOfMonth();

    $dashboard = [
      'month' => $monthStart->format('Y-m'),
      'total_revenue' => (float) Reservation::query()
        ->where('status', Reservation::STATUS_CONFIRMADA)
        ->whereBetween('start_at', [$monthStart, $monthEnd])
        ->sum('total_amount'),
      'total_clients' => User::query()->where('role', User::ROLE_CLIENTE)->count(),
      'active_admin_cancha' => User::query()
        ->where('role', User::ROLE_ADMIN_CANCHA)
        ->where('status', User::STATUS_ACTIVO)
        ->count(),
      'active_complexes' => Complex::query()
        ->where('status', Complex::STATUS_ACTIVO)
        ->where('booking_enabled', true)
        ->count(),
      'owner_performance' => User::query()
        ->select('users.id', 'users.name', 'users.email', DB::raw('SUM(reservations.total_amount) as total_revenue'))
        ->join('complex_user_assignments', 'complex_user_assignments.user_id', '=', 'users.id')
        ->join('complexes', 'complexes.id', '=', 'complex_user_assignments.complex_id')
        ->join('reservations', 'reservations.complex_id', '=', 'complexes.id')
        ->where('users.role', User::ROLE_ADMIN_CANCHA)
        ->where('reservations.status', Reservation::STATUS_CONFIRMADA)
        ->whereBetween('reservations.start_at', [$monthStart, $monthEnd])
        ->groupBy('users.id', 'users.name', 'users.email')
        ->orderByDesc('total_revenue')
        ->limit(10)
        ->get(),
    ];

    return Inertia::render('SuperAdmin/Dashboard', [
      'dashboard' => $dashboard,
      'catalogs' => [
        'cities' => City::query()->with('province:id,name')->orderBy('name')->get(['id', 'name', 'province_id']),
        'services' => ServiceCatalog::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'icon']),
      ],
      'admins' => User::query()
        ->where('role', User::ROLE_ADMIN_CANCHA)
        ->with(['complexAssignments.complex:id,name,slug'])
        ->orderBy('name')
        ->get(),
      'clients' => User::query()
        ->where('role', User::ROLE_CLIENTE)
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'phone', 'status', 'created_at']),
      'complexes' => Complex::query()
        ->orderBy('name')
        ->get(['id', 'name', 'slug']),
    ]);
  }

  public function storeAdmin(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:150'],
      'email' => ['required', 'email', 'max:150', 'unique:users,email'],
      'password' => ['required', 'string', 'min:8'],
      'phone' => ['nullable', 'string', 'max:40'],
      'status' => ['nullable', 'in:activo,suspendido'],
    ]);

    User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
      'phone' => $validated['phone'] ?? null,
      'role' => User::ROLE_ADMIN_CANCHA,
      'status' => $validated['status'] ?? User::STATUS_ACTIVO,
      'email_verified_at' => now(),
    ]);

    return redirect()->route('panel.superadmin')
      ->with('success', 'AdminCancha creado correctamente.');
  }

  public function assignComplex(Request $request, User $user): RedirectResponse
  {
    if ($user->role !== User::ROLE_ADMIN_CANCHA) {
      abort(422, 'El usuario indicado no es AdminCancha.');
    }

    $validated = $request->validate([
      'complex_id' => ['required', 'integer', 'exists:complexes,id'],
      'assignment_type' => ['required', 'in:owner,manager'],
      'is_primary' => ['nullable', 'boolean'],
    ]);

    ComplexUserAssignment::updateOrCreate(
      [
        'complex_id' => $validated['complex_id'],
        'user_id' => $user->id,
      ],
      [
        'assignment_type' => $validated['assignment_type'],
        'is_primary' => $validated['is_primary'] ?? false,
      ],
    );

    return redirect()->route('panel.superadmin')
      ->with('success', 'Complejo asignado correctamente.');
  }

  public function storeClient(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:150'],
      'email' => ['required', 'email', 'max:150', 'unique:users,email'],
      'password' => ['required', 'string', 'min:8'],
      'phone' => ['nullable', 'string', 'max:40'],
      'status' => ['nullable', 'in:activo,suspendido'],
    ]);

    User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
      'phone' => $validated['phone'] ?? null,
      'role' => User::ROLE_CLIENTE,
      'status' => $validated['status'] ?? User::STATUS_ACTIVO,
      'email_verified_at' => now(),
    ]);

    return redirect()->route('panel.superadmin')
      ->with('success', 'Cliente creado correctamente.');
  }

  public function updateClient(Request $request, User $user): RedirectResponse
  {
    if ($user->role !== User::ROLE_CLIENTE) {
      abort(422, 'El usuario indicado no es Cliente.');
    }

    $validated = $request->validate([
      'name' => ['sometimes', 'string', 'max:150'],
      'phone' => ['nullable', 'string', 'max:40'],
      'status' => ['sometimes', 'in:activo,suspendido'],
    ]);

    $user->update($validated);

    return redirect()->route('panel.superadmin')
      ->with('success', 'Cliente actualizado correctamente.');
  }

  public function updateAdminStatus(Request $request, User $user): RedirectResponse
  {
    if ($user->role !== User::ROLE_ADMIN_CANCHA) {
      abort(422, 'El usuario indicado no es AdminCancha.');
    }

    $validated = $request->validate([
      'status' => ['required', 'in:activo,suspendido'],
    ]);

    $user->update(['status' => $validated['status']]);

    return redirect()->route('panel.superadmin')
      ->with('success', 'AdminCancha actualizado correctamente.');
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

    DB::transaction(function () use ($validated): void {
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
    });

    return redirect()->route('panel.superadmin')
      ->with('success', 'Complejo creado correctamente.');
  }
}
