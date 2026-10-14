<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdministrativeAuditLog;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $yearId = $request->input('academic_year_id');

        $academicYears = AcademicYear::orderByDesc('year')->get();

        $courses = Course::with(['academicYear', 'teachers'])
            ->withCount(['students', 'elections'])
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->orderBy('grade')
            ->orderBy('parallel')
            ->paginate(15)
            ->withQueryString();

        return view('admin.courses.index', compact('courses', 'academicYears', 'yearId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade' => ['required', 'string', 'max:50'],
            'parallel' => ['required', 'string', 'max:20'],
        ]);

        $name = $validated['grade'].' '.$validated['parallel'];

        $exists = Course::where('academic_year_id', $validated['academic_year_id'])
            ->where('grade', $validated['grade'])
            ->where('parallel', $validated['parallel'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['grade' => 'Este curso y paralelo ya existe en la gestión seleccionada.'])->withInput();
        }

        $course = Course::create([
            'academic_year_id' => $validated['academic_year_id'],
            'grade' => $validated['grade'],
            'parallel' => $validated['parallel'],
            'name' => $name,
        ]);

        AdministrativeAuditLog::record('admin.course_create', 'Course', $course->id, ['name' => $name]);

        return redirect()->route('admin.courses.index')
            ->with('success', "Curso {$name} creado exitosamente.");
    }
}
