<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiOwnerDaily extends Model
{
  use HasFactory;

  protected $table = 'kpi_owner_daily';

  protected $fillable = [
    'date',
    'complex_id',
    'gross_revenue',
    'deposits_revenue',
    'reservations_confirmed',
    'occupancy_minutes',
    'available_minutes',
  ];

  protected function casts(): array
  {
    return [
      'date' => 'date',
      'gross_revenue' => 'decimal:2',
      'deposits_revenue' => 'decimal:2',
    ];
  }

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }
}
