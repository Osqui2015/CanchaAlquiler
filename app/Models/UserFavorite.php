<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavorite extends Model
{
  protected $table = 'user_favorites';

  protected $fillable = [
    'user_id',
    'complex_id',
  ];

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }
}
