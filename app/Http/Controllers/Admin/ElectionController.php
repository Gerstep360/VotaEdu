<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdministrativeAuditLog;
use App\Models\Course;
use App\Models\Election;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ElectionController extends Controller
{
    /**
     * Display a listing of all elections with filters.
     */
    public function index(Request $request): View
    {
        $type = $request->input('type');
        $status = $request->input('status');
        $academicYears = AcademicYear::orderByDesc('year')->get();

        $elections = Election::withCount(['options', 'votes', 'participations'])
            ->with(['academicYear', 'courses'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->input('academic_year_id'), fn ($q, $year) => $q->where('academic_year_id', $year))
            ->when($request->input('search'), fn ($q, $search) => $q->where('title', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.elections.index', compact('elections', 'type', 'status', 'academicYears'));
    }

    /**
     * Show the form for creating a new election draft.
     */
    public function create(): View
    {
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $courses = Course::with('academicYear')->orderBy('name')->get();

        return view('admin.elections.create', compact('academicYears', 'courses'));
    }

    /**
     * Store a newly created election in storage with pending status.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'in:curso,institucional,eleccion_estudiantil'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'description' => ['nullable', 'string'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['exists:courses,id'],
            'scheduled_open_at' => ['nullable', 'date'],
            'scheduled_close_at' => ['nullable', 'date', 'after:scheduled_open_at'],
        ], [
            'title.required' => 'El título de la votación es obligatorio.',
            'scheduled_close_at.after' => 'La fecha de cierre programada debe ser posterior a la de apertura.',
        ]);

        $election = Election::create([
            'title' => $validated['title'],
            'type' => $validated['type'] ?? 'institucional',
            'academic_year_id' => $validated['academic_year_id'] ?? AcademicYear::getActive()?->id,
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'created_by' => Auth::id(),
            'scheduled_open_at' => $validated['scheduled_open_at'] ?? null,
            'scheduled_close_at' => $validated['scheduled_close_at'] ?? null,
        ]);

        if (! empty($validated['course_ids'])) {
            $election->courses()->sync($validated['course_ids']);
        }

        AdministrativeAuditLog::record('admin.election_create', 'Election', $election->id, [
            'title' => $election->title,
            'type' => $election->type,
        ]);

        return redirect()->route('admin.elections.show', $election)
            ->with('success', 'Votación creada como borrador. Ahora puedes agregar las opciones de votación.');
    }

    /**
     * Display the specified election draft and its options.
     */
    public function show(Election $election): View
    {
        $election->load(['options', 'creator', 'courses', 'academicYear', 'eligibleVoters']);
        $votesCount = $election->votes()->count();
        $participationsCount = $election->participations()->count();
        $eligibleCount = $election->eligibleVoters()->count();

        return view('admin.elections.show', compact('election', 'votesCount', 'participationsCount', 'eligibleCount'));
    }

    /**
     * Show the form for editing the election details.
     */
    public function edit(Election $election): View|RedirectResponse
    {
        if (! $election->isPending()) {
            return redirect()->route('admin.elections.show', $election)
                ->with('error', 'Solo se pueden editar votaciones en estado borrador (pendiente).');
        }

        $academicYears = AcademicYear::orderByDesc('year')->get();
        $courses = Course::with('academicYear')->orderBy('name')->get();
        $selectedCourseIds = $election->courses()->pluck('courses.id')->toArray();

        return view('admin.elections.edit', compact('election', 'academicYears', 'courses', 'selectedCourseIds'));
    }

    /**
     * Update the election details in storage.
     */
    public function update(Request $request, Election $election): RedirectResponse
    {
        if (! $election->isPending()) {
            return redirect()->route('admin.elections.show', $election)
                ->with('error', 'Solo se pueden editar votaciones en estado borrador (pendiente).');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'in:curso,institucional,eleccion_estudiantil'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'description' => ['nullable', 'string'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['exists:courses,id'],
            'scheduled_open_at' => ['nullable', 'date'],
            'scheduled_close_at' => ['nullable', 'date', 'after:scheduled_open_at'],
        ]);

        $election->update([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'academic_year_id' => $validated['academic_year_id'] ?? $election->academic_year_id,
            'description' => $validated['description'] ?? null,
            'scheduled_open_at' => $validated['scheduled_open_at'] ?? null,
            'scheduled_close_at' => $validated['scheduled_close_at'] ?? null,
        ]);

        $election->courses()->sync($validated['course_ids'] ?? []);

        AdministrativeAuditLog::record('admin.election_update', 'Election', $election->id);

        return redirect()->route('admin.elections.show', $election)
            ->with('success', 'Detalles de la votación actualizados.');
    }

    /**
     * Delete an election draft. Only allowed if pending and no votes/participations exist.
     */
    public function destroy(Election $election): RedirectResponse
    {
        if (! $election->isPending()) {
            return back()->with('error', 'Solo se pueden eliminar votaciones que se encuentren en estado borrador.');
        }

        if ($election->votes()->exists() || $election->participations()->exists()) {
            return back()->with('error', 'No se puede eliminar una elección que tiene votos registrados.');
        }

        AdministrativeAuditLog::record('admin.election_delete', 'Election', $election->id, ['title' => $election->title]);

        $election->options()->delete();
        $election->courses()->detach();
        $election->delete();

        return redirect()->route('admin.elections.index')
            ->with('success', 'Borrador de votación eliminado correctamente.');
    }

    /**
     * Open an election for voting. Freezes the electoral roll. Requires at least 2 options.
     */
    public function open(Election $election): RedirectResponse
    {
        if (! $election->isPending()) {
            return back()->with('error', 'Solo las votaciones en estado pendiente pueden ser abiertas.');
        }

        if ($election->options()->count() < 2) {
            return back()->with('error', 'Para abrir la votación se deben registrar al menos dos opciones.');
        }

        $election->update([
            'status' => 'open',
            'opened_at' => now(),
        ]);

        // Freeze eligible voters snapshot (Section 6.2)
        $election->freezeEligibleVoters();

        AdministrativeAuditLog::record('admin.election_open', 'Election', $election->id);

        return redirect()->route('admin.elections.show', $election)
            ->with('success', '¡La votación ha sido abierta! El padrón electoral ha sido congelado.');
    }

    /**
     * Close an election. Freezes voting and enables results.
     */
    public function close(Election $election): RedirectResponse
    {
        if (! $election->isOpen()) {
            return back()->with('error', 'Solo las votaciones abiertas pueden ser cerradas.');
        }

        $election->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        AdministrativeAuditLog::record('admin.election_close', 'Election', $election->id);

        return redirect()->route('admin.elections.show', $election)
            ->with('success', 'La votación ha sido cerrada. Los resultados ya se encuentran disponibles.');
    }

    /**
     * Cancel an election with mandatory reason (Section 8).
     */
    public function cancel(Request $request, Election $election): RedirectResponse
    {
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5'],
        ], [
            'cancellation_reason.required' => 'Debes ingresar el motivo justificado de la anulación.',
            'cancellation_reason.min' => 'El motivo debe contener al menos 5 caracteres.',
        ]);

        $election->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'closed_at' => now(),
        ]);

        AdministrativeAuditLog::record('admin.election_cancel', 'Election', $election->id, [
            'reason' => $validated['cancellation_reason'],
        ]);

        return redirect()->route('admin.elections.show', $election)
            ->with('warning', 'La votación ha sido anulada con motivo registrado en auditoría.');
    }

    /**
     * Duplicate an election into a new draft WITHOUT copying votes or participations (Section 8).
     */
    public function duplicate(Election $election): RedirectResponse
    {
        $newElection = Election::create([
            'title' => $election->title.' (Copia)',
            'type' => $election->type,
            'academic_year_id' => $election->academic_year_id,
            'description' => $election->description,
            'status' => 'pending',
            'created_by' => Auth::id(),
            'parent_election_id' => $election->id,
        ]);

        // Copy course relations
        $courseIds = $election->courses()->pluck('courses.id')->toArray();
        $newElection->courses()->sync($courseIds);

        // Copy options
        foreach ($election->options as $option) {
            Option::create([
                'election_id' => $newElection->id,
                'name' => $option->name,
                'description' => $option->description,
                'photo_path' => $option->photo_path,
                'members' => $option->members,
                'proposals' => $option->proposals,
            ]);
        }

        AdministrativeAuditLog::record('admin.election_duplicate', 'Election', $newElection->id, [
            'source_election_id' => $election->id,
        ]);

        return redirect()->route('admin.elections.show', $newElection)
            ->with('success', 'Votación duplicada exitosamente como nuevo borrador (sin votos ni participaciones).');
    }
}
