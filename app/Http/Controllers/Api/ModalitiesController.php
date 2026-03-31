<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;

class ModalitiesController extends Controller
{
  public function index($sport): JsonResponse
  {
    $sportModel = Sport::where('id', $sport)->orWhere('slug', $sport)->firstOrFail();

    $modalities = match ($sportModel->slug) {
      'futbol' => ['individual', 'equipo_5', 'equipo_7', 'equipo_11'],
      'padel' => ['individual', 'pareja'],
      'basquet' => ['3x3', '5x5'],
      default => ['individual'],
    };

    return response()->json(['data' => ['modalities' => $modalities]]);
  }
}
