<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    /**
     * Display the teacher dashboard with assigned courses and course elections.
     */
    public function index(): View
    {
        $teacher = Auth::user();

        $assignedCourses = $teacher->assignedCourses()
            ->with(['academicYear', 'enrollments'])
            ->get();

        $assignedCourseIds = $assignedCourses->pluck('id')->toArray();

        // Elections created by this teacher or linked to their assigned courses
        $elections = Election::where(function ($q) use ($teacher, $assignedCourseIds) {
            $q->where('created_by', $teacher->id)
                ->orWhereHas('courses', function ($cq) use ($assignedCourseIds) {
                    $cq->whereIn('courses.id', $assignedCourseIds);
                });
        })
            ->withCount(['options', 'votes', 'participations'])
            ->with('courses')
            ->latest()
            ->paginate(10);

        return view('teacher.dashboard', compact('teacher', 'assignedCourses', 'elections'));
    }
}
