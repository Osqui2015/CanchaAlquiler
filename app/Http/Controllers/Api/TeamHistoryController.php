<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamHistoryController extends Controller
{
  public function show($team): JsonResponse
  {
    $teamModel = Team::with(['history.user', 'history.reservation'])->findOrFail($team);

    $history = $teamModel->history()->with(['user:id,name,email', 'reservation:id,code,start_at'])->orderBy('fecha')->get();

    return response()->json(['data' => ['team' => $teamModel, 'history' => $history]]);
  }
}
