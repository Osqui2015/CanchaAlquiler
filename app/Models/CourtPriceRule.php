<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtPriceRule extends Model
{
  use HasFactory;

  protected $fillable = [
    'court_id',
    'day_of_week',
    'start_time',
    'end_time',
    'price_type',
    'value',
    'valid_from',
    'valid_to',
  ];

  protected function casts(): array
  {
    return [
      'value' => 'decimal:2',
      'valid_from' => 'date',
      'valid_to' => 'date',
    ];
  }

  public function court(): BelongsTo
  {
    return $this->belongsTo(Court::class);
  }
}
