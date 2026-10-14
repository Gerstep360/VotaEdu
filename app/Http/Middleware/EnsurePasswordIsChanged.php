<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Allow access only to change password routes and logout
            if (! $request->routeIs('password.change', 'password.update', 'logout')) {
                return redirect()->route('password.change')
                    ->with('warning', 'Por motivos de seguridad institucional, debes cambiar tu contraseña inicial antes de continuar.');
            }
        }

        return $next($request);
    }
}
