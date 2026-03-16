<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvailabilitySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilitySearchController extends Controller
{
  public function __invoke(Request $request, AvailabilitySearchService $availabilitySearchService): JsonResponse
  {
    $validated = $request->validate([
      'sport_id' => ['required', 'integer', 'exists:sports,id'],
      'city_id' => ['required', 'integer', 'exists:cities,id'],
      'date' => ['required', 'date_format:Y-m-d'],
      'start_time' => ['required', 'date_format:H:i'],
      'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
    ]);

    $results = $availabilitySearchService->search($validated);

    return response()->json([
      'data' => $results,
    ]);
  }
}
