<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use ReflectionClass;
use Tests\TestCase;

class CsrfExclusionTest extends TestCase
{
    /**
     * Laravel desactiva la verificación CSRF cuando corre en tests, por lo que
     * no se puede comprobar por HTTP. Se valida la configuración del middleware.
     */
    private function rutasExcluidas(): array
    {
        $reflection = new ReflectionClass(VerifyCsrfToken::class);
        $property = $reflection->getProperty('neverVerify');
        $property->setAccessible(true);

        return $property->getValue();
    }

    public function test_webhooks_de_mercadopago_estan_excluidos_de_csrf(): void
    {
        $excluidas = $this->rutasExcluidas();

        $this->assertContains('mercadopago/webhook', $excluidas);
        $this->assertContains('api/mercadopago/webhook/qr', $excluidas);
    }

    public function test_rutas_web_normales_no_estan_excluidas(): void
    {
        $excluidas = $this->rutasExcluidas();

        $this->assertNotContains('loginUsuario', $excluidas);
        $this->assertNotContains('pago.generar', $excluidas);
    }
}
