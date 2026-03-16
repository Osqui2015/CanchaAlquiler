<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceCatalog extends Model
{
  use HasFactory;

  protected $table = 'services_catalog';

  protected $fillable = [
    'name',
    'slug',
    'icon',
    'is_active',
  ];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
    ];
  }

  public function complexes(): BelongsToMany
  {
    return $this->belongsToMany(Complex::class, 'complex_services', 'service_id', 'complex_id');
  }
}
