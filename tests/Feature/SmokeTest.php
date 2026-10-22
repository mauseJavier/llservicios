<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_responde_ok(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_home_responde_ok(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_healthcheck_responde_ok(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_endpoint_de_cerradura_sin_token_devuelve_401(): void
    {
        $this->postJson('/api/registros-cerradura', [])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }
}
