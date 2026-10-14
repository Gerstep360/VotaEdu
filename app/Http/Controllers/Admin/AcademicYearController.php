<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdministrativeAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::withCount('courses')
            ->orderByDesc('year')
            ->paginate(10);

        return view('admin.academic-years.index', compact('academicYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2099', 'unique:academic_years,year'],
            'name' => ['required', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_active')) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $year = AcademicYear::create([
            'year' => $validated['year'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AdministrativeAuditLog::record('admin.academic_year_create', 'AcademicYear', $year->id, ['year' => $year->year]);

        return redirect()->route('admin.academic-years.index')
            ->with('success', "Gestión académica {$year->year} creada exitosamente.");
    }

    public function activate(AcademicYear $academicYear): RedirectResponse
    {
        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        $academicYear->update(['is_active' => true]);

        AdministrativeAuditLog::record('admin.academic_year_activate', 'AcademicYear', $academicYear->id, ['year' => $academicYear->year]);

        return redirect()->route('admin.academic-years.index')
            ->with('success', "La gestión {$academicYear->year} ha sido establecida como activa.");
    }
}
