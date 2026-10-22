<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        role::create(['id' => 1, 'nombre' => 'Usuario']);
        role::create(['id' => 2, 'nombre' => 'Admin']);
        role::create(['id' => 3, 'nombre' => 'Super']);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'cuit' => 12345678901,
            'correo' => 'empresa@test.com',
        ]);
    }

    private function usuarioConRol(int $roleId): User
    {
        return User::create([
            'name' => 'Usuario '.$roleId,
            'email' => 'usuario'.$roleId.'@test.com',
            'password' => Hash::make('secret123'),
            'dni' => (string) (10000000 + $roleId),
            'role_id' => $roleId,
            'empresa_id' => $this->empresa->id,
        ]);
    }

    public function test_admin_puede_acceder_a_la_grilla(): void
    {
        $this->actingAs($this->usuarioConRol(2))
            ->get(route('Grilla'))
            ->assertOk();
    }

    public function test_usuario_comun_no_puede_acceder_a_la_grilla(): void
    {
        $this->actingAs($this->usuarioConRol(1))
            ->get(route('Grilla'))
            ->assertRedirect(route('panelServicios'));
    }

    public function test_super_puede_acceder_a_usuarios(): void
    {
        $this->actingAs($this->usuarioConRol(3))
            ->get(route('usuarios'))
            ->assertOk();
    }

    public function test_admin_no_puede_acceder_a_usuarios(): void
    {
        $this->actingAs($this->usuarioConRol(2))
            ->get(route('usuarios'))
            ->assertRedirect(route('panelServicios'));
    }

    public function test_invitado_no_puede_acceder_a_usuarios(): void
    {
        $this->get(route('usuarios'))->assertRedirect(route('login'));
    }
}
