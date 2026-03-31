<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientFavoritesController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $user = $request->user();

    $favorites = UserFavorite::where('user_id', $user->id)
      ->with('complex')
      ->get()
      ->map(fn($f) => $f->complex);

    return response()->json(['data' => ['favorites' => $favorites]]);
  }

  public function toggle(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'complex_id' => ['required', 'integer', 'exists:complexes,id'],
    ]);

    $user = $request->user();

    $fav = UserFavorite::where('user_id', $user->id)
      ->where('complex_id', $validated['complex_id'])
      ->first();

    if ($fav) {
      $fav->delete();
      $action = 'removed';
    } else {
      $fav = UserFavorite::create([
        'user_id' => $user->id,
        'complex_id' => $validated['complex_id'],
      ]);
      $action = 'added';
    }

    return response()->json([
      'status' => 'ok',
      'action' => $action,
      'favorite' => $fav ? $fav->load('complex') : null,
    ]);
  }
}
