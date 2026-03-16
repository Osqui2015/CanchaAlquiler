<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
  use HasFactory;

  public const STATUS_INITIATED = 'initiated';

  public const STATUS_PENDING = 'pending';

  public const STATUS_APPROVED = 'approved';

  public const STATUS_REJECTED = 'rejected';

  public const STATUS_REFUNDED = 'refunded';

  protected $fillable = [
    'reservation_id',
    'user_id',
    'provider',
    'provider_payment_id',
    'status',
    'amount',
    'currency',
    'payment_method',
    'paid_at',
    'raw_response_json',
  ];

  protected function casts(): array
  {
    return [
      'amount' => 'decimal:2',
      'paid_at' => 'datetime',
      'raw_response_json' => 'array',
    ];
  }

  public function reservation(): BelongsTo
  {
    return $this->belongsTo(Reservation::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function events(): HasMany
  {
    return $this->hasMany(PaymentEvent::class);
  }
}
