<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->must_change_password) {
                return redirect()->route('password.change');
            }

            if ($user->canManageSystem()) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->isTeacher()) {
                return redirect()->route('teacher.dashboard');
            }

            return redirect()->route('student.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an authentication attempt using either username or CI.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'El carnet de identidad (CI) o nombre de usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $loginInput = trim($request->input('username'));
        $throttleKey = Str::transliterate(Str::lower($loginInput).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'username' => "Demasiados intentos de acceso. Por favor, intenta de nuevo en {$seconds} segundos.",
            ])->onlyInput('username');
        }

        // Find user by either username or CI
        $user = User::where('username', $loginInput)
            ->orWhere('ci', $loginInput)
            ->first();

        if ($user && Hash::check($request->input('password'), $user->password)) {
            if (! $user->is_active) {
                return back()->withErrors([
                    'username' => 'Tu cuenta ha sido desactivada. Consulta con la administración escolar.',
                ])->onlyInput('username');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            RateLimiter::clear($throttleKey);

            // Audit successful login
            AdministrativeAuditLog::record('auth.login', 'User', $user->id, ['username' => $user->username]);

            // Check if mandatory first-time password change is required
            if ($user->must_change_password) {
                return redirect()->route('password.change')
                    ->with('warning', 'Por motivos de seguridad institucional, debes cambiar tu contraseña inicial antes de continuar.');
            }

            if ($user->canManageSystem()) {
                return redirect()->intended(route('admin.dashboard'));
            }

            if ($user->isTeacher()) {
                return redirect()->intended(route('teacher.dashboard'));
            }

            return redirect()->intended(route('student.dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'username' => 'Las credenciales proporcionadas no son correctas.',
        ])->onlyInput('username');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            AdministrativeAuditLog::record('auth.logout', 'User', Auth::id());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
