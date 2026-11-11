<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a uno o más roles.
 *
 * Uso: ->middleware('role:admin') o ->middleware('role:admin,driver').
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless($user instanceof User && $user->hasRole(...$allowed), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
