<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
  /**
   * @return array<string, mixed>
   */
  public function startCheckout(User $user, Reservation $reservation, string $provider = 'mercadopago'): array
  {
    if (!$this->canAccessReservation($user, $reservation)) {
      throw ValidationException::withMessages([
        'reservation' => 'No tienes permisos sobre esta reserva.',
      ]);
    }

    if ($reservation->status !== Reservation::STATUS_PENDIENTE_PAGO) {
      throw ValidationException::withMessages([
        'reservation' => 'La reserva debe estar pendiente de pago para iniciar checkout.',
      ]);
    }

    /** @var Payment|null $payment */
    $payment = $reservation->payments()
      ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
      ->latest('id')
      ->first();

    if (!$payment) {
      $payment = $reservation->payments()->create([
        'user_id' => $user->id,
        'provider' => $provider,
        'provider_payment_id' => 'CHK-' . Str::uuid(),
        'status' => Payment::STATUS_INITIATED,
        'amount' => $reservation->deposit_amount,
        'currency' => $reservation->currency,
        'payment_method' => 'gateway_redirect',
      ]);
    }

    $payment->update([
      'provider' => $provider,
      'status' => Payment::STATUS_PENDING,
    ]);

    return [
      'reservation_id' => $reservation->id,
      'provider' => $payment->provider,
      'provider_payment_id' => $payment->provider_payment_id,
      'amount' => (float) $payment->amount,
      'currency' => $payment->currency,
      'checkout_url' => url('/checkout/' . $payment->provider_payment_id),
    ];
  }

  public function processWebhook(array $payload): Payment
  {
    return DB::transaction(function () use ($payload): Payment {
      /** @var Payment $payment */
      $payment = Payment::query()
        ->with('reservation')
        ->lockForUpdate()
        ->where('provider_payment_id', $payload['provider_payment_id'])
        ->firstOrFail();

      $status = $this->mapProviderStatus($payload['status']);

      $payment->update([
        'status' => $status,
        'paid_at' => $status === Payment::STATUS_APPROVED ? now() : $payment->paid_at,
        'raw_response_json' => $payload['payload'] ?? $payload,
      ]);

      PaymentEvent::create([
        'payment_id' => $payment->id,
        'event_type' => 'webhook:' . $payload['status'],
        'payload_json' => $payload['payload'] ?? $payload,
        'received_at' => now(),
      ]);

      if ($status === Payment::STATUS_APPROVED && $payment->reservation->status === Reservation::STATUS_PENDIENTE_PAGO) {
        $fromStatus = $payment->reservation->status;

        $payment->reservation->update([
          'status' => Reservation::STATUS_CONFIRMADA,
          'hold_expires_at' => null,
        ]);

        ReservationStatusHistory::create([
          'reservation_id' => $payment->reservation->id,
          'from_status' => $fromStatus,
          'to_status' => Reservation::STATUS_CONFIRMADA,
          'changed_by_user_id' => $payment->user_id,
          'reason' => 'Pago de seña aprobado.',
          'created_at' => now(),
        ]);
      }

      return $payment->fresh('reservation');
    });
  }

  private function mapProviderStatus(string $providerStatus): string
  {
    return match ($providerStatus) {
      'approved' => Payment::STATUS_APPROVED,
      'rejected' => Payment::STATUS_REJECTED,
      'refunded' => Payment::STATUS_REFUNDED,
      default => Payment::STATUS_PENDING,
    };
  }

  private function canAccessReservation(User $user, Reservation $reservation): bool
  {
    if ($user->isSuperAdmin()) {
      return true;
    }

    if ($user->isCliente()) {
      return $reservation->client_user_id === $user->id;
    }

    if ($user->isAdminCancha()) {
      return $reservation->complex->assignments()
        ->where('user_id', $user->id)
        ->exists();
    }

    return false;
  }
}
