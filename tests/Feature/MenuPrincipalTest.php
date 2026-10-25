<?php

namespace Tests\Feature;

use App\Livewire\MenuPrincipal;
use App\Models\Empresa;
use App\Models\role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuPrincipalTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Menú',
            'cuit' => '20999999991',
            'correo' => 'menu@test.com',
        ]);
    }

    private function usuarioConRol(string $nombreRol): User
    {
        $rol = role::create(['nombre' => $nombreRol]);

        return User::create([
            'name' => "Usuario {$nombreRol}",
            'email' => strtolower($nombreRol).'@menu.test',
            'password' => bcrypt('secret123'),
            'dni' => (string) random_int(10000000, 99999999),
            'role_id' => $rol->id,
            'empresa_id' => $this->empresa->id,
        ]);
    }

    public function test_el_componente_se_resuelve_por_su_nombre_para_el_layout(): void
    {
        Livewire::actingAs($this->usuarioConRol('Super'))
            ->test('menu-principal')
            ->assertSet('abierto', false)
            ->assertSee('Menú');
    }

    public function test_super_ve_todas_las_categorias_del_menu(): void
    {
        Livewire::actingAs($this->usuarioConRol('Super'))
            ->test(MenuPrincipal::class)
            ->assertSee('Operación')
            ->assertSee('Clientes')
            ->assertSee('Cobros')
            ->assertSee('Mercado Pago')
            ->assertSee('Administración')
            ->assertSee('Cuenta')
            ->assertSee('Usuarios')
            ->assertSee('Auditoría MP')
            ->assertSee('Buscar un acceso');
    }

    public function test_admin_no_ve_los_accesos_de_administracion(): void
    {
        Livewire::actingAs($this->usuarioConRol('Admin'))
            ->test(MenuPrincipal::class)
            ->assertSee('Cierre de Caja')
            ->assertDontSee('Administración')
            ->assertDontSee('Ver Logs')
            ->assertDontSee('Auditoría MP');
    }

    public function test_operador_solo_ve_operacion_y_cuenta(): void
    {
        Livewire::actingAs($this->usuarioConRol('Operador'))
            ->test(MenuPrincipal::class)
            ->assertSee('Operación')
            ->assertSee('Agregar Servicio')
            ->assertSee('Cuenta')
            ->assertDontSee('Segmentos')
            ->assertDontSee('Cierre de Caja')
            ->assertDontSee('Gestión QR MP');
    }

    public function test_abrir_y_cerrar_el_modal(): void
    {
        Livewire::actingAs($this->usuarioConRol('Super'))
            ->test(MenuPrincipal::class)
            ->assertSet('abierto', false)
            ->call('abrir')
            ->assertSet('abierto', true)
            ->assertDispatched('menu-principal-abierto')
            ->call('cerrar')
            ->assertSet('abierto', false)
            ->assertSet('busqueda', '')
            ->assertDispatched('menu-principal-cerrado');
    }

    public function test_la_busqueda_filtra_accesos_y_oculta_categorias_vacias(): void
    {
        Livewire::actingAs($this->usuarioConRol('Super'))
            ->test(MenuPrincipal::class)
            ->call('abrir')
            ->set('busqueda', 'cierre')
            ->assertSee('Cierre de Caja')
            ->assertSee('Historial de Caja')
            ->assertDontSee('Segmentos')
            ->assertDontSee('Auditoría MP');
    }

    public function test_la_busqueda_ignora_acentos_y_mayusculas(): void
    {
        Livewire::actingAs($this->usuarioConRol('Super'))
            ->test(MenuPrincipal::class)
            ->set('busqueda', 'AUDITORIA')
            ->assertSee('Auditoría MP')
            ->assertDontSee('Segmentos');
    }

    public function test_la_busqueda_sin_resultados_muestra_el_estado_vacio(): void
    {
        Livewire::actingAs($this->usuarioConRol('Super'))
            ->test(MenuPrincipal::class)
            ->set('busqueda', 'zzzz')
            ->assertSee('No encontramos accesos')
            ->call('limpiarBusqueda')
            ->assertSet('busqueda', '')
            ->assertSee('Administración');
    }
}
