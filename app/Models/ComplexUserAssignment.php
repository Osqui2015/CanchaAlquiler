<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplexUserAssignment extends Model
{
  use HasFactory;

  public const TYPE_OWNER = 'owner';

  public const TYPE_MANAGER = 'manager';

  protected $fillable = [
    'complex_id',
    'user_id',
    'assignment_type',
    'is_primary',
  ];

  protected function casts(): array
  {
    return [
      'is_primary' => 'boolean',
    ];
  }

  public function complex(): BelongsTo
  {
    return $this->belongsTo(Complex::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
