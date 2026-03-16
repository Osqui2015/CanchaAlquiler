<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplexTournament extends Model
{
  use HasFactory;

  public const STATUS_INSCRIPCIONES_ABIERTAS = 'inscripciones_abiertas';

  public const STATUS_CUPOS_LIMITADOS = 'cupos_limitados';

  public const STATUS_CERRADO = 'cerrado';

  protected $fillable = [
    'complex_id',
    'sport_id',
    'name',
    'category',
    'format',
    'start_date',
    'end_date',
    'status',
    'max_teams',
    'entry_fee',
    'prize',
    'notes',
    'created_by_user_id',
  ];

  protected function casts(): array
  {
    return [
      'start_date' => 'date',
      'end_date' => 'date',
      'entry_fee' => 'decimal:2',
    ];
  }

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }

  public function sport(): BelongsTo
  {
    return $this->belongsTo(Sport::class);
  }

  public function teams(): HasMany
  {
    return $this->hasMany(TournamentTeam::class, 'tournament_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by_user_id');
  }
}
