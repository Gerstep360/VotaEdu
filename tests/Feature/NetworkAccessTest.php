<?php

namespace Tests\Feature;

use App\Services\NetworkAccessService;
use Tests\TestCase;

class NetworkAccessTest extends TestCase
{
    public function test_conectar_page_is_accessible_and_renders_qr_and_ip(): void
    {
        $response = $this->get('/conectar');

        $response->assertStatus(200);
        $response->assertSee('Punto de Conexión Móvil');
        $response->assertSee('Escanea para Votar');
        $response->assertSee('qr-viewport', false);
        $response->assertSee('<svg', false);
        $response->assertSee('U.E. Maestro Pitágoras II');
    }

    public function test_qr_shortcut_redirects_to_conectar(): void
    {
        $response = $this->get('/qr');

        $response->assertRedirect('/conectar');
    }

    public function test_qr_svg_download_endpoint_returns_valid_svg(): void
    {
        $response = $this->get('/qr.svg');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertSee('<svg', false);
    }

    public function test_network_service_generates_valid_qr_and_discovers_ip(): void
    {
        $service = app(NetworkAccessService::class);

        $ip = $service->getPrimaryIp();
        $this->assertNotEmpty($ip);

        $url = $service->buildUrl($ip, 8000);
        $this->assertStringStartsWith('http://', $url);
        $this->assertStringContainsString(':8000', $url);

        $svg = $service->generateQrSvg($url);
        $this->assertStringContainsString('<svg', $svg);

        $dataUri = $service->generateQrDataUri($url);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);

        $terminalQr = $service->renderTerminalQr($url);
        $this->assertNotEmpty($terminalQr);
    }

    public function test_artisan_vota_info_command_executes_successfully(): void
    {
        $this->artisan('vota', ['--info' => true, '--no-browser' => true])
            ->expectsOutputToContain('VOTAEDU · SISTEMA DISTRIBUIDO DE VOTACIÓN ESCOLAR')
            ->expectsOutputToContain('SERVIDOR ACTIVO EN RED LOCAL')
            ->assertExitCode(0);
    }
}
