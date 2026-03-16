<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplexSpecialDate extends Model
{
  use HasFactory;

  public const MODE_CERRADO = 'cerrado';

  public const MODE_HORARIO_ESPECIAL = 'horario_especial';

  protected $fillable = [
    'complex_id',
    'date',
    'mode',
    'open_time',
    'close_time',
    'reason',
  ];

  protected function casts(): array
  {
    return [
      'date' => 'date',
    ];
  }

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }
}
