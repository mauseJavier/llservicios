<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\EnviarEmailNuvoServicioJob;
use App\Mail\NotificacionCuotaMail;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Services\MercadoPago\MercadoPagoLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class EnviarEmailNuvoServicioJobTest extends TestCase
{
    use RefreshDatabase;

    protected $empresa;
    protected $cliente;
    protected $servicioPagar;

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
            'precio' => 2500.00,
            'empresa_id' => $this->empresa->id
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicio->id,
            'estado' => 'impago',
            'precio' => 2500.00,
            'cantidad' => 1
        ]);
    }

    public function test_el_correo_incluye_el_link_firmado_de_pago()
    {
        Mail::fake();

        $job = new EnviarEmailNuvoServicioJob($this->servicioPagar->id);
        $job->handle();

        Mail::assertSent(NotificacionCuotaMail::class, function ($mail) {
            return str_contains($mail->render(), '/pago/enlace/individual/' . $this->servicioPagar->id);
        });
    }

    public function test_el_correo_se_envia_igual_sin_token_de_empresa()
    {
        $this->empresa->update(['MP_ACCESS_TOKEN' => null]);

        Mail::fake();

        $job = new EnviarEmailNuvoServicioJob($this->servicioPagar->id);
        $job->handle();

        Mail::assertSent(NotificacionCuotaMail::class);
    }
}