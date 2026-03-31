<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
  use HasFactory;

  public const STATUS_PENDIENTE_PAGO = 'pendiente_pago';

  public const STATUS_CONFIRMADA = 'confirmada';

  public const STATUS_CANCELADA = 'cancelada';

  public const STATUS_EXPIRADA = 'expirada';

  public const STATUS_NO_SHOW = 'no_show';

  protected $fillable = [
    'code',
    'client_user_id',
    'complex_id',
    'court_id',
    'sport_id',
    'modalidad',
    'start_at',
    'end_at',
    'total_amount',
    'deposit_amount',
    'currency',
    'status',
    'is_paid',
    'hold_expires_at',
    'canceled_at',
    'canceled_by_user_id',
    'cancel_reason',
    'client_name',
    'client_phone',
  ];

  protected function casts(): array
  {
    return [
      'start_at' => 'datetime',
      'end_at' => 'datetime',
      'hold_expires_at' => 'datetime',
      'canceled_at' => 'datetime',
      'total_amount' => 'decimal:2',
      'deposit_amount' => 'decimal:2',
      'is_paid' => 'boolean',
    ];
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(User::class, 'client_user_id');
  }

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }

  public function court(): BelongsTo
  {
    return $this->belongsTo(Court::class);
  }

  public function sport(): BelongsTo
  {
    return $this->belongsTo(Sport::class);
  }

  public function canceledBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'canceled_by_user_id');
  }

  public function statusHistory(): HasMany
  {
    return $this->hasMany(ReservationStatusHistory::class);
  }

  public function payments(): HasMany
  {
    return $this->hasMany(Payment::class);
  }
}
