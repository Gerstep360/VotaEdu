<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database using modular seeders.
     */
    public function run(): void
    {
        $this->call([
            AcademicYearSeeder::class,
            CourseSeeder::class,
            UserSeeder::class,
            TeacherAssignmentSeeder::class,
            CourseEnrollmentSeeder::class,
            ElectionSeeder::class,
        ]);
    }
}
