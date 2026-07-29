<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if (!$user->activo) {
            abort(403, 'Usuario inactivo.');
        }

        if ($roles !== [] && !in_array($user->role?->value ?? (string) $user->role, $roles, true)) {
            // Cliente u otro rol fuera de su ámbito: volver a su inicio
            if (($user->role?->value ?? (string) $user->role) === 'cliente') {
                return redirect()
                    ->route('portal.mis-ordenes')
                    ->with('error', 'No tienes permiso para acceder a ese módulo.');
            }

            abort(403, 'No tienes permiso para acceder a este recurso.');
        }

        return $next($request);
    }
}
