<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCanchaClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::query()
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
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => User::ROLE_CLIENTE,
            'status' => User::STATUS_ACTIVO,
        ]);

        return response()->json($user);
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
        ]);

        $client->update($validated);

        return response()->json($client);
    }
}
