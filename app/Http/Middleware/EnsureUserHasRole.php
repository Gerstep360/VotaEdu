<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

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

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Tu cuenta ha sido desactivada. Consulta con un administrador.',
            ]);
        }

        // Support comma-separated roles passed in a single string (e.g. role:admin,director)
        $expandedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $expandedRoles[] = trim($r);
            }
        }

        if (! empty($expandedRoles) && ! $user->hasRole(...$expandedRoles)) {
            abort(403, 'No tienes autorización para acceder a este recurso.');
        }

        return $next($request);
    }
}
