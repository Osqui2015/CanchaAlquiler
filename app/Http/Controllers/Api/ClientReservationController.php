<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientReservationController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $reservations = $request->user()
      ->clientReservations()
      ->with(['court', 'complex', 'sport', 'payments'])
      ->orderByDesc('created_at')
      ->get();

    return response()->json([
      'data' => $reservations,
    ]);
  }

  public function store(Request $request, ReservationService $reservationService): JsonResponse
  {
    $validated = $request->validate([
      'court_id' => ['required', 'integer', 'exists:courts,id'],
      'date' => ['required', 'date_format:Y-m-d'],
      'start_time' => ['required', 'date_format:H:i'],
      'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
    ]);

    $reservation = $reservationService->createReservation($request->user(), $validated);

    return response()->json([
      'message' => 'Reserva creada correctamente.',
      'data' => $reservation,
    ], 201);
  }

  public function cancel(
    Request $request,
    Reservation $reservation,
    ReservationService $reservationService,
  ): JsonResponse {
    $validated = $request->validate([
      'reason' => ['nullable', 'string', 'max:500'],
    ]);

    $reservation = $reservationService->cancelReservation(
      $request->user(),
      $reservation,
      $validated['reason'] ?? null,
    );

    return response()->json([
      'message' => 'Reserva cancelada correctamente.',
      'data' => $reservation,
    ]);
  }
}
