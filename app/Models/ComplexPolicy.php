<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplexPolicy extends Model
{
  use HasFactory;

  protected $fillable = [
    'complex_id',
    'deposit_percent',
    'cancel_limit_minutes',
    'refund_percent_before_limit',
    'no_show_penalty_percent',
  ];

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }
}
