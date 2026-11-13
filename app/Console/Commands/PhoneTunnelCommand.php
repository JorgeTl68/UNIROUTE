<?php

namespace App\Console\Commands;

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

    /**
     * Dirección local de php artisan serve, a la que apunta el túnel.
     */
    private const string LOCAL_URL = 'http://127.0.0.1:8000';

    public function handle(): int
    {
        $cloudflared = $this->cloudflared();

        if ($cloudflared === null) {
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
        DevCommands::register($cloudflared.' tunnel --url '.self::LOCAL_URL, 'tunel');
        DevCommands::artisan('uniroute:simular --iniciar', 'simulador');

        $this->newLine();
        $this->info('En la pestaña "tunel" aparece la liga https://….trycloudflare.com: ábrela en el celular.');
        $this->line('En el celular los camiones se actualizan cada 15 s; en esta máquina, al instante.');

        return $this->call('dev');
    }

    /**
     * Comando para correr cloudflared, o null si no está instalado. Además del
     * PATH, busca en su carpeta de Windows: las terminales abiertas antes de
     * instalarlo (como las de VS Code) no ven el PATH nuevo.
     */
    private function cloudflared(): ?string
    {
        $candidates = ['cloudflared'];

        foreach (['ProgramFiles(x86)', 'ProgramFiles'] as $variable) {
            $folder = getenv($variable);

            if (is_string($folder) && $folder !== '') {
                $candidates[] = '"'.$folder.'\cloudflared\cloudflared.exe"';
            }
        }

        foreach ($candidates as $candidate) {
            if (Process::run($candidate.' --version')->successful()) {
                return $candidate;
            }
        }

        return null;
    }
}
