<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private role $roleAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdmin = role::create(['id' => 2, 'nombre' => 'Admin']);
        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'cuit' => 12345678901,
            'correo' => 'empresa@test.com',
        ]);
    }

    private function crearUsuario(string $email = 'admin@test.com', int $roleId = 2): User
    {
        return User::create([
            'name' => 'Usuario Test',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'dni' => (string) random_int(10000000, 99999999),
            'role_id' => $roleId,
            'empresa_id' => $this->empresa->id,
        ]);
    }

    public function test_login_con_credenciales_validas_redirige_y_autentica(): void
    {
        $user = $this->crearUsuario();

        $response = $this->post(route('loginUsuario'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('Grilla'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_con_credenciales_invalidas_no_autentica(): void
    {
        $user = $this->crearUsuario();

        $response = $this->from('/login')->post(route('loginUsuario'), [
            'email' => $user->email,
            'password' => 'incorrecta',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_invalida_la_sesion(): void
    {
        $user = $this->crearUsuario();

        $this->actingAs($user)
            ->get(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_login_redirige_al_panel_si_ya_esta_autenticado(): void
    {
        $user = $this->crearUsuario();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('panelServicios');
    }

    public function test_ruta_protegida_redirige_al_login_sin_autenticacion(): void
    {
        $this->get(route('panelServicios'))->assertRedirect(route('login'));
    }

    public function test_login_requiere_email_y_password(): void
    {
        $this->post(route('loginUsuario'), [])
            ->assertSessionHasErrors(['email', 'password']);
    }
}
