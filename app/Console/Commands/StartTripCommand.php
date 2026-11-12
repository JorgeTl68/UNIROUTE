<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Services\Tracking\TripLifecycle;
use DomainException;
use Illuminate\Console\Command;

class StartTripCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uniroute:viaje:iniciar
        {trip : ID del viaje}';

    /**
     * @var string
     */
    protected $description = 'Marca un viaje programado como en curso (S2-06)';

    public function handle(TripLifecycle $lifecycle): int
    {
        $trip = Trip::find((int) $this->argument('trip'));

        if ($trip === null) {
            $this->error('No existe el viaje indicado.');

            return self::FAILURE;
        }

        try {
            $lifecycle->start($trip);
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Viaje #%d iniciado a las %s.',
            $trip->id,
            $trip->started_at?->format('H:i:s') ?? 'ahora',
        ));

        return self::SUCCESS;
    }
}
