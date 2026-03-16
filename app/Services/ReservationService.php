<?php

namespace App\Services;

use App\Models\ComplexPolicy;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
  public function __construct(private readonly CourtAvailabilityService $courtAvailabilityService) {}

  /**
   * @param  array<string, mixed>  $payload
   */
  public function createReservation(User $client, array $payload): Reservation
  {
    return DB::transaction(function () use ($client, $payload): Reservation {
      $court = Court::query()
        ->with(['complex.policies', 'priceRules'])
        ->lockForUpdate()
        ->findOrFail($payload['court_id']);

      if ($court->status !== Court::STATUS_ACTIVA) {
        throw ValidationException::withMessages([
          'court_id' => 'La cancha seleccionada no se encuentra activa.',
        ]);
      }

      $startAt = Carbon::createFromFormat('Y-m-d H:i', $payload['date'] . ' ' . $payload['start_time']);
      $endAt = isset($payload['end_time'])
        ? Carbon::createFromFormat('Y-m-d H:i', $payload['date'] . ' ' . $payload['end_time'])
        : $startAt->copy()->addMinutes($court->slot_duration_minutes);

      $this->courtAvailabilityService->assertCourtAvailable($court, $startAt, $endAt);

      $totalAmount = $this->resolveReservationAmount($court, $startAt, $endAt);
      $policy = $court->complex->policies;
      $depositPercent = $policy?->deposit_percent ?? 30;
      $depositAmount = round($totalAmount * ($depositPercent / 100), 2);

      $reservation = Reservation::create([
        'code' => 'RES-' . strtoupper(Str::random(10)),
        'client_user_id' => $client->id,
        'complex_id' => $court->complex_id,
        'court_id' => $court->id,
        'sport_id' => $court->sport_id,
        'start_at' => $startAt,
        'end_at' => $endAt,
        'total_amount' => $totalAmount,
        'deposit_amount' => $depositAmount,
        'currency' => 'ARS',
        'status' => Reservation::STATUS_PENDIENTE_PAGO,
        'hold_expires_at' => now()->addMinutes(10),
      ]);

      ReservationStatusHistory::create([
        'reservation_id' => $reservation->id,
        'from_status' => null,
        'to_status' => Reservation::STATUS_PENDIENTE_PAGO,
        'changed_by_user_id' => $client->id,
        'reason' => 'Reserva creada y pendiente de seña.',
        'created_at' => now(),
      ]);

      Payment::create([
        'reservation_id' => $reservation->id,
        'user_id' => $client->id,
        'provider' => 'otro',
        'provider_payment_id' => 'INIT-' . Str::uuid(),
        'status' => Payment::STATUS_INITIATED,
        'amount' => $depositAmount,
        'currency' => 'ARS',
        'payment_method' => 'pending_gateway',
      ]);

      return $reservation->load(['court', 'complex', 'sport', 'payments']);
    });
  }

  public function cancelReservation(User $actor, Reservation $reservation, ?string $reason = null): Reservation
  {
    return DB::transaction(function () use ($actor, $reservation, $reason): Reservation {
      $reservation = Reservation::query()
        ->with(['complex.policies'])
        ->lockForUpdate()
        ->findOrFail($reservation->id);

      if (!$this->canManageReservation($actor, $reservation)) {
        throw ValidationException::withMessages([
          'reservation' => 'No tienes permisos para cancelar esta reserva.',
        ]);
      }

      if (!in_array($reservation->status, [
        Reservation::STATUS_PENDIENTE_PAGO,
        Reservation::STATUS_CONFIRMADA,
      ], true)) {
        throw ValidationException::withMessages([
          'reservation' => 'Solo se pueden cancelar reservas pendientes o confirmadas.',
        ]);
      }

      if (
        $actor->isCliente()
        && $reservation->status === Reservation::STATUS_CONFIRMADA
        && !$this->canClientCancelByPolicy($reservation)
      ) {
        throw ValidationException::withMessages([
          'reservation' => 'El tiempo limite de cancelacion para esta reserva ya ha vencido.',
        ]);
      }

      $fromStatus = $reservation->status;

      $reservation->update([
        'status' => Reservation::STATUS_CANCELADA,
        'canceled_at' => now(),
        'canceled_by_user_id' => $actor->id,
        'cancel_reason' => $reason,
      ]);

      ReservationStatusHistory::create([
        'reservation_id' => $reservation->id,
        'from_status' => $fromStatus,
        'to_status' => Reservation::STATUS_CANCELADA,
        'changed_by_user_id' => $actor->id,
        'reason' => $reason ?: 'Cancelada por el usuario.',
        'created_at' => now(),
      ]);

      return $reservation->fresh(['court', 'complex', 'client']);
    });
  }

  private function canClientCancelByPolicy(Reservation $reservation): bool
  {
    /** @var ComplexPolicy|null $policy */
    $policy = $reservation->complex->policies;
    $cancelLimit = $policy?->cancel_limit_minutes ?? 180;

    return now()->lessThanOrEqualTo($reservation->start_at->copy()->subMinutes($cancelLimit));
  }

  private function canManageReservation(User $actor, Reservation $reservation): bool
  {
    if ($actor->isSuperAdmin()) {
      return true;
    }

    if ($actor->isCliente()) {
      return $reservation->client_user_id === $actor->id;
    }

    if ($actor->isAdminCancha()) {
      return $reservation->complex->assignments()
        ->where('user_id', $actor->id)
        ->exists();
    }

    return false;
  }

  private function resolveReservationAmount(Court $court, Carbon $startAt, Carbon $endAt): float
  {
    $durationMinutes = $startAt->diffInMinutes($endAt);
    $slotCount = max(1, (int) ceil($durationMinutes / max(1, $court->slot_duration_minutes)));

    $baseTotal = (float) $court->base_price * $slotCount;

    $rule = $court->priceRules()
      ->where('day_of_week', $startAt->isoWeekday())
      ->whereTime('start_time', '<=', $startAt->format('H:i:s'))
      ->whereTime('end_time', '>=', $endAt->format('H:i:s'))
      ->where(function ($query) use ($startAt): void {
        $query->whereNull('valid_from')
          ->orWhereDate('valid_from', '<=', $startAt->toDateString());
      })
      ->where(function ($query) use ($startAt): void {
        $query->whereNull('valid_to')
          ->orWhereDate('valid_to', '>=', $startAt->toDateString());
      })
      ->first();

    if (!$rule) {
      return round($baseTotal, 2);
    }

    if ($rule->price_type === 'fijo') {
      return round((float) $rule->value * $slotCount, 2);
    }

    return round($baseTotal * (float) $rule->value, 2);
  }
}
