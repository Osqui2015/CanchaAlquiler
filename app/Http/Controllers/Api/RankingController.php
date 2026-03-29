<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use Illuminate\Http\Request;

class RankingController extends Controller
{
  public function index(Request $request, Complex $complex)
  {
    $sports = $complex->sports;

    $rankings = $sports->mapWithKeys(function ($sport) {
      return [
        $sport->name => $sport->playerStats()->orderByDesc('points')->get(),
      ];
    });

    return response()->json([
      'sports' => $sports,
      'rankings' => $rankings,
    ]);
  }
}
