<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeAuditLog;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the user's personal profile, edit form, and participation history.
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

        $courses = Course::with('academicYear')->orderBy('name')->get();

        return view('profile.show', compact('user', 'participations', 'courses'));
    }

    /**
     * Update the user's personal profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'name' => ['nullable', 'string', 'max:150'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'paternal_surname' => ['nullable', 'string', 'max:80'],
            'maternal_surname' => ['nullable', 'string', 'max:80'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'ci' => ['nullable', 'string', 'max:20', Rule::unique('users', 'ci')->ignore($user->id)],
        ];

        if ($user->isStudent()) {
            $rules['course'] = ['nullable', 'string', 'max:50'];
            $rules['parallel'] = ['nullable', 'string', 'max:20'];
        }

        $messages = [
            'username.required' => 'Debes ingresar un nombre de usuario de acceso.',
            'username.unique' => 'Este nombre de usuario ya está registrado en otra cuenta.',
            'ci.unique' => 'Este número de carnet de identidad ya se encuentra registrado.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
        ];

        $validated = $request->validate($rules, $messages);

        // Resolver el nombre completo
        $fullName = trim($validated['name'] ?? '');
        if (empty($fullName)) {
            $fullName = trim(($validated['first_name'] ?? '').' '.($validated['paternal_surname'] ?? '').' '.($validated['maternal_surname'] ?? ''));
        }

        if (empty($fullName)) {
            return back()->withErrors(['name' => 'Debes proporcionar tu nombre completo o tus nombres y apellidos.'])->withInput();
        }

        $updateData = [
            'name' => $fullName,
            'first_name' => ! empty($validated['first_name']) ? trim($validated['first_name']) : null,
            'paternal_surname' => ! empty($validated['paternal_surname']) ? trim($validated['paternal_surname']) : null,
            'maternal_surname' => ! empty($validated['maternal_surname']) ? trim($validated['maternal_surname']) : null,
            'username' => trim($validated['username']),
            'ci' => ! empty($validated['ci']) ? trim($validated['ci']) : null,
        ];

        if ($user->isStudent()) {
            $updateData['course'] = ! empty($validated['course']) ? trim($validated['course']) : null;
            $updateData['parallel'] = ! empty($validated['parallel']) ? trim($validated['parallel']) : null;

            // Vincular con CourseEnrollment si coincide con un curso registrado
            if (! empty($updateData['course']) && ! empty($updateData['parallel'])) {
                $courseModel = Course::where('grade', $updateData['course'])
                    ->where('parallel', $updateData['parallel'])
                    ->first();

                if ($courseModel) {
                    CourseEnrollment::firstOrCreate([
                        'course_id' => $courseModel->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        }

        $user->update($updateData);

        AdministrativeAuditLog::record('user.profile_update', 'User', $user->id, [
            'name' => $user->name,
            'username' => $user->username,
            'ci' => $user->ci,
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Tus datos de perfil han sido actualizados exitosamente.');
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
