<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeAuditLog;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $teachers = User::where(fn ($q) => $q->where('role', 'docente')->orWhere('role', 'teacher'))
            ->with('assignedCourses')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.teachers.index', compact('teachers', 'search'));
    }

    public function create(): View
    {
        $courses = Course::with('academicYear')->orderBy('name')->get();

        return view('admin.teachers.create', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'paternal_surname' => ['nullable', 'string', 'max:80'],
            'maternal_surname' => ['nullable', 'string', 'max:80'],
            'name' => ['nullable', 'string', 'max:150'],
            'ci' => ['nullable', 'string', 'max:20', 'unique:users,ci'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:4'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['exists:courses,id'],
        ]);

        $fullName = trim($validated['name'] ?? '');
        if (empty($fullName)) {
            $fullName = trim(($validated['first_name'] ?? '').' '.($validated['paternal_surname'] ?? '').' '.($validated['maternal_surname'] ?? ''));
        }

        $teacher = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'] ?? null,
            'paternal_surname' => $validated['paternal_surname'] ?? null,
            'maternal_surname' => $validated['maternal_surname'] ?? null,
            'ci' => $validated['ci'] ?? null,
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => 'docente',
            'must_change_password' => true,
            'is_active' => true,
        ]);

        if (! empty($validated['course_ids'])) {
            $teacher->assignedCourses()->sync($validated['course_ids']);
        }

        AdministrativeAuditLog::record('admin.teacher_create', 'User', $teacher->id, ['username' => $teacher->username]);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Docente registrado exitosamente.');
    }

    public function edit(User $teacher): View
    {
        abort_if(! $teacher->isTeacher(), 404);

        $courses = Course::with('academicYear')->orderBy('name')->get();
        $selectedCourseIds = $teacher->assignedCourses()->pluck('courses.id')->toArray();

        return view('admin.teachers.edit', compact('teacher', 'courses', 'selectedCourseIds'));
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        abort_if(! $teacher->isTeacher(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'ci' => ['nullable', 'string', 'max:20', Rule::unique('users', 'ci')->ignore($teacher->id)],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($teacher->id)],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['exists:courses,id'],
        ]);

        $teacher->update([
            'name' => $validated['name'],
            'ci' => $validated['ci'] ?? $teacher->ci,
            'username' => $validated['username'],
        ]);

        $teacher->assignedCourses()->sync($validated['course_ids'] ?? []);

        AdministrativeAuditLog::record('admin.teacher_update', 'User', $teacher->id);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Datos y asignaciones del docente actualizados correctamente.');
    }
}
