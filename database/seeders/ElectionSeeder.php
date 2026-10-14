<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Election;
use App\Models\Option;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoterParticipation;
use Illuminate\Database\Seeder;

class ElectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $director = User::where('username', 'director')->first();
        $teacher = User::where('username', 'docente1')->first();
        $year = AcademicYear::where('year', 2026)->first();

        // 1. Institucional (Draft / Pending)
        $pending = Election::updateOrCreate(
            ['title' => 'Elección de Actividad de Aniversario 2026'],
            [
                'type' => 'institucional',
                'academic_year_id' => $year?->id,
                'description' => 'Selección de la actividad central para conmemorar el aniversario de la U.E. Técnico Humanístico Maestro Pitágoras II.',
                'status' => 'pending',
                'created_by' => $director?->id ?? $admin?->id,
            ]
        );

        $pendingOptions = [
            ['name' => 'Feria de Ciencia, Tecnología y Robótica BTH', 'description' => 'Demostración de proyectos de software, hardware y robótica.'],
            ['name' => 'Campeonato Deportivo Intercolegial', 'description' => 'Torneo relámpago de fútbol de salón, voleibol y básquetbol.'],
            ['name' => 'Festival Artístico y Folclórico Cultural', 'description' => 'Muestra de bailes tradicionales bolivianos y concurso musical.'],
        ];

        foreach ($pendingOptions as $opt) {
            Option::firstOrCreate(['election_id' => $pending->id, 'name' => $opt['name']], $opt);
        }

        // 2. Elección Estudiantil (Open / Activa)
        $open = Election::updateOrCreate(
            ['title' => 'Elección de Centro de Estudiantes 2026'],
            [
                'type' => 'eleccion_estudiantil',
                'academic_year_id' => $year?->id,
                'description' => 'Votación democrática para elegir la nueva directiva del Centro de Estudiantes de la gestión 2026.',
                'status' => 'open',
                'opened_at' => now()->subDay(),
                'created_by' => $admin?->id,
            ]
        );

        $studentOptions = [
            [
                'name' => 'Frente Liderazgo Estudiantil (FLE)',
                'description' => 'Plancha estudiantil enfocada en actividades recreativas, talleres de oratoria y apoyo social.',
                'members' => 'Presidente: Carlos Mamani; Vicepresidente: Lucía Rojas; Secretaría: Alejandro Quispe.',
                'proposals' => '1. Organización de campeonatos intercursos. 2. Implementación de buzón estudiantil. 3. Ferias de talento.',
            ],
            [
                'name' => 'Frente Integración Pitágoras (FIP)',
                'description' => 'Propuesta centrada en el fortalecimiento de laboratorios de computación y bienestar escolar.',
                'members' => 'Presidente: Valeria Morales; Vicepresidente: Gabriel Torrico; Secretaría: Daniela Ortiz.',
                'proposals' => '1. Mantenimiento de computadoras BTH. 2. Red de wifi para investigación escolar. 3. Jornadas de debate.',
            ],
            [
                'name' => 'Frente Renovación y Juventud (FRJ)',
                'description' => 'Iniciativas de tutorías entre pares y organización de brigadas ecológicas.',
                'members' => 'Presidente: Rodrigo Chávez; Vicepresidente: Sofía Villarroel; Secretaría: Mateo Colque.',
                'proposals' => '1. Clases de apoyo académico entre pares. 2. Campañas de reciclaje. 3. Festival gastronómico.',
            ],
        ];

        $createdOpenOptions = [];
        foreach ($studentOptions as $opt) {
            $createdOpenOptions[] = Option::firstOrCreate(['election_id' => $open->id, 'name' => $opt['name']], $opt);
        }

        // Freeze eligible voters for open election
        $open->freezeEligibleVoters();

        // Sample votes for Carlos and Lucía if not already voted
        $carlos = User::where('username', 'carlos.mamani')->first();
        $lucia = User::where('username', 'lucia.rojas')->first();

        if ($carlos && ! $carlos->hasVotedIn($open)) {
            Vote::create(['election_id' => $open->id, 'option_id' => $createdOpenOptions[0]->id]);
            VoterParticipation::create(['election_id' => $open->id, 'user_id' => $carlos->id]);
        }

        if ($lucia && ! $lucia->hasVotedIn($open)) {
            Vote::create(['election_id' => $open->id, 'option_id' => $createdOpenOptions[1]->id]);
            VoterParticipation::create(['election_id' => $open->id, 'user_id' => $lucia->id]);
        }

        // 3. Votación de Curso (Closed)
        $course6A = Course::where('grade', '6to de Secundaria')->where('parallel', 'A')->first();
        $closed = Election::updateOrCreate(
            ['title' => 'Elección de Delegado de Curso 6to de Secundaria A'],
            [
                'type' => 'curso',
                'academic_year_id' => $year?->id,
                'description' => 'Elección del delegado de aula para representar a 6to de Secundaria A en el consejo escolar.',
                'status' => 'closed',
                'opened_at' => now()->subDays(3),
                'closed_at' => now()->subDay(),
                'created_by' => $teacher?->id ?? $admin?->id,
            ]
        );

        if ($course6A && ! $closed->courses()->where('courses.id', $course6A->id)->exists()) {
            $closed->courses()->attach($course6A->id);
        }

        $courseOptions = [
            ['name' => 'Jhasmany Choque Quispe', 'description' => 'Propuesta: Liderar actividades académicas y olimpiadas científicas.'],
            ['name' => 'Mariana Salvatierra Paz', 'description' => 'Propuesta: Coordinar comités de convivencia y eventos de despedida.'],
        ];

        $createdClosedOptions = [];
        foreach ($courseOptions as $opt) {
            $createdClosedOptions[] = Option::firstOrCreate(['election_id' => $closed->id, 'name' => $opt['name']], $opt);
        }

        // Seed consistent votes and participations for closed course election if empty
        if ($closed->votes()->count() === 0) {
            $closed->freezeEligibleVoters();
            $eligibleStudents = $closed->eligibleVoters()->with('user')->get()->pluck('user')->filter();

            $i = 0;
            foreach ($eligibleStudents as $student) {
                $chosenOpt = ($i % 3 === 0) ? $createdClosedOptions[1]->id : $createdClosedOptions[0]->id;
                Vote::create(['election_id' => $closed->id, 'option_id' => $chosenOpt]);
                VoterParticipation::create(['election_id' => $closed->id, 'user_id' => $student->id]);
                $i++;
            }
        }
    }
}
