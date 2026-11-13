<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;

/**
 * Ubica cloudflared, el programa del túnel de Cloudflare.
 */
class Cloudflared
{
    /**
     * Ruta del programa, o null si no está instalado. Además del PATH, busca en
     * su carpeta de Windows: las terminales abiertas antes de instalarlo (como
     * las de VS Code) no ven el PATH nuevo.
     */
    public function binary(): ?string
    {
        $candidates = ['cloudflared'];

        foreach (['ProgramFiles(x86)', 'ProgramFiles'] as $variable) {
            $folder = getenv($variable);

            if (is_string($folder) && $folder !== '') {
                $candidates[] = $folder.DIRECTORY_SEPARATOR.'cloudflared'.DIRECTORY_SEPARATOR.'cloudflared.exe';
            }
        }

        foreach ($candidates as $candidate) {
            // Como arreglo, la ruta con espacios llega intacta sin comillas.
            if (Process::run([$candidate, '--version'])->successful()) {
                return $candidate;
            }
        }

        return null;
    }
}
