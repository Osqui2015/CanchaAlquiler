<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
  use HasFactory;

  protected $fillable = [
    'sport_id',
    'name',
    'is_pair',
  ];

  protected function casts(): array
  {
    return [
      'is_pair' => 'boolean',
    ];
  }

  public function sport(): BelongsTo
  {
    return $this->belongsTo(Sport::class);
  }

  public function history(): HasMany
  {
    return $this->hasMany(TeamUserHistory::class);
  }
}
