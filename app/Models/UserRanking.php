<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRanking extends Model
{
    protected $fillable = [
        'user_id',
        'sport_id',
        'type',
        'rankid',
        'points',
        'matches_played',
        'matches_lost',
        'podiums_first',
        'podiums_second',
        'podiums_third',
        'team_name',
        'member_names',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }
}
