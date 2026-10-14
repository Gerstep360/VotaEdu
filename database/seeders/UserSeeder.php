<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Prof. Mario Vargas (Admin)',
                'ci' => '10000001',
                'first_name' => 'Mario',
                'paternal_surname' => 'Vargas',
                'maternal_surname' => 'Choque',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 2. Director
        User::updateOrCreate(
            ['username' => 'director'],
            [
                'name' => 'Lic. Roberto Mendoza Soliz',
                'ci' => '20000002',
                'first_name' => 'Roberto',
                'paternal_surname' => 'Mendoza',
                'maternal_surname' => 'Soliz',
                'password' => Hash::make('director123'),
                'role' => 'director',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 3. Teachers (Docentes)
        User::updateOrCreate(
            ['username' => 'docente1'],
            [
                'name' => 'Prof. Carlos Fernandez Rojas',
                'ci' => '30000003',
                'first_name' => 'Carlos',
                'paternal_surname' => 'Fernandez',
                'maternal_surname' => 'Rojas',
                'password' => Hash::make('docente123'),
                'role' => 'docente',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        User::updateOrCreate(
            ['username' => 'docente2'],
            [
                'name' => 'Prof. Elena Gutierrez Roca',
                'ci' => '30000004',
                'first_name' => 'Elena',
                'paternal_surname' => 'Gutierrez',
                'maternal_surname' => 'Roca',
                'password' => Hash::make('docente123'),
                'role' => 'docente',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 4. Students with CI and temporary password formula (Section 3.1)
        $studentsData = [
            ['first' => 'Carlos', 'pat' => 'Mamani', 'mat' => 'Choque', 'ci' => '78901234', 'username' => 'carlos.mamani', 'course' => '6to de Secundaria', 'parallel' => 'A', 'active' => true, 'must_change' => true],
            ['first' => 'Lucía', 'pat' => 'Rojas', 'mat' => 'Fernandez', 'ci' => '89012345', 'username' => 'lucia.rojas', 'course' => '6to de Secundaria', 'parallel' => 'A', 'active' => true, 'must_change' => false],
            ['first' => 'Alejandro', 'pat' => 'Quispe', 'mat' => 'Flores', 'ci' => '90123456', 'username' => 'alejandro.quispe', 'course' => '6to de Secundaria', 'parallel' => 'B', 'active' => true, 'must_change' => true],
            ['first' => 'Valeria', 'pat' => 'Morales', 'mat' => 'Gutierrez', 'ci' => '12345670', 'username' => 'valeria.morales', 'course' => '6to de Secundaria', 'parallel' => 'B', 'active' => true, 'must_change' => false],
            ['first' => 'Gabriel', 'pat' => 'Torrico', 'mat' => 'Arteaga', 'ci' => '23456781', 'username' => 'gabriel.torrico', 'course' => '5to de Secundaria', 'parallel' => 'A', 'active' => true, 'must_change' => true],
            ['first' => 'Daniela', 'pat' => 'Ortiz', 'mat' => 'Suarez', 'ci' => '34567892', 'username' => 'daniela.ortiz', 'course' => '5to de Secundaria', 'parallel' => 'A', 'active' => true, 'must_change' => false],
            ['first' => 'Rodrigo', 'pat' => 'Chavez', 'mat' => 'Mendoza', 'ci' => '45678903', 'username' => 'rodrigo.chavez', 'course' => '5to de Secundaria', 'parallel' => 'B', 'active' => true, 'must_change' => true],
            ['first' => 'Sofía', 'pat' => 'Villarroel', 'mat' => 'Roca', 'ci' => '56789014', 'username' => 'sofia.villarroel', 'course' => '5to de Secundaria', 'parallel' => 'B', 'active' => true, 'must_change' => false],
            ['first' => 'Mateo', 'pat' => 'Colque', 'mat' => 'Pinto', 'ci' => '67890125', 'username' => 'mateo.colque', 'course' => '4to de Secundaria', 'parallel' => 'A', 'active' => true, 'must_change' => true],
            ['first' => 'Camila', 'pat' => 'Justiniano', 'mat' => 'Paz', 'ci' => '78901236', 'username' => 'camila.justiniano', 'course' => '4to de Secundaria', 'parallel' => 'A', 'active' => true, 'must_change' => false],
            ['first' => 'Lucas', 'pat' => 'Bejarano', 'mat' => 'Soliz', 'ci' => '89012347', 'username' => 'lucas.bejarano', 'course' => '4to de Secundaria', 'parallel' => 'B', 'active' => true, 'must_change' => true],
            ['first' => 'Nicole', 'pat' => 'Arteaga', 'mat' => 'Vaca', 'ci' => '90123458', 'username' => 'nicole.arteaga', 'course' => '4to de Secundaria', 'parallel' => 'B', 'active' => false, 'must_change' => false],
        ];

        foreach ($studentsData as $st) {
            $tempPass = User::generateTemporaryPassword($st['pat'], $st['mat'], $st['ci']);
            $fullName = $st['first'].' '.$st['pat'].' '.$st['mat'];

            User::updateOrCreate(
                ['username' => $st['username']],
                [
                    'name' => $fullName,
                    'ci' => $st['ci'],
                    'first_name' => $st['first'],
                    'paternal_surname' => $st['pat'],
                    'maternal_surname' => $st['mat'],
                    'password' => Hash::make($tempPass), // Matches temporary formula
                    'role' => 'student',
                    'course' => $st['course'],
                    'parallel' => $st['parallel'],
                    'is_active' => $st['active'],
                    'must_change_password' => $st['must_change'],
                ]
            );
        }
    }
}
