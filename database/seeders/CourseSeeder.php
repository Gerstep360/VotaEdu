<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = AcademicYear::where('year', 2026)->first() ?? AcademicYear::create([
            'year' => 2026,
            'name' => 'Gestión Académica 2026',
            'is_active' => true,
        ]);

        $courses = [
            ['grade' => '6to de Secundaria', 'parallel' => 'A', 'name' => '6to de Secundaria A'],
            ['grade' => '6to de Secundaria', 'parallel' => 'B', 'name' => '6to de Secundaria B'],
            ['grade' => '5to de Secundaria', 'parallel' => 'A', 'name' => '5to de Secundaria A'],
            ['grade' => '5to de Secundaria', 'parallel' => 'B', 'name' => '5to de Secundaria B'],
            ['grade' => '4to de Secundaria', 'parallel' => 'A', 'name' => '4to de Secundaria A'],
            ['grade' => '4to de Secundaria', 'parallel' => 'B', 'name' => '4to de Secundaria B'],
        ];

        foreach ($courses as $c) {
            Course::firstOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'grade' => $c['grade'],
                    'parallel' => $c['parallel'],
                ],
                ['name' => $c['name']]
            );
        }
    }
}
