<?php

namespace App\Console\Commands;

use App\Support\Cloudflared;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class TunnelCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uniroute:tunel
        {--url=http://127.0.0.1:8000 : Dirección local a la que apunta el túnel}';

    /**
     * @var string
     */
    protected $description = 'Abre un túnel https de Cloudflare hacia la app local (lo usa uniroute:celular)';

    public function handle(Cloudflared $cloudflared): int
    {
        $binary = $cloudflared->binary();

        if ($binary === null) {
            $this->error('Falta cloudflared. Instálalo una vez con: winget install --id Cloudflare.cloudflared');

            return self::FAILURE;
        }

        // Se corre como arreglo para que la ruta de Windows (con espacios y
        // paréntesis) no dependa de comillas; php artisan dev envuelve cada
        // proceso entre comillas y unas comillas internas lo rompían.
        return Process::forever()
            ->run(
                [$binary, 'tunnel', '--no-autoupdate', '--url', (string) $this->option('url')],
                function (string $type, string $output): void {
                    $this->output->write($output);
                },
            )
            ->exitCode() ?? self::FAILURE;
    }
}
