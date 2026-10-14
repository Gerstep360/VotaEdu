<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicYear::firstOrCreate(
            ['year' => 2026],
            [
                'name' => 'Gestión Académica 2026',
                'is_active' => true,
            ]
        );
    }
}
