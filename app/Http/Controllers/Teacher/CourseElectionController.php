<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeAuditLog;
use App\Models\Course;
use App\Models\Election;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseElectionController extends Controller
{
    /**
     * Show form to create a new course election.
     */
    public function create(): View|RedirectResponse
    {
        $teacher = Auth::user();
        $assignedCourses = $teacher->assignedCourses()->get();

        if ($assignedCourses->isEmpty()) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'No tienes cursos asignados actualmente para crear votaciones.');
        }

        return view('teacher.elections.create', compact('assignedCourses'));
    }

    /**
     * Store a new course election draft.
     */
    public function store(Request $request): RedirectResponse
    {
        $teacher = Auth::user();
        $assignedCourseIds = $teacher->assignedCourses()->pluck('courses.id')->toArray();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'course_id' => ['required', 'integer', 'in:'.implode(',', $assignedCourseIds)],
        ], [
            'title.required' => 'El título de la votación es obligatorio.',
            'course_id.required' => 'Debes seleccionar un curso asignado.',
            'course_id.in' => 'Solo puedes crear votaciones para los cursos que tienes asignados.',
        ]);

        $course = Course::findOrFail($validated['course_id']);

        $election = Election::create([
            'title' => $validated['title'],
            'type' => 'curso',
            'academic_year_id' => $course->academic_year_id,
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'created_by' => $teacher->id,
        ]);

        $election->courses()->attach($course->id);

        AdministrativeAuditLog::record('teacher.election_create', 'Election', $election->id, [
            'title' => $election->title,
            'course_id' => $course->id,
        ]);

        return redirect()->route('teacher.elections.show', $election)
            ->with('success', 'Votación de curso creada. Agrega ahora las opciones correspondientes.');
    }

    /**
     * Display the course election details and draft options.
     */
    public function show(Election $election): View|RedirectResponse
    {
        $teacher = Auth::user();
        $this->authorizeTeacherAccess($teacher, $election);

        $election->load(['options', 'courses', 'creator']);
        $votesCount = $election->votes()->count();
        $participationsCount = $election->participations()->count();

        return view('teacher.elections.show', compact('election', 'votesCount', 'participationsCount'));
    }

    /**
     * Open the course election.
     */
    public function open(Election $election): RedirectResponse
    {
        $teacher = Auth::user();
        $this->authorizeTeacherAccess($teacher, $election);

        if (! $election->isPending()) {
            return back()->with('error', 'Solo se pueden abrir votaciones en estado pendiente.');
        }

        if ($election->options()->count() < 2) {
            return back()->with('error', 'Para abrir la votación debes registrar al menos dos opciones.');
        }

        $election->update([
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $election->freezeEligibleVoters();

        AdministrativeAuditLog::record('teacher.election_open', 'Election', $election->id);

        return back()->with('success', '¡La votación de curso ha sido abierta!');
    }

    /**
     * Close the course election.
     */
    public function close(Election $election): RedirectResponse
    {
        $teacher = Auth::user();
        $this->authorizeTeacherAccess($teacher, $election);

        if (! $election->isOpen()) {
            return back()->with('error', 'Solo las votaciones abiertas pueden ser cerradas.');
        }

        $election->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        AdministrativeAuditLog::record('teacher.election_close', 'Election', $election->id);

        return back()->with('success', 'La votación ha sido cerrada.');
    }

    private function authorizeTeacherAccess($teacher, Election $election): void
    {
        if ($election->created_by === $teacher->id) {
            return;
        }

        $assignedCourseIds = $teacher->assignedCourses()->pluck('courses.id')->toArray();
        if ($election->courses()->whereIn('courses.id', $assignedCourseIds)->exists()) {
            return;
        }

        abort(403, 'No tienes autorización para gestionar votaciones de otros cursos.');
    }
}
