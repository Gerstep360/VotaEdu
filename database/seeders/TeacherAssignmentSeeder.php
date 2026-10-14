<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\TeacherCourseAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teacher1 = User::where('username', 'docente1')->first();
        $teacher2 = User::where('username', 'docente2')->first();

        if ($teacher1) {
            $courses6 = Course::where('grade', '6to de Secundaria')->get();
            foreach ($courses6 as $c) {
                TeacherCourseAssignment::firstOrCreate([
                    'course_id' => $c->id,
                    'user_id' => $teacher1->id,
                ]);
            }
        }

        if ($teacher2) {
            $courses5 = Course::where('grade', '5to de Secundaria')->get();
            foreach ($courses5 as $c) {
                TeacherCourseAssignment::firstOrCreate([
                    'course_id' => $c->id,
                    'user_id' => $teacher2->id,
                ]);
            }
        }
    }
}
