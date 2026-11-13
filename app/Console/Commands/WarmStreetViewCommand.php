<?php

namespace App\Console\Commands;

use App\Models\Stop;
use App\Services\Transit\StreetView;
use Illuminate\Console\Command;

class WarmStreetViewCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uniroute:vista-calle';

    /**
     * @var string
     */
    protected $description = 'Busca en Mapillary la foto más cercana a cada parada y la deja lista para "Ver en 360°"';

    public function handle(StreetView $streetView): int
    {
        if (! $streetView->isConfigured()) {
            $this->warn('Falta MAPILLARY_TOKEN en el .env; las paradas mostrarán la vista satelital.');

            return self::SUCCESS;
        }

        $stops = Stop::query()->orderBy('name')->get();
        $withPhoto = 0;

        foreach ($stops as $stop) {
            $image = $streetView->refresh($stop);

            if ($image !== null) {
                $withPhoto++;
            }

            $this->line(sprintf(
                '%s: %s',
                $stop->name,
                $image === null ? 'sin foto (vista satelital)' : "foto a {$image['distance_meters']} m",
            ));
        }

        $this->info("Paradas con foto: {$withPhoto} de {$stops->count()}.");

        return self::SUCCESS;
    }
}
