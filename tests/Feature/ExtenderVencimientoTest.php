<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Models\User;
use App\Models\role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExtenderVencimientoTest extends TestCase
{
    use RefreshDatabase;

    protected $roleAdmin;
    protected $empresa;
    protected $usuario;
    protected $cliente;
    protected $servicio;
    protected $servicioPagar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdmin = role::create(['id' => 2, 'nombre' => 'Admin']);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'cuit' => 12345678901,
            'correo' => 'empresa@test.com',
        ]);

        $this->usuario = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'dni' => '12345678',
            'role_id' => $this->roleAdmin->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Test',
            'dni' => 22333444,
            'telefono' => '987654321',
            'domicilio' => 'Test Address',
            'correo' => 'cliente@test.com',
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Servicio Test',
            'descripcion' => 'Descripcion del servicio de prueba',
            'precio' => 1000.00,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1,
            'fecha_vencimiento' => now()->addDay()->toDateString(),
        ]);
    }

    public function test_actualiza_la_fecha_de_vencimiento_de_un_servicio_impago()
    {
        $nuevaFecha = now()->addDays(10)->toDateString();

        $response = $this->actingAs($this->usuario)
            ->post(route('ExtenderVencimiento', ['idServicioPagar' => $this->servicioPagar->id]), [
                'fecha_vencimiento' => $nuevaFecha,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame($nuevaFecha, $this->servicioPagar->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_permite_fijar_una_fecha_pasada()
    {
        $nuevaFecha = now()->subDays(5)->toDateString();

        $response = $this->actingAs($this->usuario)
            ->post(route('ExtenderVencimiento', ['idServicioPagar' => $this->servicioPagar->id]), [
                'fecha_vencimiento' => $nuevaFecha,
            ]);

        $response->assertRedirect();

        $this->assertSame($nuevaFecha, $this->servicioPagar->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_no_permite_modificar_un_servicio_pagado()
    {
        $fechaOriginal = $this->servicioPagar->fecha_vencimiento->toDateString();
        $this->servicioPagar->update(['estado' => 'pago']);

        $response = $this->actingAs($this->usuario)
            ->post(route('ExtenderVencimiento', ['idServicioPagar' => $this->servicioPagar->id]), [
                'fecha_vencimiento' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertSessionHasErrors();

        $this->assertSame($fechaOriginal, $this->servicioPagar->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_no_permite_modificar_servicio_de_otra_empresa()
    {
        $otraEmpresa = Empresa::create([
            'nombre' => 'Otra Empresa',
            'cuit' => 10987654321,
            'correo' => 'otra@test.com',
        ]);

        $servicioAjeno = Servicio::create([
            'nombre' => 'Servicio Ajeno',
            'descripcion' => 'Servicio de otra empresa',
            'precio' => 500.00,
            'empresa_id' => $otraEmpresa->id,
        ]);

        $servicioPagarAjeno = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicioAjeno->id,
            'estado' => 'impago',
            'precio' => 500.00,
            'cantidad' => 1,
            'fecha_vencimiento' => now()->addDay()->toDateString(),
        ]);

        $fechaOriginal = $servicioPagarAjeno->fecha_vencimiento->toDateString();

        $response = $this->actingAs($this->usuario)
            ->post(route('ExtenderVencimiento', ['idServicioPagar' => $servicioPagarAjeno->id]), [
                'fecha_vencimiento' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertSessionHasErrors();

        $this->assertSame($fechaOriginal, $servicioPagarAjeno->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_rechaza_una_fecha_invalida()
    {
        $fechaOriginal = $this->servicioPagar->fecha_vencimiento->toDateString();

        $response = $this->actingAs($this->usuario)
            ->post(route('ExtenderVencimiento', ['idServicioPagar' => $this->servicioPagar->id]), [
                'fecha_vencimiento' => 'no-es-una-fecha',
            ]);

        $response->assertSessionHasErrors('fecha_vencimiento');

        $this->assertSame($fechaOriginal, $this->servicioPagar->fresh()->fecha_vencimiento->toDateString());
    }

    public function test_rechaza_una_fecha_vacia()
    {
        $fechaOriginal = $this->servicioPagar->fecha_vencimiento->toDateString();

        $response = $this->actingAs($this->usuario)
            ->post(route('ExtenderVencimiento', ['idServicioPagar' => $this->servicioPagar->id]), [
                'fecha_vencimiento' => '',
            ]);

        $response->assertSessionHasErrors('fecha_vencimiento');

        $this->assertSame($fechaOriginal, $this->servicioPagar->fresh()->fecha_vencimiento->toDateString());
    }
}
