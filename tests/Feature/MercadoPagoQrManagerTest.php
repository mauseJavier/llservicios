<?php

namespace Tests\Feature;

use App\Livewire\MercadoPagoQrManager;
use App\Models\Empresa;
use App\Models\role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MercadoPagoQrManagerTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $role = role::create(['id' => 3, 'nombre' => 'Super']);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa QR Manager',
            'cuit' => '20999888777',
            'correo' => 'qr-manager@test.com',
            'MP_ACCESS_TOKEN' => str_repeat('APP_USR-1234567890', 5),
            'MP_PUBLIC_KEY' => 'TEST-public-key',
            'MP_USER_ID' => '777888999',
        ]);

        $this->usuario = User::create([
            'name' => 'Usuario QR',
            'email' => 'qr-manager-user@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '12345678',
            'role_id' => $role->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->actingAs($this->usuario);
    }

    private function completarFormularioTienda(): array
    {
        return [
            'storeName' => 'prueba',
            'storeStreet' => 'san jorge',
            'storeNumber' => '1',
            'storeCity' => 'LAS LAJAS, NEUQUEN',
            'storeState' => 'Neuquén',
            'storeZipCode' => '8347',
        ];
    }

    public function test_el_error_de_mercadopago_se_muestra_dentro_del_modal(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'error' => 'validation_error',
                'message' => 'location.city_name was invalid.',
                'status' => 400,
                'causes' => [[
                    'code' => 400,
                    'description' => 'location.city_name was invalid. Valid values are: Confluencia, Zapala, Lácar',
                ]],
            ], 400),
        ]);

        $component = Livewire::test(MercadoPagoQrManager::class)
            ->call('openStoreModal')
            ->set($this->completarFormularioTienda())
            ->call('saveStore')
            ->assertSet('showStoreModal', true)
            ->assertSee('No se pudo guardar la tienda')
            ->assertSee('location.city_name was invalid')
            ->assertSee('Confluencia');

        $this->assertStringContainsString(
            'location.city_name was invalid',
            $component->get('errorMessage')
        );

        $this->assertContains('Confluencia', $component->get('validCities'));
        $this->assertContains('Zapala', $component->get('validCities'));

        $this->assertDatabaseCount('mercadopago_stores', 0);
    }

    public function test_crear_tienda_exitosa_guarda_la_sucursal(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'id' => '12345',
                'name' => 'prueba',
            ], 200),
        ]);

        Livewire::test(MercadoPagoQrManager::class)
            ->call('openStoreModal')
            ->set($this->completarFormularioTienda())
            ->call('saveStore')
            ->assertSet('showStoreModal', false)
            ->assertSet('errorMessage', '');

        $this->assertDatabaseHas('mercadopago_stores', [
            'empresa_id' => $this->empresa->id,
            'mp_store_id' => '12345',
            'name' => 'prueba',
        ]);
    }

    public function test_al_elegir_provincia_se_sugieren_las_ciudades_del_catalogo(): void
    {
        Http::fake();

        $component = Livewire::test(MercadoPagoQrManager::class)
            ->call('openStoreModal')
            ->set('storeState', 'Neuquén');

        $this->assertContains('Confluencia', $component->get('validCities'));
    }
}
