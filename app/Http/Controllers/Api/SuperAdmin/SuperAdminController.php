<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\ComplexUserAssignment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
  public function adminCanchaIndex(): JsonResponse
  {
    $admins = User::query()
      ->where('role', User::ROLE_ADMIN_CANCHA)
      ->with(['complexAssignments.complex:id,name,slug'])
      ->orderBy('name')
      ->get();

    return response()->json([
      'data' => $admins,
    ]);
  }

  public function adminCanchaStore(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:150'],
      'email' => ['required', 'email', 'max:150', 'unique:users,email'],
      'password' => ['required', 'string', 'min:8'],
      'phone' => ['nullable', 'string', 'max:40'],
      'status' => ['nullable', 'in:activo,suspendido'],
    ]);

    $user = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
      'phone' => $validated['phone'] ?? null,
      'role' => User::ROLE_ADMIN_CANCHA,
      'status' => $validated['status'] ?? User::STATUS_ACTIVO,
      'email_verified_at' => now(),
    ]);

    return response()->json([
      'message' => 'AdminCancha creado correctamente.',
      'data' => $user,
    ], 201);
  }

  public function adminCanchaUpdate(Request $request, User $user): JsonResponse
  {
    if ($user->role !== User::ROLE_ADMIN_CANCHA) {
      abort(422, 'El usuario indicado no es AdminCancha.');
    }

    $validated = $request->validate([
      'name' => ['sometimes', 'string', 'max:150'],
      'email' => ['sometimes', 'email', 'max:150', 'unique:users,email,' . $user->id],
      'phone' => ['nullable', 'string', 'max:40'],
      'status' => ['sometimes', 'in:activo,suspendido'],
    ]);

    $user->update($validated);

    return response()->json([
      'message' => 'AdminCancha actualizado correctamente.',
      'data' => $user,
    ]);
  }

  public function assignComplex(Request $request, User $user): JsonResponse
  {
    if ($user->role !== User::ROLE_ADMIN_CANCHA) {
      abort(422, 'El usuario indicado no es AdminCancha.');
    }

    $validated = $request->validate([
      'complex_id' => ['required', 'integer', 'exists:complexes,id'],
      'assignment_type' => ['required', 'in:owner,manager'],
      'is_primary' => ['nullable', 'boolean'],
    ]);

    $assignment = ComplexUserAssignment::updateOrCreate(
      [
        'complex_id' => $validated['complex_id'],
        'user_id' => $user->id,
      ],
      [
        'assignment_type' => $validated['assignment_type'],
        'is_primary' => $validated['is_primary'] ?? false,
      ],
    );

    return response()->json([
      'message' => 'Complejo asignado correctamente.',
      'data' => $assignment,
    ]);
  }

  public function clientsIndex(): JsonResponse
  {
    $clients = User::query()
      ->where('role', User::ROLE_CLIENTE)
      ->orderBy('name')
      ->paginate(20);

    return response()->json($clients);
  }

  public function clientStore(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:150'],
      'email' => ['required', 'email', 'max:150', 'unique:users,email'],
      'password' => ['required', 'string', 'min:8'],
      'phone' => ['nullable', 'string', 'max:40'],
      'status' => ['nullable', 'in:activo,suspendido'],
    ]);

    $client = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
      'phone' => $validated['phone'] ?? null,
      'role' => User::ROLE_CLIENTE,
      'status' => $validated['status'] ?? User::STATUS_ACTIVO,
      'email_verified_at' => now(),
    ]);

    return response()->json([
      'message' => 'Cliente creado correctamente.',
      'data' => $client,
    ], 201);
  }

  public function clientUpdate(Request $request, User $user): JsonResponse
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

    return response()->json([
      'message' => 'Cliente actualizado correctamente.',
      'data' => $user,
    ]);
  }

  public function dashboard(): JsonResponse
  {
    $now = now();
    $monthStart = $now->copy()->startOfMonth();
    $monthEnd = $now->copy()->endOfMonth();

    $totalRevenue = (float) Reservation::query()
      ->where('status', Reservation::STATUS_CONFIRMADA)
      ->whereBetween('start_at', [$monthStart, $monthEnd])
      ->sum('total_amount');

    $totalClients = User::query()
      ->where('role', User::ROLE_CLIENTE)
      ->count();

    $activeAdmins = User::query()
      ->where('role', User::ROLE_ADMIN_CANCHA)
      ->where('status', User::STATUS_ACTIVO)
      ->count();

    $activeComplexes = Complex::query()
      ->where('status', Complex::STATUS_ACTIVO)
      ->where('booking_enabled', true)
      ->count();

    $ownerPerformance = User::query()
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
      ->get();

    return response()->json([
      'data' => [
        'month' => $monthStart->format('Y-m'),
        'total_revenue' => $totalRevenue,
        'total_clients' => $totalClients,
        'active_admin_cancha' => $activeAdmins,
        'active_complexes' => $activeComplexes,
        'owner_performance' => $ownerPerformance,
      ],
    ]);
  }
}
