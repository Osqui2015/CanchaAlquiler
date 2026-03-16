<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SessionAuthController extends Controller
{
  public function register(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:150'],
      'email' => ['required', 'email', 'max:150', 'unique:users,email'],
      'phone' => ['nullable', 'string', 'max:40'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $user = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'phone' => $validated['phone'] ?? null,
      'password' => Hash::make($validated['password']),
      'role' => User::ROLE_CLIENTE,
      'status' => User::STATUS_ACTIVO,
      'email_verified_at' => now(),
    ]);

    Auth::login($user);
    $request->session()->regenerate();

    return response()->json([
      'message' => 'Registro exitoso.',
      'data' => $user,
    ], 201);
  }

  public function login(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'email' => ['required', 'email'],
      'password' => ['required', 'string'],
      'remember' => ['nullable', 'boolean'],
    ]);

    if (!Auth::attempt([
      'email' => $validated['email'],
      'password' => $validated['password'],
      'status' => User::STATUS_ACTIVO,
    ], (bool) ($validated['remember'] ?? false))) {
      throw ValidationException::withMessages([
        'email' => 'Credenciales invalidas o usuario suspendido.',
      ]);
    }

    $request->session()->regenerate();

    /** @var User $user */
    $user = $request->user();
    $user->forceFill(['last_login_at' => now()])->save();

    return response()->json([
      'message' => 'Login exitoso.',
      'data' => $user,
    ]);
  }

  public function me(Request $request): JsonResponse
  {
    if (!$request->user()) {
      return response()->json([
        'message' => 'No autenticado.',
      ], 401);
    }

    return response()->json([
      'data' => $request->user(),
    ]);
  }

  public function logout(Request $request): JsonResponse
  {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json([
      'message' => 'Sesion finalizada correctamente.',
    ]);
  }
}
