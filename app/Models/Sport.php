<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'slug',
    'is_active',
  ];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
    ];
  }

  public function courts(): HasMany
  {
    return $this->hasMany(Court::class);
  }

  public function tournaments(): HasMany
  {
    return $this->hasMany(ComplexTournament::class);
  }

  public function teamBoardPosts(): HasMany
  {
    return $this->hasMany(ComplexTeamBoardPost::class);
  }

  public function userRankings(): HasMany
  {
    return $this->hasMany(UserRanking::class);
  }
}
