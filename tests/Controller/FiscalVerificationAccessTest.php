<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * «Verificar en SUNAT» es solo del panel del facturador (decisión del dueño, 2026-10-18): ni el
 * CLIENT_TOKEN de backend_go (por el que pasan los proxies del tenant) ni una petición sin sesión
 * llegan a la acción.
 */
class FiscalVerificationAccessTest extends WebTestCase
{
    /**
     * @dataProvider rutas
     */
    public function testSinSesionDelPanelNoSeLlega(string $path): void
    {
        $client = static::createClient();
        $client->request('POST', $path . '?token=123456', [], [], ['HTTP_X_FISCAL_PANEL' => '1', 'CONTENT_TYPE' => 'application/json'], '{"expected_outcome":"accepted"}');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirect() || in_array($response->getStatusCode(), [401, 403], true),
            'Se esperaba redirección al login o 401/403 y llegó ' . $response->getStatusCode()
        );
        if ($response->isRedirect()) {
            $this->assertStringContainsString('/login', (string) $response->headers->get('Location'));
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rutas(): array
    {
        return [
            'verificar' => ['/dashboard/api/documents/0c6f66c6-a8fa-4bc0-8365-0d22ab8f557e/verify'],
            'aplicar' => ['/dashboard/api/documents/0c6f66c6-a8fa-4bc0-8365-0d22ab8f557e/verify/apply'],
        ];
    }

    public function testNoExisteBajoLaApiDeMaquina(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/fiscal/documents/0c6f66c6-a8fa-4bc0-8365-0d22ab8f557e/verify?token=123456');

        $this->assertNotSame(200, $client->getResponse()->getStatusCode());
    }
}
