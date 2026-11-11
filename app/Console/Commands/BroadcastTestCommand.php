<?php

namespace App\Console\Commands;

use App\Events\TrackingTestPing;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Console\Command;

class BroadcastTestCommand extends Command
{
    protected $signature = 'uniroute:broadcast-test
        {--mensaje=Ping de prueba de UNIROUTE : Mensaje que se transmitirá por el canal de pruebas}';

    protected $description = 'Transmite un evento de prueba por Reverb para verificar el broadcasting de extremo a extremo (S0-10)';

    public function handle(): int
    {
        $message = (string) $this->option('mensaje');

        try {
            broadcast(new TrackingTestPing($message));
        } catch (BroadcastException $exception) {
            $this->error('No se pudo transmitir el evento. Verifica que Reverb esté corriendo con "php artisan reverb:start".');

            return self::FAILURE;
        }

        $this->info('Evento transmitido correctamente.');
        $this->table(['Canal', 'Evento', 'Mensaje'], [
            [TrackingTestPing::CHANNEL, TrackingTestPing::class, $message],
        ]);

        return self::SUCCESS;
    }
}
