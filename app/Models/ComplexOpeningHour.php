<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplexOpeningHour extends Model
{
  use HasFactory;

  protected $fillable = [
    'complex_id',
    'day_of_week',
    'is_open',
    'open_time',
    'close_time',
  ];

  protected function casts(): array
  {
    return [
      'is_open' => 'boolean',
    ];
  }

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }
}
