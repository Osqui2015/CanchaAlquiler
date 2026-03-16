<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplexTeamBoardPost extends Model
{
  use HasFactory;

  public const KIND_FALTA_JUGADOR = 'falta_jugador';

  public const KIND_BUSCO_RIVAL = 'busco_rival';

  public const KIND_FALTA_EQUIPO = 'falta_equipo';

  public const STATUS_ACTIVO = 'activo';

  public const STATUS_CERRADO = 'cerrado';

  protected $fillable = [
    'complex_id',
    'sport_id',
    'kind',
    'title',
    'level',
    'needed_players',
    'play_day',
    'play_time',
    'contact',
    'notes',
    'status',
    'created_by_user_id',
  ];

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }

  public function sport(): BelongsTo
  {
    return $this->belongsTo(Sport::class);
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by_user_id');
  }
}
