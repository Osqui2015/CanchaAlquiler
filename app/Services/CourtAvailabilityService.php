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

    // Allow range if it's within 24 hours (supports past-midnight turns)
    return $startAt->diffInHours($endAt) <= 24;
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

    // Handle past-midnight closing
    if ($closeAt->lessThanOrEqualTo($openAt)) {
        if ($closeAt->hour < $openAt->hour || $closeAt->format('H:i') === '00:00') {
            $closeAt->addDay();
        } else {
            return false;
        }
    }

    // Also consider the case where startAt is actually after midnight but within the previous day's window
    // (e.g. business hours 09:00 - 02:00, and startAt is 01:00 AM)
    // However, the resolveCourtWindow already picks the correct day_of_week based on startAt.
    // If startAt is 01:00 AM Saturday, resolveCourtWindow will check Saturday opening hours.
    // This logic is simplified to assume we are checking the window of the calendar day 'startAt'.

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
