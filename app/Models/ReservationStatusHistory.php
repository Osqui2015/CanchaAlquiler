<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationStatusHistory extends Model
{
  use HasFactory;

  public $timestamps = false;

  protected $fillable = [
    'reservation_id',
    'from_status',
    'to_status',
    'changed_by_user_id',
    'reason',
    'created_at',
  ];

  protected function casts(): array
  {
    return [
      'created_at' => 'datetime',
    ];
  }

  public function reservation(): BelongsTo
  {
    return $this->belongsTo(Reservation::class);
  }

  public function changedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'changed_by_user_id');
  }
}
