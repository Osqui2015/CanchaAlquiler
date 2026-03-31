<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Models\UserRanking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RankingController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $sportParam = $request->query('deporte');
    $modalidad = $request->query('modalidad');

    if (! $sportParam) {
      return response()->json(['error' => 'deporte parameter required'], 400);
    }

    $sport = Sport::where('id', $sportParam)->orWhere('slug', $sportParam)->firstOrFail();

    $query = UserRanking::query()->where('sport_id', $sport->id);

    if ($modalidad) {
      $query->where('type', $modalidad);
    }

    $rankings = $query->orderByDesc('points')->get();

    return response()->json(['data' => ['rankings' => $rankings]]);
  }
}
