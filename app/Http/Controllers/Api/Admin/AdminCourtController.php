<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\Court;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCourtController extends Controller
{
  public function store(Request $request, Complex $complex): JsonResponse
  {
    $this->authorizeComplex($request->user(), $complex);

    $validated = $request->validate([
      'sport_id' => ['required', 'integer', 'exists:sports,id'],
      'name' => ['required', 'string', 'max:150'],
      'surface_type' => ['required', 'in:cesped_sintetico,cemento,polvo_ladrillo,otro'],
      'players_capacity' => ['required', 'integer', 'min:2', 'max:50'],
      'slot_duration_minutes' => ['required', 'integer', 'min:30', 'max:240'],
      'base_price' => ['required', 'numeric', 'min:0'],
      'status' => ['nullable', 'in:activa,inactiva,mantenimiento'],
    ]);

    $court = $complex->courts()->create([
      ...$validated,
      'status' => $validated['status'] ?? Court::STATUS_ACTIVA,
    ]);

    return response()->json([
      'message' => 'Cancha creada correctamente.',
      'data' => $court->load('sport'),
    ], 201);
  }

  public function update(Request $request, Court $court): JsonResponse
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

    return response()->json([
      'message' => 'Cancha actualizada correctamente.',
      'data' => $court->fresh('sport'),
    ]);
  }

  public function destroy(Request $request, Court $court): JsonResponse
  {
    $this->authorizeComplex($request->user(), $court->complex);

    $court->update(['status' => Court::STATUS_INACTIVA]);

    return response()->json([
      'message' => 'Cancha desactivada correctamente.',
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
}
