<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Complex extends Model
{
  use HasFactory;

  public const STATUS_ACTIVO = 'activo';

  public const STATUS_SUSPENDIDO = 'suspendido';

  protected $fillable = [
    'city_id',
    'name',
    'slug',
    'address_line',
    'latitude',
    'longitude',
    'description',
    'phone_contact',
    'instagram_url',
    'facebook_url',
    'status',
    'booking_enabled',
  ];

  protected function casts(): array
  {
    return [
      'booking_enabled' => 'boolean',
      'latitude' => 'decimal:7',
      'longitude' => 'decimal:7',
    ];
  }

  public function scopeActive(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_ACTIVO)
      ->where('booking_enabled', true);
  }

  public function city(): BelongsTo
  {
    return $this->belongsTo(City::class);
  }

  public function assignments(): HasMany
  {
    return $this->hasMany(ComplexUserAssignment::class);
  }

  public function services(): BelongsToMany
  {
    return $this->belongsToMany(ServiceCatalog::class, 'complex_services', 'complex_id', 'service_id');
  }

  public function courts(): HasMany
  {
    return $this->hasMany(Court::class);
  }

  public function openingHours(): HasMany
  {
    return $this->hasMany(ComplexOpeningHour::class);
  }

  public function specialDates(): HasMany
  {
    return $this->hasMany(ComplexSpecialDate::class);
  }

  public function policies(): HasOne
  {
    return $this->hasOne(ComplexPolicy::class);
  }

  public function reservations(): HasMany
  {
    return $this->hasMany(Reservation::class);
  }

  public function tournaments(): HasMany
  {
    return $this->hasMany(ComplexTournament::class);
  }

  public function teamBoardPosts(): HasMany
  {
    return $this->hasMany(ComplexTeamBoardPost::class);
  }

  public function recurringReservations(): HasMany
  {
    return $this->hasMany(RecurringReservation::class);
  }

  public function sports(): BelongsToMany
  {
    return $this->belongsToMany(Sport::class, 'courts', 'complex_id', 'sport_id')->distinct();
  }
}
