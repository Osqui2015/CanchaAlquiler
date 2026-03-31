<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamUserHistory extends Model
{
  use HasFactory;

  protected $table = 'team_user_histories';

  protected $fillable = [
    'team_id',
    'user_id',
    'reservation_id',
    'fecha',
  ];

  protected function casts(): array
  {
    return [
      'fecha' => 'datetime',
    ];
  }

  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function reservation(): BelongsTo
  {
    return $this->belongsTo(Reservation::class);
  }
}
