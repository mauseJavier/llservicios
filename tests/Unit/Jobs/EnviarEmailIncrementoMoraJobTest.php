<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\EnviarEmailIncrementoMoraJob;
use App\Mail\NotificacionIncrementoMoraMail;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class EnviarEmailIncrementoMoraJobTest extends TestCase
{
    use RefreshDatabase;

    protected $empresa;
    protected $cliente;
    protected $servicioPagar;
    protected $datos;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mercadopago.access_token', 'TEST-123456789-access-token');
        Config::set('services.mercadopago.sandbox', true);
        Config::set('app.url', 'https://test-app.com');
        Config::set('app.env', 'production');

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'cuit' => 12345678901,
            'correo' => 'empresa@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-empresa-token'
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Test',
            'dni' => 12345678,
            'telefono' => '987654321',
            'domicilio' => 'Test Address',
            'correo' => 'cliente@test.com'
        ]);

        $servicio = Servicio::create([
            'nombre' => 'Servicio Test',
            'descripcion' => 'Descripción del servicio de prueba',
            'precio' => 1000.00,
            'empresa_id' => $this->empresa->id,
            'incremento_mora_tipo' => 'porcentaje',
            'incremento_mora_valor' => 10
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1,
            'fecha_vencimiento' => '2026-08-01',
            'incremento_mora_aplicado' => true
        ]);

        $this->datos = [
            'idServicioPagar' => $this->servicioPagar->id,
            'nombreCliente' => $this->cliente->nombre,
            'correoCliente' => $this->cliente->correo,
            'telefonoCliente' => $this->cliente->telefono,
            'nombreServicio' => $servicio->nombre,
            'cantidadServicio' => 1,
            'precioOriginal' => 1000.00,
            'totalOriginal' => 1000.00,
            'tipoRecargo' => 'porcentaje',
            'valorRecargo' => 10,
            'montoRecargo' => 100.00,
            'totalConRecargo' => 1100.00,
            'fechaVencimiento' => '01-08-2026',
            'nombreEmpresa' => $this->empresa->nombre,
            'empresaId' => $this->empresa->id,
        ];
    }

    public function test_el_correo_incluye_el_link_firmado_de_pago()
    {
        Mail::fake();

        $job = new EnviarEmailIncrementoMoraJob($this->servicioPagar->id, $this->datos);
        $job->handle();

        Mail::assertSent(NotificacionIncrementoMoraMail::class, function ($mail) {
            return str_contains($mail->render(), '/pago/enlace/individual/' . $this->servicioPagar->id);
        });
    }

    public function test_el_correo_se_envia_igual_sin_token_de_empresa()
    {
        $this->empresa->update(['MP_ACCESS_TOKEN' => null]);

        Mail::fake();

        $job = new EnviarEmailIncrementoMoraJob($this->servicioPagar->id, $this->datos);
        $job->handle();

        Mail::assertSent(NotificacionIncrementoMoraMail::class);
    }
}