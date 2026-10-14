<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeAuditLog;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a paginated listing of students with search filtering.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $students = User::where(fn ($q) => $q->where('role', 'student')->orWhere('role', 'estudiante'))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhere('parallel', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', compact('students', 'search'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(): View
    {
        $courses = Course::with('academicYear')->orderBy('name')->get();

        return view('admin.students.create', compact('courses'));
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'paternal_surname' => ['nullable', 'string', 'max:80'],
            'maternal_surname' => ['nullable', 'string', 'max:80'],
            'name' => ['nullable', 'string', 'max:150'],
            'ci' => ['nullable', 'string', 'max:20', 'unique:users,ci'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'password' => ['nullable', 'string', 'min:4'],
            'course' => ['nullable', 'string', 'max:50'],
            'parallel' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'ci.unique' => 'Este número de CI ya se encuentra registrado.',
            'username.unique' => 'Este nombre de usuario ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 4 caracteres.',
        ]);

        // Resolve full name
        $fullName = trim($validated['name'] ?? '');
        if (empty($fullName)) {
            $fullName = trim(($validated['first_name'] ?? '').' '.($validated['paternal_surname'] ?? '').' '.($validated['maternal_surname'] ?? ''));
        }

        if (empty($fullName)) {
            return back()->withErrors(['name' => 'Debes proporcionar el nombre completo o los apellidos y nombres.'])->withInput();
        }

        // Username defaults to CI if provided, otherwise required username
        $username = $validated['username'] ?? $validated['ci'];
        if (empty($username)) {
            return back()->withErrors(['username' => 'Debes ingresar un nombre de usuario o carnet de identidad.'])->withInput();
        }

        // Generate temporary password if not manually provided (Section 3.1)
        $mustChangePassword = false;
        if (! empty($validated['password'])) {
            $passwordToHash = $validated['password'];
        } else {
            $pat = $validated['paternal_surname'] ?? 'X';
            $mat = $validated['maternal_surname'] ?? null;
            $ci = $validated['ci'] ?? $username;
            $passwordToHash = User::generateTemporaryPassword($pat, $mat, $ci);
            $mustChangePassword = true;
        }

        $student = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'] ?? null,
            'paternal_surname' => $validated['paternal_surname'] ?? null,
            'maternal_surname' => $validated['maternal_surname'] ?? null,
            'ci' => $validated['ci'] ?? null,
            'username' => $username,
            'password' => Hash::make($passwordToHash),
            'role' => 'student',
            'course' => $validated['course'] ?? null,
            'parallel' => $validated['parallel'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'must_change_password' => $mustChangePassword,
        ]);

        // Link with course enrollment if exists
        if (! empty($validated['course']) && ! empty($validated['parallel'])) {
            $courseModel = Course::where('grade', $validated['course'])
                ->where('parallel', $validated['parallel'])
                ->first();

            if ($courseModel) {
                CourseEnrollment::firstOrCreate([
                    'course_id' => $courseModel->id,
                    'user_id' => $student->id,
                ]);
            }
        }

        AdministrativeAuditLog::record('admin.student_create', 'User', $student->id, [
            'username' => $student->username,
            'ci' => $student->ci,
        ]);

        return redirect()->route('admin.students.index')
            ->with('success', 'Estudiante registrado correctamente.');
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit(User $student): View
    {
        abort_if(! $student->isStudent(), 404);

        $courses = Course::with('academicYear')->orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'courses'));
    }

    /**
     * Update the specified student in storage.
     */
    public function update(Request $request, User $student): RedirectResponse
    {
        abort_if(! $student->isStudent(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'paternal_surname' => ['nullable', 'string', 'max:80'],
            'maternal_surname' => ['nullable', 'string', 'max:80'],
            'ci' => ['nullable', 'string', 'max:20', Rule::unique('users', 'ci')->ignore($student->id)],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($student->id)],
            'course' => ['nullable', 'string', 'max:50'],
            'parallel' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $student->update([
            'name' => $validated['name'],
            'first_name' => $validated['first_name'] ?? $student->first_name,
            'paternal_surname' => $validated['paternal_surname'] ?? $student->paternal_surname,
            'maternal_surname' => $validated['maternal_surname'] ?? $student->maternal_surname,
            'ci' => $validated['ci'] ?? $student->ci,
            'username' => $validated['username'],
            'course' => $validated['course'] ?? null,
            'parallel' => $validated['parallel'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Update enrollment
        if (! empty($validated['course']) && ! empty($validated['parallel'])) {
            $courseModel = Course::where('grade', $validated['course'])
                ->where('parallel', $validated['parallel'])
                ->first();

            if ($courseModel) {
                CourseEnrollment::firstOrCreate([
                    'course_id' => $courseModel->id,
                    'user_id' => $student->id,
                ]);
            }
        }

        AdministrativeAuditLog::record('admin.student_update', 'User', $student->id);

        return redirect()->route('admin.students.index')
            ->with('success', 'Datos del estudiante actualizados correctamente.');
    }

    /**
     * Reset the specified student's password.
     */
    public function resetPassword(Request $request, User $student): RedirectResponse
    {
        abort_if(! $student->isStudent(), 404);

        $validated = $request->validate([
            'new_password' => ['nullable', 'string', 'min:4'],
        ]);

        if (! empty($validated['new_password'])) {
            $newPassword = $validated['new_password'];
        } else {
            // Regenerate temporary password by CI/apellidos rule
            $pat = $student->paternal_surname ?? 'X';
            $mat = $student->maternal_surname ?? null;
            $ci = $student->ci ?? $student->username;
            $newPassword = User::generateTemporaryPassword($pat, $mat, $ci);
        }

        $student->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
        ]);

        AdministrativeAuditLog::record('admin.student_password_reset', 'User', $student->id);

        return redirect()->route('admin.students.index')
            ->with('success', "Contraseña restablecida exitosamente para {$student->name}. Se le solicitará cambio en su próximo ingreso.");
    }

    /**
     * Toggle active status for a student.
     */
    public function toggleStatus(User $student): RedirectResponse
    {
        abort_if(! $student->isStudent(), 404);

        $student->update([
            'is_active' => ! $student->is_active,
        ]);

        AdministrativeAuditLog::record('admin.student_toggle_status', 'User', $student->id, [
            'is_active' => $student->is_active,
        ]);

        $statusText = $student->is_active ? 'activado' : 'desactivado';

        return redirect()->route('admin.students.index')
            ->with('success', "El estudiante {$student->name} ha sido {$statusText}.");
    }
}
