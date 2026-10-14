<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Election;
use App\Models\Option;
use App\Models\TeacherCourseAssignment;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoterParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AmpliacionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $year;

    protected Course $course6A;

    protected Course $course6B;

    protected User $admin;

    protected User $director;

    protected User $teacher;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->year = AcademicYear::create([
            'year' => 2026,
            'name' => 'Gestión 2026',
            'is_active' => true,
        ]);

        $this->course6A = Course::create([
            'academic_year_id' => $this->year->id,
            'grade' => '6to de Secundaria',
            'parallel' => 'A',
            'name' => '6to de Secundaria A',
        ]);

        $this->course6B = Course::create([
            'academic_year_id' => $this->year->id,
            'grade' => '6to de Secundaria',
            'parallel' => 'B',
            'name' => '6to de Secundaria B',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'ci' => '10000001',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->director = User::create([
            'name' => 'Director User',
            'username' => 'director',
            'ci' => '20000002',
            'password' => Hash::make('director123'),
            'role' => 'director',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->teacher = User::create([
            'name' => 'Profesor Carlos',
            'username' => 'docente1',
            'ci' => '30000003',
            'password' => Hash::make('docente123'),
            'role' => 'docente',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Assign teacher ONLY to course 6A
        TeacherCourseAssignment::create([
            'course_id' => $this->course6A->id,
            'user_id' => $this->teacher->id,
        ]);

        // Student with temporary password in 6A
        $tempPassword = User::generateTemporaryPassword('Rojas', 'Cruz', '77788899');
        $this->student = User::create([
            'name' => 'Juan Rojas Cruz',
            'username' => '77788899',
            'ci' => '77788899',
            'first_name' => 'Juan',
            'paternal_surname' => 'Rojas',
            'maternal_surname' => 'Cruz',
            'password' => Hash::make($tempPassword),
            'role' => 'student',
            'course' => '6to de Secundaria',
            'parallel' => 'A',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        CourseEnrollment::create([
            'course_id' => $this->course6A->id,
            'user_id' => $this->student->id,
        ]);
    }

    /** Test 1: Temporary password formula complies with Section 3.1 rule */
    public function test_temporary_password_formula(): void
    {
        $formula = User::generateTemporaryPassword('Rojas', 'Cruz', '12345678');
        $this->assertEquals('Rc.12345678', $formula);

        // Missing maternal surname
        $formulaNoMat = User::generateTemporaryPassword('Mamani', null, '88889999');
        $this->assertEquals('M.88889999', $formulaNoMat);
    }

    /** Test 2: Login using CI and forced password change redirection */
    public function test_login_by_ci_and_forced_password_change(): void
    {
        $response = $this->post('/login', [
            'username' => '77788899', // logging in with CI
            'password' => 'Rc.77788899',
        ]);

        $this->assertAuthenticatedAs($this->student);
        $response->assertRedirect(route('password.change'));

        // Attempting to access dashboard is redirected to password.change
        $dashboardResponse = $this->actingAs($this->student)->get('/student');
        $dashboardResponse->assertRedirect(route('password.change'));

        // Change password
        $changeResponse = $this->actingAs($this->student)->post('/change-password', [
            'password' => 'nuevaClaveSegura123',
            'password_confirmation' => 'nuevaClaveSegura123',
        ]);

        $this->student->refresh();
        $this->assertFalse($this->student->must_change_password);
        $changeResponse->assertRedirect(route('student.dashboard'));
    }

    /** Test 3: Teacher can create course election for assigned course but not for unassigned course */
    public function test_teacher_course_scoping(): void
    {
        // Allowed: 6A
        $allowedResponse = $this->actingAs($this->teacher)->post('/teacher/elections', [
            'title' => 'Votación de Aula 6A',
            'course_id' => $this->course6A->id,
        ]);
        $allowedResponse->assertSessionHas('success');
        $this->assertDatabaseHas('elections', [
            'title' => 'Votación de Aula 6A',
            'type' => 'curso',
            'created_by' => $this->teacher->id,
        ]);

        // Denied: 6B (not assigned to this teacher)
        $deniedResponse = $this->actingAs($this->teacher)->post('/teacher/elections', [
            'title' => 'Votación de Aula 6B',
            'course_id' => $this->course6B->id,
        ]);
        $deniedResponse->assertSessionHasErrors('course_id');
    }

    /** Test 4: Electoral roll is frozen on opening and non-eligible student cannot vote */
    public function test_electoral_roll_freezing_and_eligibility(): void
    {
        $election = Election::create([
            'title' => 'Elección de 6to A',
            'type' => 'curso',
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);
        $election->courses()->attach($this->course6A->id);

        Option::create(['election_id' => $election->id, 'name' => 'Candidato 1']);
        Option::create(['election_id' => $election->id, 'name' => 'Candidato 2']);

        // Student outside 6A (in 6B)
        $outsideStudent = User::create([
            'name' => 'Alumno 6B',
            'username' => 'alumno6b',
            'ci' => '99990000',
            'password' => Hash::make('pass123'),
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        CourseEnrollment::create([
            'course_id' => $this->course6B->id,
            'user_id' => $outsideStudent->id,
        ]);

        // Open election via admin controller
        $this->actingAs($this->admin)->post("/admin/elections/{$election->id}/open");

        $election->refresh();
        $this->assertTrue($election->isOpen());

        // Eligible voters snapshot has student 6A, but not outsideStudent
        $this->assertTrue($election->isUserEligible($this->student));
        $this->assertFalse($election->isUserEligible($outsideStudent));

        // Outside student attempts to vote
        $voteAttempt = $this->actingAs($outsideStudent)->post("/elections/{$election->id}/vote", [
            'option_id' => $election->options->first()->id,
        ]);
        $voteAttempt->assertSessionHas('error');
        $this->assertEquals(0, Vote::where('election_id', $election->id)->count());
    }

    /** Test 5: Duplicating an election does NOT copy votes or participations */
    public function test_election_duplication_does_not_copy_votes(): void
    {
        $original = Election::create([
            'title' => 'Elección Original',
            'type' => 'institucional',
            'status' => 'closed',
            'created_by' => $this->admin->id,
        ]);
        $opt = Option::create(['election_id' => $original->id, 'name' => 'Opción']);
        Option::create(['election_id' => $original->id, 'name' => 'Opción 2']);

        Vote::create(['election_id' => $original->id, 'option_id' => $opt->id]);
        VoterParticipation::create(['election_id' => $original->id, 'user_id' => $this->student->id]);

        $this->actingAs($this->admin)->post("/admin/elections/{$original->id}/duplicate");

        $duplicated = Election::where('parent_election_id', $original->id)->first();
        $this->assertNotNull($duplicated);
        $this->assertEquals('pending', $duplicated->status);
        $this->assertEquals(0, $duplicated->votes()->count());
        $this->assertEquals(0, $duplicated->participations()->count());
        $this->assertEquals(2, $duplicated->options()->count());
    }

    /** Test 6: Cancelling an election requires reason and marks status */
    public function test_cancelling_election_requires_reason(): void
    {
        $election = Election::create([
            'title' => 'Elección Por Anular',
            'type' => 'institucional',
            'status' => 'open',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/elections/{$election->id}/cancel", [
            'cancellation_reason' => 'Suspensión por fuerza mayor meteorológica.',
        ]);

        $response->assertSessionHas('warning');
        $election->refresh();
        $this->assertEquals('cancelled', $election->status);
        $this->assertEquals('Suspensión por fuerza mayor meteorológica.', $election->cancellation_reason);
    }
}
