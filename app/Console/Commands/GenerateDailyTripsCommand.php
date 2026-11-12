<?php

namespace App\Console\Commands;

use App\Services\Tracking\DailyTripGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class GenerateDailyTripsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uniroute:generar-viajes
        {--fecha= : Fecha AAAA-MM-DD; por defecto, hoy}';

    /**
     * @var string
     */
    protected $description = 'Genera los viajes del día a partir de los horarios activos (S2-06)';

    public function handle(DailyTripGenerator $generator): int
    {
        $input = $this->option('fecha');
        $timezone = (string) config('app.timezone', 'UTC');

        try {
            $date = is_string($input) && $input !== ''
                ? CarbonImmutable::parse($input, $timezone)
                : CarbonImmutable::now($timezone);
        } catch (Throwable) {
            $this->error('La fecha debe tener el formato AAAA-MM-DD.');

            return self::FAILURE;
        }

        if (is_string($input) && $input !== '' && $date->format('Y-m-d') !== $input) {
            $this->error('La fecha debe tener el formato AAAA-MM-DD.');

            return self::FAILURE;
        }

        $result = $generator->generate($date);

        $this->info(sprintf(
            'Viajes del %s: %d nuevos, %d ya existían.',
            $date->format('Y-m-d'),
            $result['created'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
