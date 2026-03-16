<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiGlobalDaily extends Model
{
  use HasFactory;

  protected $table = 'kpi_global_daily';

  protected $fillable = [
    'date',
    'gross_revenue',
    'reservations_confirmed',
    'new_clients',
    'active_complexes',
    'active_admin_cancha',
  ];

  protected function casts(): array
  {
    return [
      'date' => 'date',
      'gross_revenue' => 'decimal:2',
    ];
  }
}
