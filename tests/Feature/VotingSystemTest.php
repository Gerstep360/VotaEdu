<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\Option;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoterParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VotingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected User $inactiveStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Profesor Admin',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->student = User::create([
            'name' => 'Estudiante Activo',
            'username' => 'estudiante1',
            'password' => Hash::make('pass123'),
            'role' => 'student',
            'course' => '6to de Secundaria',
            'parallel' => 'A',
            'is_active' => true,
        ]);

        $this->inactiveStudent = User::create([
            'name' => 'Estudiante Inactivo',
            'username' => 'inactivo',
            'password' => Hash::make('pass123'),
            'role' => 'student',
            'is_active' => false,
        ]);
    }

    /** T01: Valid admin login redirects to admin dashboard */
    public function test_t01_valid_admin_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    /** T02: Valid student login redirects to student dashboard */
    public function test_t02_valid_student_login(): void
    {
        $response = $this->post('/login', [
            'username' => 'estudiante1',
            'password' => 'pass123',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($this->student);
    }

    /** T03: Wrong password and inactive user rejected */
    public function test_t03_wrong_password_and_inactive_user_rejected(): void
    {
        // Wrong password
        $response = $this->post('/login', [
            'username' => 'estudiante1',
            'password' => 'wrongpassword',
        ]);
        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        // Inactive user
        $responseInactive = $this->post('/login', [
            'username' => 'inactivo',
            'password' => 'pass123',
        ]);
        $responseInactive->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /** T04: Student attempting to access /admin gets 403 Forbidden */
    public function test_t04_student_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->student)->get('/admin');

        $response->assertStatus(403);
    }

    /** T05: Admin creates an election draft and options */
    public function test_t05_admin_creates_election_draft_and_options(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/elections', [
            'title' => 'Elección de Prueba 2026',
            'description' => 'Descripción de prueba',
        ]);

        $election = Election::first();
        $this->assertNotNull($election);
        $this->assertEquals('pending', $election->status);
        $this->assertEquals($this->admin->id, $election->created_by);

        // Add option
        $optResponse = $this->actingAs($this->admin)->post("/admin/elections/{$election->id}/options", [
            'name' => 'Opción Alfa',
            'description' => 'Primera opción',
        ]);
        $optResponse->assertSessionHas('success');
        $this->assertDatabaseHas('options', [
            'election_id' => $election->id,
            'name' => 'Opción Alfa',
        ]);
    }

    /** T06: Cannot open election with fewer than two options */
    public function test_t06_cannot_open_election_with_fewer_than_two_options(): void
    {
        $election = Election::create([
            'title' => 'Elección Incompleta',
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        // 0 options
        $this->actingAs($this->admin)->post("/admin/elections/{$election->id}/open");
        $this->assertEquals('pending', $election->fresh()->status);

        // 1 option
        Option::create(['election_id' => $election->id, 'name' => 'Opción Única']);
        $this->actingAs($this->admin)->post("/admin/elections/{$election->id}/open");
        $this->assertEquals('pending', $election->fresh()->status);

        // 2 options -> successfully opens
        Option::create(['election_id' => $election->id, 'name' => 'Opción Segunda']);
        $response = $this->actingAs($this->admin)->post("/admin/elections/{$election->id}/open");
        $response->assertSessionHas('success');
        $this->assertEquals('open', $election->fresh()->status);
        $this->assertNotNull($election->fresh()->opened_at);
    }

    /** T07: Student votes once in open election (1 vote + 1 participation, vote is anonymous) */
    public function test_t07_student_votes_once_in_open_election(): void
    {
        $election = Election::create([
            'title' => 'Elección Abierta',
            'status' => 'open',
            'opened_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $opt1 = Option::create(['election_id' => $election->id, 'name' => 'Candidato 1']);
        $opt2 = Option::create(['election_id' => $election->id, 'name' => 'Candidato 2']);

        $response = $this->actingAs($this->student)->post("/elections/{$election->id}/vote", [
            'option_id' => $opt1->id,
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('success');

        // Check exactly 1 vote and 1 participation
        $this->assertEquals(1, Vote::where('election_id', $election->id)->count());
        $this->assertEquals(1, VoterParticipation::where('election_id', $election->id)->count());

        // Check vote record does NOT have user_id
        $vote = Vote::first();
        $this->assertFalse(array_key_exists('user_id', $vote->getAttributes()));

        // Check participation belongs to the student
        $this->assertDatabaseHas('voter_participations', [
            'election_id' => $election->id,
            'user_id' => $this->student->id,
        ]);
    }

    /** T08: Student repeating vote is strictly rejected */
    public function test_t08_student_cannot_vote_twice(): void
    {
        $election = Election::create([
            'title' => 'Elección Doble Voto',
            'status' => 'open',
            'opened_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $opt1 = Option::create(['election_id' => $election->id, 'name' => 'Opción 1']);
        $opt2 = Option::create(['election_id' => $election->id, 'name' => 'Opción 2']);

        // First vote
        $this->actingAs($this->student)->post("/elections/{$election->id}/vote", [
            'option_id' => $opt1->id,
        ]);

        // Second vote attempt
        $response2 = $this->actingAs($this->student)->post("/elections/{$election->id}/vote", [
            'option_id' => $opt2->id,
        ]);

        $this->assertEquals(1, Vote::where('election_id', $election->id)->count());
        $this->assertEquals(1, VoterParticipation::where('election_id', $election->id)->count());
        $this->assertTrue($this->student->hasVotedIn($election));
    }

    /** T09: Attempt to vote in closed or pending election is rejected */
    public function test_t09_cannot_vote_in_closed_or_pending_election(): void
    {
        $pending = Election::create(['title' => 'Pendiente', 'status' => 'pending']);
        $optP = Option::create(['election_id' => $pending->id, 'name' => 'Op P']);

        $closed = Election::create(['title' => 'Cerrada', 'status' => 'closed', 'closed_at' => now()]);
        $optC = Option::create(['election_id' => $closed->id, 'name' => 'Op C']);

        $resP = $this->actingAs($this->student)->post("/elections/{$pending->id}/vote", ['option_id' => $optP->id]);
        $resP->assertSessionHas('error');

        $resC = $this->actingAs($this->student)->post("/elections/{$closed->id}/vote", ['option_id' => $optC->id]);
        $resC->assertSessionHas('error');

        $this->assertEquals(0, Vote::count());
    }

    /** T10: Attempt to vote for option from another election is rejected */
    public function test_t10_cannot_vote_for_foreign_option(): void
    {
        $election1 = Election::create(['title' => 'Elección 1', 'status' => 'open', 'opened_at' => now()]);
        $opt1 = Option::create(['election_id' => $election1->id, 'name' => 'Opción 1']);

        $election2 = Election::create(['title' => 'Elección 2', 'status' => 'open', 'opened_at' => now()]);
        $optForeign = Option::create(['election_id' => $election2->id, 'name' => 'Opción Ajena']);

        $response = $this->actingAs($this->student)->post("/elections/{$election1->id}/vote", [
            'option_id' => $optForeign->id,
        ]);

        $response->assertSessionHasErrors('option_id');
        $this->assertEquals(0, Vote::count());
    }

    /** T11: Option vote percentages and totals calculated accurately */
    public function test_t11_vote_calculation_percentages(): void
    {
        $election = Election::create(['title' => 'Elección Cálculo', 'status' => 'closed', 'closed_at' => now()]);
        $optA = Option::create(['election_id' => $election->id, 'name' => 'Opción A']);
        $optB = Option::create(['election_id' => $election->id, 'name' => 'Opción B']);
        $optC = Option::create(['election_id' => $election->id, 'name' => 'Opción C']);

        // 3 votes for A, 2 for B, 0 for C (Total: 5 votes -> 60%, 40%, 0%)
        for ($i = 0; $i < 3; $i++) {
            Vote::create(['election_id' => $election->id, 'option_id' => $optA->id]);
        }
        for ($i = 0; $i < 2; $i++) {
            Vote::create(['election_id' => $election->id, 'option_id' => $optB->id]);
        }

        $totalVotes = $election->votes()->count();
        $this->assertEquals(5, $totalVotes);

        $percentA = round(($optA->votes()->count() / $totalVotes) * 100, 2);
        $percentB = round(($optB->votes()->count() / $totalVotes) * 100, 2);
        $percentC = round(($optC->votes()->count() / $totalVotes) * 100, 2);

        $this->assertEquals(60.0, $percentA);
        $this->assertEquals(40.0, $percentB);
        $this->assertEquals(0.0, $percentC);
    }

    /** T14: Results of open election cannot be viewed */
    public function test_t14_results_of_open_election_forbidden(): void
    {
        $election = Election::create(['title' => 'Abierta', 'status' => 'open', 'opened_at' => now()]);

        $response = $this->actingAs($this->student)->get("/elections/{$election->id}/results");
        $response->assertSessionHas('error');
    }

    /** T15: Modifying options in open or closed election is prohibited */
    public function test_t15_cannot_modify_options_of_open_or_closed_election(): void
    {
        $election = Election::create(['title' => 'Abierta', 'status' => 'open', 'opened_at' => now()]);
        $opt = Option::create(['election_id' => $election->id, 'name' => 'Opción Fija']);

        // Attempt update
        $resUpdate = $this->actingAs($this->admin)->patch("/admin/options/{$opt->id}", [
            'name' => 'Nombre Cambiado',
        ]);
        $resUpdate->assertSessionHas('error');
        $this->assertEquals('Opción Fija', $opt->fresh()->name);

        // Attempt delete
        $resDelete = $this->actingAs($this->admin)->delete("/admin/options/{$opt->id}");
        $resDelete->assertSessionHas('error');
        $this->assertDatabaseHas('options', ['id' => $opt->id]);
    }
}
