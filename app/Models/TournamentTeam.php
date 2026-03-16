<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentTeam extends Model
{
  use HasFactory;

  protected $fillable = [
    'tournament_id',
    'name',
    'matches',
    'wins',
    'draws',
    'losses',
    'goal_diff',
    'points',
    'position',
  ];

  public function tournament(): BelongsTo
  {
    return $this->belongsTo(ComplexTournament::class, 'tournament_id');
  }
}
