<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdministrativeAuditLog;
use App\Models\Course;
use App\Models\Election;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with comprehensive system metrics.
     */
    public function index(): View
    {
        $totalStudents = User::where(fn ($q) => $q->where('role', 'student')->orWhere('role', 'estudiante'))->count();
        $activeStudents = User::where(fn ($q) => $q->where('role', 'student')->orWhere('role', 'estudiante'))->where('is_active', true)->count();
        $inactiveStudents = $totalStudents - $activeStudents;

        $totalTeachers = User::where(fn ($q) => $q->where('role', 'docente')->orWhere('role', 'teacher'))->count();
        $totalCourses = Course::count();
        $activeAcademicYear = AcademicYear::getActive();

        $pendingElections = Election::whereIn('status', ['pending', 'borrador'])->count();
        $openElections = Election::whereIn('status', ['open', 'abierta'])->count();
        $closedElections = Election::whereIn('status', ['closed', 'cerrada'])->count();
        $cancelledElections = Election::whereIn('status', ['cancelled', 'anulada'])->count();

        // Elections by type
        $courseElectionsCount = Election::where('type', 'curso')->count();
        $institutionalElectionsCount = Election::where('type', 'institucional')->count();
        $studentElectionsCount = Election::where('type', 'eleccion_estudiantil')->count();

        // Recent elections to display on dashboard
        $recentElections = Election::withCount(['options', 'eligibleVoters', 'participations'])
            ->with(['academicYear', 'courses'])
            ->latest()
            ->take(5)
            ->get();

        // Recent audit logs
        $recentLogs = AdministrativeAuditLog::with('user')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalStudents',
            'activeStudents',
            'inactiveStudents',
            'totalTeachers',
            'totalCourses',
            'activeAcademicYear',
            'pendingElections',
            'openElections',
            'closedElections',
            'cancelledElections',
            'courseElectionsCount',
            'institutionalElectionsCount',
            'studentElectionsCount',
            'recentElections',
            'recentLogs'
        ));
    }
}
