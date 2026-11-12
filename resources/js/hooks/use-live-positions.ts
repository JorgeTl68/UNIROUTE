import { router, usePoll } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { useState } from 'react';
import type {
    LiveVehicle,
    PositionSnapshot,
    TransitChannel,
    VehiclePositionUpdatedEvent,
} from '@/types/transit';

/** Cada cuánto se recargan los datos si no llegan eventos (respaldo). */
const POLL_MILLISECONDS = 15_000;

type LiveUpdate = { position: PositionSnapshot; occupancy: number };

function isNewer(a: PositionSnapshot | null, b: PositionSnapshot): boolean {
    return a === null || Date.parse(b.recorded_at) > Date.parse(a.recorded_at);
}

/**
 * Mantiene al día las unidades de una página (S3-02): mueve cada unidad con
 * VehiclePositionUpdated en cuanto llega y recarga las props indicadas cuando
 * cambia un viaje, hay una incidencia o pasan 15 s (usePoll de respaldo).
 */
export function useLivePositions(channel: TransitChannel, props: string[]) {
    const [updates, setUpdates] = useState<Record<number, LiveUpdate>>({});
    const [updatedAt, setUpdatedAt] = useState(() => Date.now());
    const reload = () =>
        router.reload({
            only: props,
            onSuccess: () => setUpdatedAt(Date.now()),
        });

    useEchoPublic<VehiclePositionUpdatedEvent>(
        channel,
        'VehiclePositionUpdated',
        (event) => {
            setUpdates((current) => ({
                ...current,
                [event.trip_id]: {
                    position: event.position,
                    occupancy: event.occupancy,
                },
            }));
            setUpdatedAt(Date.now());
        },
        [channel],
    );
    useEchoPublic(channel, ['TripStatusChanged', 'IncidentReported'], reload, [
        channel,
    ]);
    usePoll(POLL_MILLISECONDS, {
        only: props,
        onSuccess: () => setUpdatedAt(Date.now()),
    });

    /** La unidad con la posición más reciente entre la del servidor y el evento. */
    const withLive = (vehicle: LiveVehicle): LiveVehicle => {
        const update = updates[vehicle.trip_id];

        return update && isNewer(vehicle.position, update.position)
            ? {
                  ...vehicle,
                  position: update.position,
                  occupancy: update.occupancy,
              }
            : vehicle;
    };

    return { withLive, updatedAt };
}
