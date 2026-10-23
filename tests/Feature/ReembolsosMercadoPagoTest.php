<?php

namespace Tests\Feature;

use App\Livewire\ReembolsosMercadoPago;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\role;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ReembolsosMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Empresa $otraEmpresa;

    private User $admin;

    private User $super;

    private ServicioPagar $servicioPagar;

    protected function setUp(): void
    {
        parent::setUp();

        $roleSuper = role::create(['id' => 1, 'nombre' => 'Super']);
        $roleAdmin = role::create(['id' => 2, 'nombre' => 'Admin']);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa A',
            'cuit' => '20333333334',
            'correo' => 'empresa-a@test.com',
            'MP_ACCESS_TOKEN' => 'token-empresa-a',
        ]);

        $this->otraEmpresa = Empresa::create([
            'nombre' => 'Empresa B',
            'cuit' => '20444444445',
            'correo' => 'empresa-b@test.com',
            'MP_ACCESS_TOKEN' => 'token-empresa-b',
        ]);

        $this->super = User::create([
            'name' => 'Super Test',
            'email' => 'super-reembolsos@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '11111111',
            'role_id' => $roleSuper->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Empresa A',
            'email' => 'admin-reembolsos@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '22222222',
            'role_id' => $roleAdmin->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Javier Desmaret',
            'correo' => 'javier-reembolsos@test.com',
            'telefono' => '2942506803',
            'dni' => '35833716',
        ]);

        $servicio = Servicio::create([
            'nombre' => 'Rayos X',
            'descripcion' => 'Radiografía digital',
            'precio' => 450,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'cantidad' => 1,
            'precio' => 450,
            'estado' => 'impago',
            'periodo_servicio' => '2026-10-01',
            'fecha_vencimiento' => '2026-11-01',
        ]);

        $this->servicioPagar->created_at = now()->subDay();
        $this->servicioPagar->save();
    }

    private function fakeMercadoPago(): void
    {
        $referencia = 'servicio_pagar_'.$this->servicioPagar->id;

        $pagos = [
            [
                'id' => 'pago-viejo',
                'status' => 'approved',
                'external_reference' => $referencia,
                'transaction_amount' => 450,
                'currency_id' => 'ARS',
                'date_approved' => now()->subHours(2)->toIso8601String(),
                'date_created' => now()->subHours(2)->toIso8601String(),
                'transaction_amount_refunded' => 0,
            ],
            [
                'id' => 'pago-nuevo',
                'status' => 'approved',
                'external_reference' => $referencia,
                'transaction_amount' => 450,
                'currency_id' => 'ARS',
                'date_approved' => now()->subHour()->toIso8601String(),
                'date_created' => now()->subHour()->toIso8601String(),
                'transaction_amount_refunded' => 0,
            ],
        ];

        Http::fake(function (ClientRequest $request) use ($pagos) {
            $url = $request->url();

            if (str_contains($url, '/v1/payments/search')) {
                return Http::response([
                    'results' => $pagos,
                    'paging' => ['total' => count($pagos)],
                ], 200);
            }

            if (str_contains($url, '/refunds')) {
                return Http::response([
                    'id' => 'refund-test',
                    'status' => 'approved',
                    'amount' => 450,
                ], 201);
            }

            if (preg_match('#/v1/payments/([^/?]+)$#', $url, $matches)) {
                $pago = collect($pagos)->firstWhere('id', $matches[1]);

                return $pago
                    ? Http::response($pago, 200)
                    : Http::response(['message' => 'not found'], 404);
            }

            return Http::response([], 200);
        });
    }

    public function test_super_puede_ver_la_pantalla_de_reembolsos(): void
    {
        $this->actingAs($this->super)
            ->get('/mercadopago/reembolsos')
            ->assertOk()
            ->assertSee('Reembolsos de pagos duplicados MercadoPago');
    }

    public function test_admin_de_empresa_solo_puede_buscar_en_su_empresa(): void
    {
        $this->fakeMercadoPago();
        $this->actingAs($this->admin);

        Livewire::test(ReembolsosMercadoPago::class)
            ->assertSet('empresaId', (string) $this->empresa->id)
            ->set('empresaId', (string) $this->otraEmpresa->id)
            ->assertSet('empresaId', (string) $this->empresa->id)
            ->set('desde', now()->subDays(2)->toDateString())
            ->set('hasta', now()->toDateString())
            ->call('buscarDuplicados')
            ->assertSet('candidatos', function (array $candidatos) {
                return count($candidatos) === 1
                    && $candidatos[0]['referencia'] === 'servicio_pagar_'.$this->servicioPagar->id
                    && $candidatos[0]['elegible'] === true;
            });
    }

    public function test_super_puede_cambiar_de_empresa(): void
    {
        $this->fakeMercadoPago();
        $this->actingAs($this->super);

        Livewire::test(ReembolsosMercadoPago::class)
            ->assertSet('empresaId', (string) $this->empresa->id)
            ->set('empresaId', (string) $this->otraEmpresa->id)
            ->assertSet('empresaId', (string) $this->otraEmpresa->id);
    }

    public function test_usuario_sin_rol_admin_no_puede_acceder(): void
    {
        $roleUsuario = role::create(['id' => 3, 'nombre' => 'Usuario']);

        $usuario = User::create([
            'name' => 'Usuario Test',
            'email' => 'usuario-reembolsos@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '33333333',
            'role_id' => $roleUsuario->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->actingAs($usuario)
            ->get('/mercadopago/reembolsos')
            ->assertForbidden();
    }
}
