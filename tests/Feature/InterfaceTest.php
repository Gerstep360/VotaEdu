<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\Option;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfaceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['name' => 'Persona de prueba', 'username' => $role, 'password' => 'password123', 'role' => $role, 'is_active' => true]);
    }

    public function test_all_admin_pages_render_with_empty_and_populated_data(): void
    {
        $admin = $this->user('admin');
        $student = $this->user('student');
        $election = Election::create(['title' => 'Borrador de prueba', 'status' => 'pending', 'created_by' => $admin->id]);
        Option::create(['election_id' => $election->id, 'name' => 'Primera propuesta']);
        $this->actingAs($admin);
        foreach (['/admin', '/admin/students', '/admin/students/create', "/admin/students/{$student->id}/edit", '/admin/elections', '/admin/elections/create', "/admin/elections/{$election->id}", "/admin/elections/{$election->id}/edit"] as $url) {
            $this->get($url)->assertOk()->assertSee('VotaEdu');
        }
        $this->get('/admin/students?search=sin-coincidencias')->assertOk()->assertSee('No encontramos coincidencias');
        $this->get('/admin/elections?status=closed')->assertOk()->assertDontSee('Borrador de prueba')->assertSee('Aún no hay votaciones aquí');
    }

    public function test_student_dashboard_ballot_and_result_states_render(): void
    {
        $student = $this->user('student');
        $this->actingAs($student)->get('/student')->assertOk()->assertSee('No hay votaciones abiertas');
        $election = Election::create(['title' => 'Decisión escolar', 'status' => 'open']);
        Option::create(['election_id' => $election->id, 'name' => 'Propuesta A']);
        Option::create(['election_id' => $election->id, 'name' => 'Propuesta B']);
        $this->get('/student')->assertOk()->assertSee('Participar');
        $this->get("/elections/{$election->id}/vote")->assertOk()->assertSee('Propuesta A')->assertSee('data-confirm', false);
        $election->update(['status' => 'closed', 'closed_at' => now()]);
        $this->get("/elections/{$election->id}/results")->assertOk()->assertSee('No se registraron votos')->assertSee('0,00 %');
        foreach ($election->options as $option) {
            Vote::create(['election_id' => $election->id, 'option_id' => $option->id]);
        }
        $this->get("/elections/{$election->id}/results")->assertOk()->assertSee('Empate')->assertSee('50,00 %')->assertDontSee('OPCIÓN MÁS VOTADA');
    }

    public function test_authenticated_users_are_redirected_from_login_to_their_actual_dashboard(): void
    {
        $this->actingAs($this->user('admin'))->get('/login')->assertRedirect('/admin');
        $this->actingAs($this->user('student'))->get('/login')->assertRedirect('/student');
    }

    public function test_inactive_account_cannot_read_results(): void
    {
        $student = $this->user('student');
        $student->update(['is_active' => false]);
        $election = Election::create(['title' => 'Cerrada', 'status' => 'closed']);
        $this->actingAs($student)->get("/elections/{$election->id}/results")->assertRedirect('/login');
        $this->assertGuest();
    }
}
