<?php

namespace App\Services;

use App\Models\Complex;
use App\Models\ComplexOpeningHour;
use App\Models\ComplexSpecialDate;
use App\Models\Court;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilitySearchService
{
  public function __construct(private readonly CourtAvailabilityService $courtAvailabilityService) {}

  /**
   * @param  array<string, mixed>  $filters
   * @return Collection<int, array<string, mixed>>
   */
  public function search(array $filters): Collection
  {
    $selectedStartAt = Carbon::createFromFormat('Y-m-d H:i', $filters['date'] . ' ' . $filters['start_time']);
    $hasExactRange = !empty($filters['end_time']);
    $selectedEndAt = $hasExactRange
      ? Carbon::createFromFormat('Y-m-d H:i', $filters['date'] . ' ' . $filters['end_time'])
      : null;

    $courts = Court::query()
      ->with(['complex.city.province', 'complex.openingHours', 'complex.specialDates', 'sport'])
      ->where('sport_id', $filters['sport_id'])
      ->where('status', Court::STATUS_ACTIVA)
      ->whereHas('complex', function ($query) use ($filters): void {
        $query->where('city_id', $filters['city_id'])
          ->where('status', Complex::STATUS_ACTIVO)
          ->where('booking_enabled', true);
      })
      ->get();

    $availableCourts = $courts
      ->map(function (Court $court) use ($selectedStartAt, $selectedEndAt, $hasExactRange): ?array {
        $availableSlots = $hasExactRange
          ? $this->buildExactRangeSlots($court, $selectedStartAt, $selectedEndAt)
          : $this->buildAvailableSlotsFromStartTime($court, $selectedStartAt);

        if ($availableSlots === []) {
          return null;
        }

        return [
          'court' => $court,
          'available_slots' => $availableSlots,
        ];
      })
      ->filter()
      ->values();

    return $availableCourts
      ->groupBy(fn(array $entry): int => $entry['court']->complex_id)
      ->map(function (Collection $group): array {
        /** @var array{court: Court, available_slots: array<int, array<string, string>>} $firstEntry */
        $firstEntry = $group->first();
        $firstCourt = $firstEntry['court'];
        $complex = $firstCourt->complex;

        return [
          'complex' => $this->buildComplexPayload($complex),
          'courts' => $group->map(function (array $entry): array {
            /** @var Court $court */
            $court = $entry['court'];

            return $this->buildCourtPayload($court, $entry['available_slots']);
          })->values()->all(),
        ];
      })
      ->values();
  }

  /**
   * @param  array<string, mixed>  $filters
   * @return array<string, mixed>
   */
  public function searchForComplex(Complex $complex, array $filters): array
  {
    $selectedStartAt = Carbon::createFromFormat('Y-m-d H:i', $filters['date'] . ' ' . $filters['start_time']);
    $hasExactRange = !empty($filters['end_time']);
    $selectedEndAt = $hasExactRange
      ? Carbon::createFromFormat('Y-m-d H:i', $filters['date'] . ' ' . $filters['end_time'])
      : null;

    $complex->loadMissing(['city.province', 'openingHours', 'specialDates']);

    $allActiveCourts = Court::query()
      ->with(['sport'])
      ->where('complex_id', $complex->id)
      ->where('status', Court::STATUS_ACTIVA)
      ->orderBy('name')
      ->get();

    $filteredCourts = $allActiveCourts
      ->when($filters['sport_id'] ?? null, function (Collection $collection, mixed $sportId): Collection {
        return $collection->where('sport_id', (int) $sportId)->values();
      });

    $courtsPayload = $filteredCourts->map(function (Court $court) use ($selectedStartAt, $selectedEndAt, $hasExactRange): array {
      $availableSlots = $hasExactRange
        ? $this->buildExactRangeSlots($court, $selectedStartAt, $selectedEndAt)
        : $this->buildAvailableSlotsFromStartTime($court, $selectedStartAt);

      return $this->buildCourtPayload($court, $availableSlots);
    })->values();

    $sports = $allActiveCourts
      ->map(fn(Court $court): array => [
        'id' => $court->sport->id,
        'name' => $court->sport->name,
        'slug' => $court->sport->slug,
      ])
      ->unique('id')
      ->values()
      ->all();

    $availableCourtsCount = $courtsPayload
      ->filter(fn(array $court): bool => $court['available_slots'] !== [])
      ->count();

    return [
      'complex' => $this->buildComplexPayload($complex),
      'summary' => [
        'total_courts' => $allActiveCourts->count(),
        'displayed_courts' => $courtsPayload->count(),
        'available_courts' => $availableCourtsCount,
        'sports' => $sports,
      ],
      'courts' => $courtsPayload->all(),
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private function buildComplexPayload(Complex $complex): array
  {
    $city = $complex->city;
    $province = $city?->province;

    return [
      'id' => $complex->id,
      'name' => $complex->name,
      'slug' => $complex->slug,
      'address' => $complex->address_line,
      'city' => $city?->name,
      'province' => $province?->name,
      'description' => $complex->description,
      'phone_contact' => $complex->phone_contact,
      'latitude' => $complex->latitude !== null ? (float) $complex->latitude : null,
      'longitude' => $complex->longitude !== null ? (float) $complex->longitude : null,
      'photo_url' => '/images/complex-default.svg',
      'map_url' => $this->buildComplexMapUrl($complex),
      'map_embed_url' => $this->buildComplexMapEmbedUrl($complex),
    ];
  }

  /**
   * @param  array<int, array{start_time: string, end_time: string, label: string}>  $availableSlots
   * @return array<string, mixed>
   */
  private function buildCourtPayload(Court $court, array $availableSlots): array
  {
    return [
      'id' => $court->id,
      'name' => $court->name,
      'surface_type' => $court->surface_type,
      'players_capacity' => $court->players_capacity,
      'slot_duration_minutes' => $court->slot_duration_minutes,
      'base_price' => (float) $court->base_price,
      'available_slots' => $availableSlots,
      'sport' => [
        'id' => $court->sport->id,
        'name' => $court->sport->name,
        'slug' => $court->sport->slug,
      ],
    ];
  }

  /**
   * @return array<int, array{start_time: string, end_time: string, label: string}>
   */
  private function buildExactRangeSlots(Court $court, CarbonInterface $startAt, ?CarbonInterface $endAt): array
  {
    if (!$endAt) {
      return [];
    }

    if (!$this->courtAvailabilityService->isCourtAvailable($court, $startAt, $endAt)) {
      return [];
    }

    return [
      [
        'start_time' => $startAt->format('H:i'),
        'end_time' => $endAt->format('H:i'),
        'label' => $startAt->format('H:i') . ' - ' . $endAt->format('H:i'),
      ],
    ];
  }

  /**
   * @return array<int, array{start_time: string, end_time: string, label: string}>
   */
  private function buildAvailableSlotsFromStartTime(Court $court, CarbonInterface $selectedStartAt): array
  {
    $window = $this->resolveCourtWindow($court, $selectedStartAt);

    if (!$window) {
      return [];
    }

    $slotDurationMinutes = max(15, (int) $court->slot_duration_minutes);
    $windowStart = $window['open_at'];
    $windowEnd = $window['close_at'];
    $slotStart = $selectedStartAt->greaterThan($windowStart)
      ? $selectedStartAt->copy()
      : $windowStart->copy();

    $slotStart = $this->alignToSlotBoundary($slotStart, $windowStart, $slotDurationMinutes);
    $availableSlots = [];

    while (true) {
      $slotEnd = $slotStart->copy()->addMinutes($slotDurationMinutes);

      if ($slotEnd->greaterThan($windowEnd)) {
        break;
      }

      if ($this->courtAvailabilityService->isCourtAvailable($court, $slotStart, $slotEnd)) {
        $availableSlots[] = [
          'start_time' => $slotStart->format('H:i'),
          'end_time' => $slotEnd->format('H:i'),
          'label' => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
        ];
      }

      $slotStart = $slotStart->addMinutes($slotDurationMinutes);

      // Safety cap to avoid infinite loops on malformed data.
      if (count($availableSlots) > 64) {
        break;
      }
    }

    return $availableSlots;
  }

  /**
   * @return array{open_at: Carbon, close_at: Carbon}|null
   */
  private function resolveCourtWindow(Court $court, CarbonInterface $targetDate): ?array
  {
    $complex = $court->complex;

    if (!$complex || $complex->status !== Complex::STATUS_ACTIVO || !$complex->booking_enabled) {
      return null;
    }

    $dateString = $targetDate->toDateString();

    $specialDate = $complex->specialDates->first(function (ComplexSpecialDate $row) use ($dateString): bool {
      return $row->date?->toDateString() === $dateString;
    });

    if ($specialDate instanceof ComplexSpecialDate) {
      if ($specialDate->mode === ComplexSpecialDate::MODE_CERRADO) {
        return null;
      }

      if (!$specialDate->open_time || !$specialDate->close_time) {
        return null;
      }

      return $this->buildWindowRange($targetDate, $specialDate->open_time, $specialDate->close_time);
    }

    $openingHour = $complex->openingHours->first(function (ComplexOpeningHour $row) use ($targetDate): bool {
      return (int) $row->day_of_week === $targetDate->isoWeekday();
    });

    if (!$openingHour || !$openingHour->is_open || !$openingHour->open_time || !$openingHour->close_time) {
      return null;
    }

    return $this->buildWindowRange($targetDate, $openingHour->open_time, $openingHour->close_time);
  }

  /**
   * @return array{open_at: Carbon, close_at: Carbon}|null
   */
  private function buildWindowRange(CarbonInterface $targetDate, string $openTime, string $closeTime): ?array
  {
    $openAt = Carbon::parse($targetDate->toDateString() . ' ' . $openTime);
    $closeAt = Carbon::parse($targetDate->toDateString() . ' ' . $closeTime);

    if ($closeAt->lessThanOrEqualTo($openAt)) {
      return null;
    }

    return [
      'open_at' => $openAt,
      'close_at' => $closeAt,
    ];
  }

  private function alignToSlotBoundary(CarbonInterface $slotStart, CarbonInterface $windowStart, int $slotDurationMinutes): Carbon
  {
    $minutesFromWindowStart = $windowStart->diffInMinutes($slotStart, false);

    if ($minutesFromWindowStart <= 0) {
      return $windowStart->copy();
    }

    $remainder = $minutesFromWindowStart % $slotDurationMinutes;

    if ($remainder === 0) {
      return $slotStart->copy();
    }

    return $slotStart->copy()->addMinutes($slotDurationMinutes - $remainder);
  }

  private function buildComplexMapUrl(Complex $complex): string
  {
    if ($complex->latitude !== null && $complex->longitude !== null) {
      return sprintf(
        'https://www.openstreetmap.org/?mlat=%1$.6F&mlon=%2$.6F#map=16/%1$.6F/%2$.6F',
        (float) $complex->latitude,
        (float) $complex->longitude,
      );
    }

    $query = trim(implode(' ', array_filter([
      $complex->name,
      $complex->address_line,
      $complex->city?->name,
      $complex->city?->province?->name,
    ])));

    return 'https://www.openstreetmap.org/search?query=' . urlencode($query);
  }

  private function buildComplexMapEmbedUrl(Complex $complex): ?string
  {
    if ($complex->latitude === null || $complex->longitude === null) {
      return null;
    }

    $lat = (float) $complex->latitude;
    $lng = (float) $complex->longitude;
    $delta = 0.01;
    $bbox = sprintf(
      '%1$.6F%%2C%2$.6F%%2C%3$.6F%%2C%4$.6F',
      $lng - $delta,
      $lat - $delta,
      $lng + $delta,
      $lat + $delta,
    );
    $marker = sprintf('%1$.6F%%2C%2$.6F', $lat, $lng);

    return 'https://www.openstreetmap.org/export/embed.html?bbox=' . $bbox . '&layer=mapnik&marker=' . $marker;
  }
}
