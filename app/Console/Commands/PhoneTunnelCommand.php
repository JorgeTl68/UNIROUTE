<?php

namespace App\Console\Commands;

use App\Support\Cloudflared;
use Illuminate\Console\Command;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Process;

class PhoneTunnelCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uniroute:celular';

    /**
     * @var string
     */
    protected $description = 'Levanta UniRoute con una liga https de Cloudflare para abrirlo en el celular';

    public function handle(Cloudflared $cloudflared): int
    {
        if ($cloudflared->binary() === null) {
            $this->error('Falta cloudflared. Instálalo una vez con: winget install --id Cloudflare.cloudflared');

            return self::FAILURE;
        }

        // El celular no puede llegar al servidor de Vite de esta máquina: usa
        // los archivos compilados.
        $this->info('Compilando el frontend…');
        $build = Process::forever()->run('npm run build', function (string $type, string $output): void {
            $this->output->write($output);
        });

        if ($build->failed()) {
            $this->error('No se pudo compilar el frontend (npm run build).');

            return self::FAILURE;
        }

        // Si quedó de un npm run dev, Laravel seguiría apuntando a Vite.
        if (is_file(public_path('hot'))) {
            unlink(public_path('hot'));
        }

        DevCommands::except('vite');
        DevCommands::artisan('uniroute:tunel', 'tunel');
        DevCommands::artisan('uniroute:simular --iniciar', 'simulador');

        $this->newLine();
        $this->info('En la pestaña "tunel" aparece la liga https://….trycloudflare.com: ábrela en el celular.');
        $this->line('En el celular los camiones se actualizan cada 15 s; en esta máquina, al instante.');

        return $this->call('dev');
    }
}
