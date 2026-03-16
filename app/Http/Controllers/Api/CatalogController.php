<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\ServiceCatalog;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
  public function __invoke(): JsonResponse
  {
    $sports = Sport::query()
      ->where('is_active', true)
      ->orderBy('name')
      ->get(['id', 'name', 'slug']);

    $provinces = Province::query()
      ->with(['cities:id,province_id,name'])
      ->orderBy('name')
      ->get(['id', 'name', 'code']);

    $services = ServiceCatalog::query()
      ->where('is_active', true)
      ->orderBy('name')
      ->get(['id', 'name', 'slug', 'icon']);

    return response()->json([
      'data' => [
        'sports' => $sports,
        'provinces' => $provinces,
        'services' => $services,
      ],
    ]);
  }
}
