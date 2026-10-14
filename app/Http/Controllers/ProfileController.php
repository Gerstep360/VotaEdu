<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the user's personal profile and participation history.
     */
    public function show(): View
    {
        $user = Auth::user();

        // Load participations with the associated election (never revealing the vote choice)
        $participations = $user->participations()
            ->with(['election' => function ($q) {
                $q->select('id', 'title', 'type', 'status', 'created_at', 'closed_at');
            }])
            ->latest('created_at')
            ->get();

        return view('profile.show', compact('user', 'participations'));
    }

    /**
     * Show the form to change password (including first-login forced change).
     */
    public function showChangePassword(): View
    {
        $user = Auth::user();
        $isForced = (bool) $user->must_change_password;

        return view('profile.change-password', compact('user', 'isForced'));
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isForced = (bool) $user->must_change_password;

        $rules = [
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];

        // If it's a regular change, also verify current password
        if (! $isForced) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $messages = [
            'current_password.required' => 'Debes ingresar tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Debes ingresar una nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];

        $request->validate($rules, $messages);

        $user->update([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ]);

        AdministrativeAuditLog::record('user.password_change', 'User', $user->id);

        $redirectRoute = match (true) {
            $user->canManageSystem() => 'admin.dashboard',
            $user->isTeacher() => 'teacher.dashboard',
            default => 'student.dashboard',
        };

        return redirect()->route($redirectRoute)
            ->with('success', 'Tu contraseña ha sido actualizada exitosamente.');
    }
}
