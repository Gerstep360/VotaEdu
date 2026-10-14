<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Election;
use App\Models\ElectionEligibleVoter;
use App\Models\Option;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoterParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpansionInterfaceAndPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private User $teacher;

    private Course $course;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->year = AcademicYear::create(['year' => 2026, 'name' => 'Gestión 2026', 'is_active' => true]);
        $this->course = Course::create(['academic_year_id' => $this->year->id, 'grade' => '6to de Secundaria', 'parallel' => 'A', 'name' => '6to de Secundaria A']);
        $this->admin = $this->account('admin', 'admin', '10000001');
        $this->student = $this->account('student', 'student', '77889900');
        $this->teacher = $this->account('docente', 'teacher', '30000003');
        $this->teacher->assignedCourses()->attach($this->course);
    }

    private function account(string $role, string $username, string $ci): User
    {
        return User::create(['name' => 'Persona ficticia '.$username, 'username' => $username, 'ci' => $ci, 'password' => 'password123', 'role' => $role, 'is_active' => true, 'must_change_password' => false]);
    }

    private function election(string $status = 'closed', array $distribution = [3, 2, 0]): Election
    {
        $election = Election::create(['title' => 'Elección de representación estudiantil - demostración', 'type' => 'eleccion_estudiantil', 'academic_year_id' => $this->year->id, 'created_by' => $this->admin->id, 'status' => $status, 'opened_at' => now()->subDays(2), 'closed_at' => $status === 'closed' ? now() : null]);
        $election->courses()->attach($this->course);
        foreach ($distribution as $index => $count) {
            $option = $election->options()->create(['name' => ['Frente Jóvenes por la educación', 'Frente Integración y Tecnología', 'Propuesta de biblioteca escolar'][$index] ?? 'Opción '.($index + 1)]);
            for ($number = 0; $number < $count; $number++) {
                Vote::create(['election_id' => $election->id, 'option_id' => $option->id]);
                $participant = $this->account('student', 'voter'.$index.'-'.$number, '900'.$index.$number.'123');
                VoterParticipation::create(['election_id' => $election->id, 'user_id' => $participant->id]);
                ElectionEligibleVoter::create(['election_id' => $election->id, 'user_id' => $participant->id]);
            }
        }
        ElectionEligibleVoter::create(['election_id' => $election->id, 'user_id' => $this->student->id]);

        return $election;
    }

    private function saveFixture(string $name, string $content): void
    {
        if (getenv('VOTAEDU_EXPORT_FIXTURES') !== '1') {
            return;
        }
        $directory = base_path('output/pdf');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($directory.'/'.$name.'.pdf', $content);
    }

    public function test_expanded_pages_render_and_display_complete_ci(): void
    {
        $this->actingAs($this->admin);
        foreach (['/admin', '/admin/academic-years', '/admin/courses', '/admin/teachers', '/admin/teachers/create', '/admin/teachers/'.$this->teacher->id.'/edit', '/admin/audit-logs', '/profile', '/change-password', '/admin/elections/create'] as $path) {
            $this->get($path)->assertOk()->assertSee('VotaEdu');
        }
        $this->get('/admin/students')->assertSee('77889900')->assertDontSee('7788****');
        $this->get('/admin/teachers')->assertSee('30000003');
        $this->actingAs($this->student)->get('/profile')->assertOk()->assertSee('77889900');
        $this->student->update(['must_change_password' => true]);
        $this->get('/change-password')->assertOk()->assertSee('Haz tuyo este acceso.');
        $this->get('/student')->assertRedirect('/change-password');
        $this->actingAs($this->teacher)->get('/teacher')->assertOk()->assertSee('6to de Secundaria A');
        $this->get('/teacher/elections/create')->assertOk();
    }

    public function test_pdf_download_is_a_real_document_with_restricted_access(): void
    {
        $election = $this->election();
        $response = $this->actingAs($this->admin)->get(route('results.pdf', $election));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->saveFixture('acta-votaedu-demostracion', $response->getContent());
        $this->get(route('results.acta', $election))->assertOk()->assertSee('60,00 %')->assertSee('40,00 %')->assertSee('0,00 %');
        $this->actingAs($this->student)->get(route('results.pdf', $election))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('results.pdf', $election))->assertOk();
        $election->courses()->detach();
        $this->get(route('results.pdf', $election))->assertForbidden();
        $this->get(route('results.show', $election))->assertForbidden();
        $election->update(['status' => 'open']);
        $this->actingAs($this->admin)->get(route('results.pdf', $election))->assertForbidden();
    }

    public function test_zero_vote_and_tie_actas_render_without_arbitrary_winner(): void
    {
        $election = $this->election('closed', [0, 0]);
        $this->actingAs($this->admin)->get(route('results.acta', $election))->assertOk()->assertSee('No se registraron votos');
        $response = $this->get(route('results.pdf', $election))->assertOk();
        $this->saveFixture('acta-votaedu-sin-votos', $response->getContent());
        foreach ($election->options as $index => $option) {
            Vote::create(['election_id' => $election->id, 'option_id' => $option->id]);
            $participant = $this->account('student', 'tie'.$index, '8000000'.$index);
            VoterParticipation::create(['election_id' => $election->id, 'user_id' => $participant->id]);
            ElectionEligibleVoter::create(['election_id' => $election->id, 'user_id' => $participant->id]);
        }
        $this->get(route('results.acta', $election))->assertOk()->assertSee('Empate')->assertDontSee('OPCIÓN MÁS VOTADA');
        $response = $this->get(route('results.pdf', $election))->assertOk();
        $this->saveFixture('acta-votaedu-empate', $response->getContent());
    }

    public function test_large_acta_contains_many_options_and_long_names(): void
    {
        $election = $this->election('closed', [0, 0]);
        $election->update(['title' => 'Elección de representantes y propuestas de la comunidad educativa para fortalecer la educación técnica, científica y humanística en la gestión académica 2026']);
        for ($number = 1; $number <= 35; $number++) {
            $election->options()->create(['name' => 'Propuesta '.$number.': Biblioteca, integración, ciencia y tecnología para la comunidad estudiantil de Maestro Pitágoras II']);
        }
        $response = $this->actingAs($this->admin)->get(route('results.pdf', $election))->assertOk();
        $this->saveFixture('acta-votaedu-multipagina', $response->getContent());
    }

    public function test_teacher_option_form_persists_candidate_fields_and_blocks_other_courses(): void
    {
        Storage::fake('public');
        $election = $this->election('pending', [0, 0]);
        $election->update(['created_by' => $this->teacher->id]);
        $this->actingAs($this->teacher)->get(route('teacher.elections.show', $election))->assertOk();
        $this->post(route('teacher.options.store', $election), ['name' => 'Nueva candidatura', 'members' => 'María y José', 'proposals' => 'Talleres de robótica', 'photo' => UploadedFile::fake()->image('candidate.png')])->assertSessionHas('success');
        $option = Option::where('name', 'Nueva candidatura')->firstOrFail();
        $this->assertSame('María y José', $option->members);
        Storage::disk('public')->assertExists($option->photo_path);
        $election->update(['created_by' => $this->admin->id]);
        $election->courses()->detach();
        $this->patch(route('teacher.options.update', $option), ['name' => 'No autorizado'])->assertForbidden();
    }
}
