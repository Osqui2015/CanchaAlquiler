<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InertiaAuthController extends Controller
{
  public function showLogin(): Response
  {
    return Inertia::render('Auth/Login');
  }

  public function showRegister(): Response
  {
    return Inertia::render('Auth/Register');
  }

  public function register(Request $request): RedirectResponse
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

    return redirect()->route('panel')
      ->with('success', 'Registro exitoso. Bienvenido/a.');
  }

  public function login(Request $request): RedirectResponse
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

    return redirect()->route('panel')
      ->with('success', 'Sesion iniciada correctamente.');
  }

  public function logout(Request $request): RedirectResponse
  {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home')
      ->with('success', 'Sesion finalizada correctamente.');
  }
}
