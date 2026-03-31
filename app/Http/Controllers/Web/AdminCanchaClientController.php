<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCanchaClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::query()
            ->with('rankings')
            ->where('role', User::ROLE_CLIENTE)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'status', 'created_at']);
        
        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'rankings' => ['nullable', 'array'],
            'rankings.*.sport_id' => ['required', 'exists:sports,id'],
            'rankings.*.type' => ['required', 'string'],
            'rankings.*.rankid' => ['nullable', 'string'],
            'rankings.*.points' => ['nullable', 'integer'],
            'rankings.*.matches_played' => ['nullable', 'integer'],
            'rankings.*.matches_lost' => ['nullable', 'integer'],
            'rankings.*.podiums_first' => ['nullable', 'integer'],
            'rankings.*.podiums_second' => ['nullable', 'integer'],
            'rankings.*.podiums_third' => ['nullable', 'integer'],
            'rankings.*.team_name' => ['nullable', 'string'],
            'rankings.*.member_names' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                'role' => User::ROLE_CLIENTE,
                'status' => User::STATUS_ACTIVO,
            ]);

            if ($request->has('rankings')) {
                foreach ($request->input('rankings') as $rankingData) {
                    $user->rankings()->create([
                        'sport_id' => $rankingData['sport_id'],
                        'type' => $rankingData['type'] ?? 'individual',
                        'rankid' => $rankingData['rankid'] ?? null,
                        'points' => $rankingData['points'] ?? 0,
                        'matches_played' => $rankingData['matches_played'] ?? 0,
                        'matches_lost' => $rankingData['matches_lost'] ?? 0,
                        'podiums_first' => $rankingData['podiums_first'] ?? 0,
                        'podiums_second' => $rankingData['podiums_second'] ?? 0,
                        'podiums_third' => $rankingData['podiums_third'] ?? 0,
                        'team_name' => $rankingData['team_name'] ?? null,
                        'member_names' => $rankingData['member_names'] ?? null,
                    ]);
                }
            }

            return response()->json($user->load('rankings'));
        });
    }

    public function update(Request $request, User $client)
    {
        if ($client->role !== User::ROLE_CLIENTE) {
            throw ValidationException::withMessages(['client' => 'Solo puedes editar clientes.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($client->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in([User::STATUS_ACTIVO, User::STATUS_SUSPENDIDO])],
            'rankings' => ['nullable', 'array'],
            'rankings.*.sport_id' => ['required', 'exists:sports,id'],
            'rankings.*.type' => ['required', 'string'],
            'rankings.*.rankid' => ['nullable', 'string'],
            'rankings.*.points' => ['nullable', 'integer'],
            'rankings.*.matches_played' => ['nullable', 'integer'],
            'rankings.*.matches_lost' => ['nullable', 'integer'],
            'rankings.*.podiums_first' => ['nullable', 'integer'],
            'rankings.*.podiums_second' => ['nullable', 'integer'],
            'rankings.*.podiums_third' => ['nullable', 'integer'],
            'rankings.*.team_name' => ['nullable', 'string'],
            'rankings.*.member_names' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($client, $validated, $request) {
            $client->update($validated);

            if ($request->has('rankings')) {
                foreach ($request->input('rankings') as $rankingData) {
                    $client->rankings()->updateOrCreate(
                        [
                            'sport_id' => $rankingData['sport_id'],
                            'type' => $rankingData['type'] ?? 'individual',
                        ],
                        [
                            'rankid' => $rankingData['rankid'] ?? null,
                            'points' => $rankingData['points'] ?? 0,
                            'matches_played' => $rankingData['matches_played'] ?? 0,
                            'matches_lost' => $rankingData['matches_lost'] ?? 0,
                            'podiums_first' => $rankingData['podiums_first'] ?? 0,
                            'podiums_second' => $rankingData['podiums_second'] ?? 0,
                            'podiums_third' => $rankingData['podiums_third'] ?? 0,
                            'team_name' => $rankingData['team_name'] ?? null,
                            'member_names' => $rankingData['member_names'] ?? null,
                        ]
                    );
                }
            }

            return response()->json($client->load('rankings'));
        });
    }
}
