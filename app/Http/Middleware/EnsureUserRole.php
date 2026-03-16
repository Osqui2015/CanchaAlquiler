<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
  /**
   * Handle an incoming request.
   */
  public function handle(Request $request, Closure $next, string ...$roles): Response
  {
    $user = $request->user();

    if (!$user instanceof User) {
      abort(401);
    }

    if ($user->status !== User::STATUS_ACTIVO) {
      abort(403, 'Tu usuario se encuentra suspendido.');
    }

    if ($roles !== [] && !in_array($user->role, $roles, true)) {
      abort(403, 'No tienes permisos para acceder a este recurso.');
    }

    return $next($request);
  }
}
