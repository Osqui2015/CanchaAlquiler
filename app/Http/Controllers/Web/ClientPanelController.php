<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientPanelController extends Controller
{
  public function index(Request $request): Response
  {
    $user = $request->user();

    // Reservas regulares con payments
    $regularReservations = $user->clientReservations()
      ->with(['court.sport', 'complex.city.province', 'payments'])
      ->get();

    // Reservas recurrentes SIN payments
    $recurringReservations = $user->recurringReservations()
      ->with(['court.sport', 'complex.city.province'])
      ->get();

    // Unir ambas colecciones y ordenar por fecha de creación
    $reservations = $regularReservations->concat($recurringReservations)
      ->sortByDesc('created_at')
      ->values();

    // Estadísticas de ranking y sedes favoritas
    $stats = $user->playerStats()
      ->with('sport:id,name,slug')
      ->get();

    $venueStats = $user->venueStats()
      ->with('complex:id,name')
      ->orderByDesc('matches_played')
      ->take(3)
      ->get();

    return Inertia::render('Client/Dashboard', [
      'reservations' => $reservations,
      'stats' => $stats,
      'venueStats' => $venueStats,
      'checkout' => session('checkout'),
    ]);
  }

  public function history(Request $request): Response
  {
    $user = $request->user();

    $history = $user->clientReservations()
      ->where(function ($query) {
          $query->where('start_at', '<', now())
                ->orWhere('status', \App\Models\Reservation::STATUS_CANCELADA);
      })
      ->with(['court.sport', 'complex.city.province', 'payments'])
      ->orderByDesc('start_at')
      ->get();

    return Inertia::render('Client/History', [
      'history' => $history,
    ]);
  }

  public function storeReservation(Request $request, ReservationService $reservationService): RedirectResponse
  {
    $validated = $request->validate([
      'court_id' => ['required', 'integer', 'exists:courts,id'],
      'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
      'start_time' => ['required', 'date_format:H:i'],
      'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
    ]);

    // Validar que no sea en el pasado si es hoy
    if ($validated['date'] === now()->toDateString()) {
        $currentTime = now()->timezone('America/Argentina/Buenos_Aires')->format('H:i');
        if ($validated['start_time'] < $currentTime) {
            return back()->withErrors(['start_time' => 'No puedes reservar un turno que ya paso. Escolge un horario futuro.']);
        }
    }

    $reservationService->createReservation($request->user(), $validated);

    return redirect()->route('panel.cliente')
      ->with('success', 'Reserva creada. Completa la seña para confirmarla.');
  }

  public function cancelReservation(
    Request $request,
    Reservation $reservation,
    ReservationService $reservationService,
  ): RedirectResponse {
    $validated = $request->validate([
      'reason' => ['nullable', 'string', 'max:500'],
    ]);

    $reservationService->cancelReservation(
      $request->user(),
      $reservation,
      $validated['reason'] ?? null,
    );

    return redirect()->route('panel.cliente')
      ->with('success', 'Reserva cancelada correctamente.');
  }

  public function startCheckout(
    Request $request,
    Reservation $reservation,
    PaymentService $paymentService,
  ): RedirectResponse {
    $validated = $request->validate([
      'provider' => ['nullable', 'in:mercadopago,stripe,otro'],
    ]);

    $checkout = $paymentService->startCheckout(
      $request->user(),
      $reservation,
      $validated['provider'] ?? 'mercadopago',
    );

    return redirect()->route('panel.cliente')
      ->with('success', 'Checkout iniciado. Simula o completa el pago para confirmar tu reserva.')
      ->with('checkout', $checkout);
  }

  public function approveCheckoutDemo(
    Request $request,
    Reservation $reservation,
    PaymentService $paymentService,
  ): RedirectResponse {
    $payment = $reservation->payments()
      ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
      ->latest('id')
      ->first();

    if (!$payment) {
      return redirect()->route('panel.cliente')
        ->with('error', 'No se encontro un pago pendiente para esta reserva.');
    }

    $paymentService->processWebhook([
      'provider_payment_id' => $payment->provider_payment_id,
      'status' => 'approved',
      'payload' => [
        'source' => 'demo',
        'reservation_id' => $reservation->id,
      ],
    ]);

    return redirect()->route('panel.cliente')
      ->with('success', 'Pago aprobado en modo demo. Reserva confirmada.');
  }
}
