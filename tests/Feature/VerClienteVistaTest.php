<?php

namespace Tests\Feature;

use App\Livewire\VerCliente\VerCliente;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\RegistroCerradura;
use App\Models\role;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class VerClienteVistaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $usuario;

    private Cliente $cliente;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $role = role::create(['id' => 2, 'nombre' => 'Admin']);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Clientes',
            'cuit' => '20111111112',
            'correo' => 'clientes@test.com',
        ]);

        $this->usuario = User::create([
            'name' => 'Admin Clientes',
            'email' => 'admin.clientes@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '30111222',
            'role_id' => $role->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'FERRSEB',
            'titular' => 'JOSE AGUERO',
            'dni' => 294259884,
            'correo' => 'ferrseb@test.com',
            'telefono' => '2942598846',
            'domicilio' => 'Plotier, Neuquén',
        ]);

        $this->empresa->clientes()->attach($this->cliente->id);

        $this->servicio = Servicio::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'LLFactura Infinity',
            'descripcion' => 'Servicio de prueba',
            'precio' => 180000,
            'activo' => true,
        ]);

        $this->cliente->servicios()->attach($this->servicio->id, [
            'cantidad' => 1,
            'vencimiento' => now()->addDays(30),
        ]);
    }

    public function test_muestra_indicadores_y_el_cliente_vinculado(): void
    {
        $this->actingAs($this->usuario);

        Livewire::test(VerCliente::class)
            ->assertOk()
            ->assertSee('Total de clientes')
            ->assertSee('Con servicio activo')
            ->assertSee('Accesos esta semana')
            ->assertSee('FERRSEB')
            ->assertSee('LLFactura Infinity')
            ->assertSee('JOSE AGUERO')
            ->assertSee('Activo');
    }

    public function test_filtra_clientes_sin_servicio_activo(): void
    {
        $this->actingAs($this->usuario);

        $this->cliente->servicios()->detach();

        Livewire::test(VerCliente::class)
            ->call('filtrarPorEstado', 'con')
            ->assertDontSee('FERRSEB')
            ->call('filtrarPorEstado', 'sin')
            ->assertSee('FERRSEB')
            ->assertSee('Sin servicio');
    }

    public function test_expande_el_detalle_del_cliente(): void
    {
        $this->actingAs($this->usuario);

        Livewire::test(VerCliente::class)
            ->call('toggleDetalle', $this->cliente->id)
            ->assertSet('clienteExpandidoId', $this->cliente->id)
            ->assertSee('Últimos ingresos')
            ->assertSee('Acceso al local')
            ->call('toggleDetalle', $this->cliente->id)
            ->assertSet('clienteExpandidoId', null);
    }

    public function test_la_busqueda_sin_resultados_muestra_el_estado_vacio(): void
    {
        $this->actingAs($this->usuario);

        Livewire::test(VerCliente::class)
            ->set('buscarCliente', 'cliente-que-no-existe')
            ->assertSee('No se encontraron clientes');
    }

    private function registroCerradura(string $nombre, $fecha): RegistroCerradura
    {
        return RegistroCerradura::create([
            'empresa_id' => $this->empresa->id,
            'cliente_id' => $this->cliente->id,
            'nombre_cerradura' => $nombre,
            'fecha_hora' => $fecha,
            'tipo' => 'apertura',
            'dispositivo_id' => 'lock-01',
        ]);
    }

    private function impago($fecha, ?Cliente $cliente = null): ServicioPagar
    {
        return ServicioPagar::create([
            'cliente_id' => ($cliente ?? $this->cliente)->id,
            'servicio_id' => $this->servicio->id,
            'cantidad' => 1,
            'precio' => 1000,
            'estado' => 'impago',
            'fecha_vencimiento' => $fecha,
        ]);
    }

    public function test_el_aviso_cuenta_los_impagos_por_vencer(): void
    {
        $this->actingAs($this->usuario);
        $this->impago(now()->addDays(3));

        Livewire::test(VerCliente::class)
            ->assertSee('Servicios impagos por vencer')
            ->assertSee('1 servicio por vencer')
            ->assertSee(now()->addDays(3)->format('d/m/Y'))
            ->assertSee('Por vencer');
    }

    public function test_el_filtro_semana_muestra_solo_clientes_con_impagos_por_vencer(): void
    {
        $this->actingAs($this->usuario);

        $otroCliente = Cliente::create([
            'nombre' => 'CLIENTE SIN DEUDA',
            'dni' => 31444555,
            'correo' => 'sindeuda@test.com',
            'telefono' => '2942506811',
            'domicilio' => 'Otra calle 123',
        ]);
        $this->empresa->clientes()->attach($otroCliente->id);

        $this->impago(now()->addDays(3));

        Livewire::test(VerCliente::class)
            ->call('filtrarPorVencimiento', 'semana')
            ->assertSee($this->cliente->nombre)
            ->assertDontSee($otroCliente->nombre);
    }

    public function test_el_filtro_mes_cuenta_los_impagos_del_mes(): void
    {
        $this->actingAs($this->usuario);
        $this->impago(now()->endOfMonth()->startOfDay());

        Livewire::test(VerCliente::class)
            ->call('filtrarPorVencimiento', 'mes')
            ->assertSee($this->cliente->nombre)
            ->assertSee('Impagos del mes');
    }

    public function test_los_impagos_vencidos_marcan_la_fila_y_el_filtro(): void
    {
        $this->actingAs($this->usuario);
        $this->impago(now()->subDays(2));

        Livewire::test(VerCliente::class)
            ->assertSee('1 ya vencido')
            ->call('filtrarPorVencimiento', 'vencido')
            ->assertSee($this->cliente->nombre)
            ->assertSee('Vencido');
    }

    public function test_impagos_de_otra_empresa_no_se_cuentan(): void
    {
        $this->actingAs($this->usuario);

        $otraEmpresa = Empresa::create([
            'nombre' => 'Empresa Ajena',
            'cuit' => '20888888881',
            'correo' => 'ajena@test.com',
        ]);

        $clienteAjeno = Cliente::create([
            'nombre' => 'CLIENTE AJENO IMPAGO',
            'dni' => 30777888,
            'correo' => 'ajeno.impago@test.com',
            'telefono' => '2942506822',
            'domicilio' => 'Calle ajena 456',
        ]);
        $otraEmpresa->clientes()->attach($clienteAjeno->id);

        $servicioAjeno = Servicio::create([
            'empresa_id' => $otraEmpresa->id,
            'nombre' => 'Servicio ajeno',
            'descripcion' => 'Servicio de otra empresa',
            'precio' => 100,
            'activo' => true,
        ]);

        ServicioPagar::create([
            'cliente_id' => $clienteAjeno->id,
            'servicio_id' => $servicioAjeno->id,
            'cantidad' => 1,
            'precio' => 100,
            'estado' => 'impago',
            'fecha_vencimiento' => now()->addDays(2),
        ]);

        Livewire::test(VerCliente::class)
            ->assertSee('0 servicios por vencer')
            ->assertDontSee('1 servicio por vencer')
            ->assertDontSee($clienteAjeno->nombre);
    }

    public function test_el_modal_de_accesos_muestra_el_calendario_y_los_eventos_del_dia(): void
    {
        $this->actingAs($this->usuario);
        $this->registroCerradura('Puerta principal', now()->setTime(10, 30));

        Livewire::test(VerCliente::class)
            ->call('verEventosCerradura', $this->cliente->id)
            ->assertSet('vistaEventos', 'calendario')
            ->assertSet('mostrarModalEventos', true)
            ->assertSet('diaSeleccionado', now()->format('Y-m-d'))
            ->assertSee('Calendario')
            ->assertSee('Puerta principal')
            ->assertSee('10:30');
    }

    public function test_navega_al_mes_anterior_y_muestra_sus_eventos(): void
    {
        $this->actingAs($this->usuario);

        $fecha = now()->startOfMonth()->subMonthNoOverflow()->setDay(5)->setTime(9, 0);
        $this->registroCerradura('Cerradura mes anterior', $fecha);

        Livewire::test(VerCliente::class)
            ->call('verEventosCerradura', $this->cliente->id)
            ->call('mesAnterior')
            ->assertSet('mesCalendario', $fecha->format('Y-m'))
            ->assertSet('diaSeleccionado', $fecha->format('Y-m-d'))
            ->assertSee('Cerradura mes anterior');
    }

    public function test_seleccionar_un_dia_muestra_solo_sus_accesos(): void
    {
        $this->actingAs($this->usuario);

        $diaUno = now()->startOfMonth()->setTime(8, 0);
        $diaDos = now()->startOfMonth()->addDays(10)->setTime(18, 15);
        $this->registroCerradura('Acceso dia uno', $diaUno);
        $this->registroCerradura('Acceso dia dos', $diaDos);

        Livewire::test(VerCliente::class)
            ->call('verEventosCerradura', $this->cliente->id)
            ->call('seleccionarDia', $diaDos->format('Y-m-d'))
            ->assertSet('diaSeleccionado', $diaDos->format('Y-m-d'))
            ->assertSee('Acceso dia dos')
            ->assertDontSee('Acceso dia uno');
    }

    public function test_alterna_entre_calendario_y_lista(): void
    {
        $this->actingAs($this->usuario);
        $this->registroCerradura('Puerta principal', now()->setTime(10, 30));

        Livewire::test(VerCliente::class)
            ->call('verEventosCerradura', $this->cliente->id)
            ->call('cambiarVista', 'lista')
            ->assertSet('vistaEventos', 'lista')
            ->assertSee('Datos extra')
            ->call('cambiarVista', 'calendario')
            ->assertSet('vistaEventos', 'calendario');
    }

    public function test_no_muestra_accesos_de_otra_empresa(): void
    {
        $this->actingAs($this->usuario);

        $otraEmpresa = Empresa::create([
            'nombre' => 'Otra Empresa',
            'cuit' => '20999999991',
            'correo' => 'otra@test.com',
        ]);

        $otroCliente = Cliente::create([
            'nombre' => 'CLIENTE AJENO',
            'dni' => 30999888,
            'correo' => 'ajeno@test.com',
            'telefono' => '2942506800',
            'domicilio' => 'Otra calle',
        ]);

        $otraEmpresa->clientes()->attach($otroCliente->id);

        RegistroCerradura::create([
            'empresa_id' => $otraEmpresa->id,
            'cliente_id' => $otroCliente->id,
            'nombre_cerradura' => 'Cerradura ajena',
            'fecha_hora' => now()->setTime(11, 0),
            'tipo' => 'apertura',
        ]);

        $this->registroCerradura('Mi cerradura', now()->setTime(12, 0));

        Livewire::test(VerCliente::class)
            ->call('verEventosCerradura', $this->cliente->id)
            ->assertSee('Mi cerradura')
            ->assertDontSee('Cerradura ajena');
    }
}
