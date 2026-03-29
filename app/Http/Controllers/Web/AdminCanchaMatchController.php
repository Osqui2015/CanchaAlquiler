<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complex;
use App\Models\GameMatch;
use App\Models\PlayerStat;
use App\Models\PlayerVenueStat;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCanchaMatchController extends Controller
{
    public function store(Request $request, Complex $complex)
    {
        $validated = $request->validate([
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'sport_id' => ['required', 'exists:sports,id'],
            'match_type' => ['required', 'in:solo,pair,team'],
            'team_a_players' => ['required', 'array'],
            'team_a_players.*' => ['exists:users,id'],
            'team_b_players' => ['required', 'array'],
            'team_b_players.*' => ['exists:users,id'],
            'score_a' => ['required', 'integer', 'min:0'],
            'score_b' => ['required', 'integer', 'min:0'],
        ]);

        return DB::transaction(function () use ($validated, $complex) {
            $match = GameMatch::create([
                'sport_id' => $validated['sport_id'],
                'complex_id' => $complex->id,
                'reservation_id' => $validated['reservation_id'],
                'team_a_players' => $validated['team_a_players'],
                'team_b_players' => $validated['team_b_players'],
                'score_a' => $validated['score_a'],
                'score_b' => $validated['score_b'],
                'match_type' => $validated['match_type'],
                'completed_at' => now(),
            ]);

            $winner = null;
            if ($validated['score_a'] > $validated['score_b']) $winner = 'a';
            elseif ($validated['score_b'] > $validated['score_a']) $winner = 'b';

            $this->updateStats($validated['team_a_players'], $validated['sport_id'], $validated['match_type'], $complex->id, $winner === 'a', $winner === null);
            $this->updateStats($validated['team_b_players'], $validated['sport_id'], $validated['match_type'], $complex->id, $winner === 'b', $winner === null);

            return back()->with('success', 'Resultado registrado correctamente y estadísticas actualizadas.');
        });
    }

    private function updateStats(array $userIds, $sportId, $matchType, $complexId, bool $isWinner, bool $isDraw)
    {
        foreach ($userIds as $userId) {
            // Player Stats
            $stat = PlayerStat::firstOrCreate(
                ['user_id' => $userId, 'sport_id' => $sportId, 'match_type' => $matchType]
            );

            if ($isWinner) {
                $stat->increment('wins');
                $stat->increment('points', 3);
            } elseif ($isDraw) {
                $stat->increment('draws');
                $stat->increment('points', 1);
            } else {
                $stat->increment('losses');
            }

            // Venue Stats
            $venueStat = PlayerVenueStat::firstOrCreate(
                ['user_id' => $userId, 'complex_id' => $complexId]
            );
            $venueStat->increment('matches_played');
            if ($isWinner) $venueStat->increment('wins');
        }
    }
}
