<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'complex_id',
        'reservation_id',
        'team_a_players',
        'team_b_players',
        'score_a',
        'score_b',
        'match_type',
        'completed_at',
    ];

    protected $casts = [
        'team_a_players' => 'array',
        'team_b_players' => 'array',
        'completed_at' => 'datetime',
    ];

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
