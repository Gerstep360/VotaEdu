<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_displays_user_data_and_edit_form(): void
    {
        $user = User::create([
            'name' => 'Carlos Mamani',
            'username' => 'carlos.m',
            'ci' => '78901234',
            'password' => Hash::make('secret123'),
            'role' => 'student',
            'course' => '6to de Secundaria',
            'parallel' => 'A',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('Mi perfil y configuración');
        $response->assertSee('Modificar mis datos personales');
        $response->assertSee('carlos.m');
        $response->assertSee('78901234');
        $response->assertSee('6to de Secundaria');
    }

    public function test_user_can_update_name_and_username(): void
    {
        $user = User::create([
            'name' => 'Carlos Mamani',
            'username' => 'carlos.m',
            'ci' => '78901234',
            'password' => Hash::make('secret123'),
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Carlos Mamani Choque',
            'username' => 'carlos.mamani.nuevo',
            'ci' => '78901234',
            'first_name' => 'Carlos',
            'paternal_surname' => 'Mamani',
            'maternal_surname' => 'Choque',
            'course' => '6to de Secundaria',
            'parallel' => 'B',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('Carlos Mamani Choque', $user->name);
        $this->assertEquals('carlos.mamani.nuevo', $user->username);
        $this->assertEquals('Carlos', $user->first_name);
        $this->assertEquals('Mamani', $user->paternal_surname);
        $this->assertEquals('Choque', $user->maternal_surname);
        $this->assertEquals('6to de Secundaria', $user->course);
        $this->assertEquals('B', $user->parallel);
    }

    public function test_username_must_be_unique_when_updating_profile(): void
    {
        User::create([
            'name' => 'Otro Usuario',
            'username' => 'usuario.existente',
            'ci' => '11111111',
            'password' => Hash::make('secret123'),
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $user = User::create([
            'name' => 'Mi Nombre',
            'username' => 'mi.usuario',
            'ci' => '22222222',
            'password' => Hash::make('secret123'),
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Mi Nombre',
            'username' => 'usuario.existente', // ya tomado
            'ci' => '22222222',
        ]);

        $response->assertSessionHasErrors(['username']);
        $user->refresh();
        $this->assertEquals('mi.usuario', $user->username);
    }

    public function test_ci_must_be_unique_when_updating_profile(): void
    {
        User::create([
            'name' => 'Otro Usuario',
            'username' => 'otro.usuario',
            'ci' => '11111111',
            'password' => Hash::make('secret123'),
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $user = User::create([
            'name' => 'Mi Nombre',
            'username' => 'mi.usuario',
            'ci' => '22222222',
            'password' => Hash::make('secret123'),
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Mi Nombre',
            'username' => 'mi.usuario',
            'ci' => '11111111', // ya tomado
        ]);

        $response->assertSessionHasErrors(['ci']);
        $user->refresh();
        $this->assertEquals('22222222', $user->ci);
    }
}
