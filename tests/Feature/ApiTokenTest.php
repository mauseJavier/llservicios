<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    private function empresa(string $nombre): Empresa
    {
        return Empresa::create([
            'nombre' => $nombre,
            'cuit' => (string) random_int(20000000000, 29999999999),
            'correo' => strtolower($nombre).'@test.com',
        ]);
    }

    private function cliente(string $nombre, string $dni): Cliente
    {
        return Cliente::create([
            'nombre' => $nombre,
            'dni' => (int) $dni,
            'telefono' => '2942506803',
            'domicilio' => 'Calle Test 123',
            'correo' => strtolower($nombre).'@test.com',
        ]);
    }

    private function payload(Empresa $empresa, Cliente $cliente): array
    {
        return [
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'nombre_cerradura' => 'Puerta principal',
            'fecha_hora' => now()->toDateTimeString(),
            'tipo' => 'apertura',
        ];
    }

    public function test_sin_token_devuelve_401(): void
    {
        $this->postJson('/api/registros-cerradura', [])
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_token_invalido_devuelve_401(): void
    {
        $this->withHeader('Authorization', 'Bearer token-invalido')
            ->postJson('/api/registros-cerradura', [])
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_token_valido_crea_el_registro(): void
    {
        $empresa = $this->empresa('Empresa A');
        $cliente = $this->cliente('Cliente A', '22333444');
        $empresa->clientes()->attach($cliente->id);

        $token = $empresa->generateApiToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/registros-cerradura', $this->payload($empresa, $cliente))
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('registros_cerradura', [
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'nombre_cerradura' => 'Puerta principal',
        ]);
    }

    public function test_token_de_otra_empresa_no_permite_operar_sobre_un_cliente_ajeno(): void
    {
        $empresaA = $this->empresa('Empresa A');
        $empresaB = $this->empresa('Empresa B');
        $clienteB = $this->cliente('Cliente B', '33444555');
        $empresaB->clientes()->attach($clienteB->id);

        $tokenA = $empresaA->generateApiToken();

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/registros-cerradura', $this->payload($empresaB, $clienteB))
            ->assertStatus(403);

        $this->assertDatabaseCount('registros_cerradura', 0);
    }

    public function test_empresa_id_que_no_coincide_con_el_token_devuelve_403(): void
    {
        $empresaA = $this->empresa('Empresa A');
        $empresaB = $this->empresa('Empresa B');
        $clienteA = $this->cliente('Cliente A', '22333444');
        $empresaA->clientes()->attach($clienteA->id);

        $tokenA = $empresaA->generateApiToken();
        $payload = $this->payload($empresaA, $clienteA);
        $payload['empresa_id'] = $empresaB->id;

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/registros-cerradura', $payload)
            ->assertStatus(403);
    }
}
