<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseEnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where(function ($q) {
            $q->where('role', 'student')->orWhere('role', 'estudiante');
        })->whereNotNull('course')->whereNotNull('parallel')->get();

        foreach ($students as $student) {
            $course = Course::where('grade', $student->course)
                ->where('parallel', $student->parallel)
                ->first();

            if ($course) {
                CourseEnrollment::firstOrCreate([
                    'course_id' => $course->id,
                    'user_id' => $student->id,
                ]);
            }
        }
    }
}
