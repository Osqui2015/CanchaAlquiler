<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'complex_id',
        'court_id',
        'client_user_id',
        'client_name',
        'client_phone',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'is_active',
        'is_paid',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }
}
