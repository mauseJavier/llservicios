<?php

namespace Tests\Feature;

use App\Livewire\EmpresaToken;
use App\Models\Empresa;
use App\Models\role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireComponentsTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $role = role::create(['id' => 3, 'nombre' => 'Super']);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Livewire',
            'cuit' => '12345678901',
            'correo' => 'livewire@test.com',
        ]);

        $this->usuario = User::create([
            'name' => 'Super Test',
            'email' => 'super@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '12345678',
            'role_id' => $role->id,
            'empresa_id' => $this->empresa->id,
        ]);
    }

    public function test_empresa_token_muestra_los_datos_de_la_empresa(): void
    {
        $this->actingAs($this->usuario);

        Livewire::test(EmpresaToken::class, ['empresaId' => $this->empresa->id])
            ->assertSet('nombreEmpresa', 'Empresa Livewire')
            ->assertSet('tieneToken', false);
    }

    public function test_empresa_token_genera_un_token_para_super(): void
    {
        $this->actingAs($this->usuario);

        Livewire::test(EmpresaToken::class, ['empresaId' => $this->empresa->id])
            ->call('generarToken')
            ->assertSet('tieneToken', true)
            ->assertSet('mostrarModal', true);

        $this->empresa->refresh();
        $this->assertNotEmpty($this->empresa->api_token);
    }

    public function test_empresa_token_rechaza_generar_sin_ser_super(): void
    {
        $roleAdmin = role::create(['id' => 2, 'nombre' => 'Admin']);

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '87654321',
            'role_id' => $roleAdmin->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EmpresaToken::class, ['empresaId' => $this->empresa->id])
            ->call('generarToken')
            ->assertForbidden();

        $this->empresa->refresh();
        $this->assertEmpty($this->empresa->api_token);
    }
}
