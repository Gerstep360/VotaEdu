<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    /**
     * Display the student dashboard with open and closed elections.
     */
    public function index(Request $request): View
    {
        $student = $request->user();

        // Get IDs of elections where this student already participated
        $participatedElectionIds = $student->participations()
            ->pluck('election_id')
            ->toArray();

        // Open elections
        $openElections = Election::where('status', 'open')
            ->with(['courses', 'eligibleVoters'])
            ->withCount('options')
            ->latest()
            ->get()->filter(fn ($election) => $election->isUserEligible($student))->values();

        // Closed elections with published results
        $closedElections = Election::where('status', 'closed')
            ->withCount(['options', 'votes'])
            ->latest('closed_at')
            ->get();

        return view('student.dashboard', compact(
            'student',
            'openElections',
            'closedElections',
            'participatedElectionIds'
        ));
    }
}
