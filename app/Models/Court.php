<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
  use HasFactory;

  public const STATUS_ACTIVA = 'activa';

  public const STATUS_INACTIVA = 'inactiva';

  public const STATUS_MANTENIMIENTO = 'mantenimiento';

  protected $fillable = [
    'complex_id',
    'sport_id',
    'name',
    'surface_type',
    'players_capacity',
    'slot_duration_minutes',
    'base_price',
    'status',
  ];

  protected function casts(): array
  {
    return [
      'base_price' => 'decimal:2',
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

  public function blocks(): HasMany
  {
    return $this->hasMany(CourtBlock::class);
  }

  public function priceRules(): HasMany
  {
    return $this->hasMany(CourtPriceRule::class);
  }

  public function reservations(): HasMany
  {
    return $this->hasMany(Reservation::class);
  }
}
