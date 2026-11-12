<?php

namespace App\Console\Commands;

use App\Services\Tracking\PositionPruner;
use Illuminate\Console\Command;

class PruneVehiclePositionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uniroute:limpiar-posiciones
        {--dias=7 : Días de historial que se conservan}';

    /**
     * @var string
     */
    protected $description = 'Borra las posiciones de unidades con más de 7 días (S5-04)';

    public function handle(PositionPruner $pruner): int
    {
        $days = filter_var($this->option('dias'), FILTER_VALIDATE_INT);

        if ($days === false || $days < 1) {
            $this->error('Los días deben ser un entero mayor o igual a 1.');

            return self::FAILURE;
        }

        $deleted = $pruner->prune($days);

        $this->info(sprintf(
            'Posiciones eliminadas: %d (con más de %d días).',
            $deleted,
            $days,
        ));

        return self::SUCCESS;
    }
}
