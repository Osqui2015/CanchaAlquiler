<?php

namespace App\Services;

use App\Models\Complex;
use App\Models\ComplexSpecialDate;
use App\Models\Court;
use App\Models\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class CourtAvailabilityService
{
  public function isCourtAvailable(
    Court $court,
    CarbonInterface $startAt,
    CarbonInterface $endAt,
    ?int $ignoreReservationId = null,
  ): bool {
    if (!$this->hasValidTimeRange($startAt, $endAt)) {
      return false;
    }

    if (!$this->isWithinComplexSchedule($court, $startAt, $endAt)) {
      return false;
    }

    if ($this->hasCourtBlockConflict($court, $startAt, $endAt)) {
      return false;
    }

    if ($this->hasReservationConflict($court, $startAt, $endAt, $ignoreReservationId)) {
      return false;
    }

    return true;
  }

  public function assertCourtAvailable(
    Court $court,
    CarbonInterface $startAt,
    CarbonInterface $endAt,
    ?int $ignoreReservationId = null,
  ): void {
    if (!$this->isCourtAvailable($court, $startAt, $endAt, $ignoreReservationId)) {
      throw ValidationException::withMessages([
        'court_id' => 'La cancha no se encuentra disponible en el rango horario solicitado.',
      ]);
    }
  }

  private function hasValidTimeRange(CarbonInterface $startAt, CarbonInterface $endAt): bool
  {
    if ($endAt->lessThanOrEqualTo($startAt)) {
      return false;
    }

    return $startAt->toDateString() === $endAt->toDateString();
  }

  private function isWithinComplexSchedule(Court $court, CarbonInterface $startAt, CarbonInterface $endAt): bool
  {
    $complex = $court->complex;

    if (!$complex || $complex->status !== Complex::STATUS_ACTIVO || !$complex->booking_enabled) {
      return false;
    }

    $date = $startAt->toDateString();

    $specialDate = $complex->specialDates()
      ->whereDate('date', $date)
      ->first();

    if ($specialDate instanceof ComplexSpecialDate) {
      if ($specialDate->mode === ComplexSpecialDate::MODE_CERRADO) {
        return false;
      }

      if (!$specialDate->open_time || !$specialDate->close_time) {
        return false;
      }

      return $this->isInsideWindow($startAt, $endAt, $specialDate->open_time, $specialDate->close_time);
    }

    $openingHour = $complex->openingHours()
      ->where('day_of_week', $startAt->isoWeekday())
      ->first();

    if (!$openingHour || !$openingHour->is_open || !$openingHour->open_time || !$openingHour->close_time) {
      return false;
    }

    return $this->isInsideWindow($startAt, $endAt, $openingHour->open_time, $openingHour->close_time);
  }

  private function isInsideWindow(
    CarbonInterface $startAt,
    CarbonInterface $endAt,
    string $openTime,
    string $closeTime,
  ): bool {
    $openAt = $startAt->copy()->setTimeFromTimeString($openTime);
    $closeAt = $startAt->copy()->setTimeFromTimeString($closeTime);

    return $startAt->greaterThanOrEqualTo($openAt)
      && $endAt->lessThanOrEqualTo($closeAt);
  }

  private function hasCourtBlockConflict(Court $court, CarbonInterface $startAt, CarbonInterface $endAt): bool
  {
    return $court->blocks()
      ->where('start_at', '<', $endAt)
      ->where('end_at', '>', $startAt)
      ->exists();
  }

  private function hasReservationConflict(
    Court $court,
    CarbonInterface $startAt,
    CarbonInterface $endAt,
    ?int $ignoreReservationId,
  ): bool {
    return $court->reservations()
      ->when($ignoreReservationId, fn($query) => $query->whereKeyNot($ignoreReservationId))
      ->whereIn('status', [
        Reservation::STATUS_PENDIENTE_PAGO,
        Reservation::STATUS_CONFIRMADA,
      ])
      ->where('start_at', '<', $endAt)
      ->where('end_at', '>', $startAt)
      ->exists();
  }
}
