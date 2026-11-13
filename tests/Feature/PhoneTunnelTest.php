<?php

namespace Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PhoneTunnelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Responde con la URL que arma Laravel, la que usa para ligas y archivos.
        Route::get('/_url-actual', fn () => url('/'));
    }

    public function test_behind_the_local_tunnel_laravel_builds_https_urls_with_the_tunnel_host(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'demo.trycloudflare.com',
            'X-Forwarded-Port' => '443',
        ])
            ->get('/_url-actual')
            ->assertOk()
            ->assertSeeText('https://demo.trycloudflare.com', false);
    }

    public function test_a_proxy_from_another_machine_is_not_trusted(): void
    {
        $normal = (string) $this->get('/_url-actual')->getContent();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'malicioso.test'])
            ->get('/_url-actual')
            ->assertOk()
            ->assertDontSeeText('malicioso.test', false)
            ->assertContent($normal);
    }

    public function test_the_phone_command_asks_to_install_cloudflared_when_it_is_missing(): void
    {
        // También cubre la carpeta de Windows donde se instala.
        Process::fake(['*cloudflared*' => Process::result(exitCode: 1)]);

        $this->artisan('uniroute:celular')
            ->expectsOutputToContain('winget install --id Cloudflare.cloudflared')
            ->assertFailed();

        Process::assertNotRan('npm run build');
    }

    public function test_the_phone_command_stops_if_the_frontend_does_not_build(): void
    {
        Process::fake([
            '*cloudflared* --version' => Process::result('cloudflared version 2026.11.0'),
            'npm run build' => Process::result(errorOutput: 'error', exitCode: 1),
        ]);

        $this->artisan('uniroute:celular')
            ->expectsOutputToContain('No se pudo compilar el frontend')
            ->assertFailed();

        Process::assertRan(fn (PendingProcess $process) => $process->command === 'npm run build');
    }
}
