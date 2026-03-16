<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Sport;
use App\Services\AvailabilitySearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
  public function index(Request $request, AvailabilitySearchService $availabilitySearchService): Response
  {
    $catalogs = [
      'sports' => Sport::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name', 'slug']),
      'provinces' => Province::query()
        ->with(['cities:id,province_id,name'])
        ->orderBy('name')
        ->get(['id', 'name', 'code']),
    ];

    $filters = [
      'sport_id' => $request->query('sport_id'),
      'province_id' => $request->query('province_id'),
      'city_id' => $request->query('city_id'),
      'date' => $request->query('date'),
      'start_time' => $request->query('start_time'),
      'end_time' => $request->query('end_time'),
    ];

    $availability = [];

    if ($this->hasSearchFilters($request)) {
      $validated = $request->validate([
        'sport_id' => ['required', 'integer', 'exists:sports,id'],
        'city_id' => ['required', 'integer', 'exists:cities,id'],
        'date' => ['required', 'date_format:Y-m-d'],
        'start_time' => ['required', 'date_format:H:i'],
        'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
      ]);

      $availability = $availabilitySearchService->search($validated)->all();
    }

    return Inertia::render('Home', [
      'appName' => config('app.name'),
      'catalogs' => $catalogs,
      'filters' => $filters,
      'availability' => $availability,
    ]);
  }

  private function hasSearchFilters(Request $request): bool
  {
    return $request->filled(['sport_id', 'city_id', 'date', 'start_time']);
  }
}
