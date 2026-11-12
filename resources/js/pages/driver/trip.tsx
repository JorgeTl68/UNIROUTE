import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarX2,
    LogOut,
    MapPin,
    Minus,
    Play,
    Plus,
    Satellite,
    Square,
    TriangleAlert,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import {
    occupancyPercent,
    occupancyTone,
} from '@/components/transit/occupancy-bar';
import { StatusChip } from '@/components/transit/status-chip';
import { cn } from '@/lib/utils';
import { clockTime } from '@/lib/transit-format';
import { logout } from '@/routes';
import type { TripStatus } from '@/types/transit';

type DriverTrip = {
    id: number;
    status: TripStatus;
    scheduled_at: string;
    started_at: string | null;
    occupancy: number;
    route: {
        id: number;
        number: number;
        name: string;
        color: string;
        first_stop: string | null;
    };
    vehicle: {
        id: number;
        code: string;
        plate: string;
        capacity: number;
    } | null;
    next_stop: {
        name: string;
        eta_minutes: number;
        is_delayed: boolean;
    } | null;
};

type Props = {
    trip: DriverTrip | null;
    trips: {
        id: number;
        scheduled_at: string;
        status: TripStatus;
        route_number: number;
    }[];
};

type GpsState = 'waiting' | 'active' | 'denied' | 'unavailable';

/** Cada cuánto se manda la ubicación mientras el viaje está en curso. */
const SEND_EVERY_MILLISECONDS = 5_000;

const gpsLabels: Record<GpsState, { label: string; className: string }> = {
    waiting: {
        label: 'GPS en espera',
        className: 'bg-secondary text-muted-foreground',
    },
    active: { label: 'GPS activo', className: 'bg-live/15 text-live' },
    denied: {
        label: 'GPS sin permiso',
        className: 'bg-destructive/15 text-destructive',
    },
    unavailable: {
        label: 'Sin GPS',
        className: 'bg-warning/15 text-warning',
    },
};

const tripLabels: Record<
    TripStatus,
    { label: string; tone: 'live' | 'neutral' | 'danger' }
> = {
    scheduled: { label: 'Programado', tone: 'neutral' },
    in_progress: { label: 'En ruta', tone: 'live' },
    completed: { label: 'Terminado', tone: 'neutral' },
    cancelled: { label: 'Cancelado', tone: 'danger' },
};

/** Token CSRF de la cookie de Laravel para las peticiones con fetch. */
function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

function elapsed(since: string, now: number): string {
    const seconds = Math.max(0, Math.floor((now - Date.parse(since)) / 1000));
    const minutes = Math.floor(seconds / 60);

    return `${String(minutes).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
}

/** Manda la ubicación del navegador mientras el viaje está en curso. */
function useTripGps(trip: DriverTrip | null) {
    const inProgress = trip?.status === 'in_progress';
    const tripId = trip?.id;
    const [state, setState] = useState<GpsState>('waiting');
    const [lastSent, setLastSent] = useState<number | null>(null);
    const latest = useRef<GeolocationPosition | null>(null);

    useEffect(() => {
        if (!inProgress || tripId === undefined) return;

        if (!navigator.geolocation) {
            setState('unavailable');

            return;
        }

        const watch = navigator.geolocation.watchPosition(
            (position) => {
                latest.current = position;
                setState('active');
            },
            (error) =>
                setState(
                    error.code === error.PERMISSION_DENIED
                        ? 'denied'
                        : 'unavailable',
                ),
            { enableHighAccuracy: true, maximumAge: 2_000 },
        );
        const timer = window.setInterval(() => {
            const position = latest.current;

            if (!position) return;

            void fetch(`/driver/trips/${tripId}/positions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    speed_kmh:
                        position.coords.speed === null
                            ? null
                            : position.coords.speed * 3.6,
                    heading: position.coords.heading,
                }),
            }).then((response) => {
                if (response.ok) setLastSent(Date.now());
            });
        }, SEND_EVERY_MILLISECONDS);

        return () => {
            navigator.geolocation.clearWatch(watch);
            window.clearInterval(timer);
        };
    }, [inProgress, tripId]);

    return { state: inProgress ? state : 'waiting', lastSent };
}

function OccupancyControl({ trip }: { trip: DriverTrip }) {
    const capacity = trip.vehicle?.capacity ?? 0;
    const [count, setCount] = useState(trip.occupancy);
    const pending = useRef<number | null>(null);
    const percent = occupancyPercent(count, capacity);

    const change = (delta: number) => {
        const next = Math.max(0, Math.min(capacity, count + delta));

        setCount(next);

        if (pending.current) window.clearTimeout(pending.current);

        // Se manda al dejar de presionar para no llenar la red de peticiones.
        pending.current = window.setTimeout(() => {
            router.patch(
                `/driver/trips/${trip.id}/occupancy`,
                { occupancy: next },
                { preserveScroll: true, preserveState: true, only: ['trip'] },
            );
        }, 600);
    };

    return (
        <section
            className="mt-4 rounded-2xl border border-border bg-card p-5"
            aria-labelledby="occupancy-title"
        >
            <div className="flex items-center justify-between">
                <h2 id="occupancy-title" className="text-base font-bold">
                    Pasajeros a bordo
                </h2>
                <span className="rounded-full bg-gold/15 px-2 py-0.5 text-[11px] font-bold text-gold">
                    {percent} %
                </span>
            </div>
            <div className="mt-4 flex items-center justify-between gap-4">
                <button
                    type="button"
                    onClick={() => change(-1)}
                    disabled={count === 0}
                    className="flex size-20 items-center justify-center rounded-2xl border border-border bg-secondary text-foreground active:scale-95 disabled:opacity-40"
                    aria-label="Bajó un pasajero"
                >
                    <Minus className="size-8" />
                </button>
                <p className="text-center" aria-live="polite">
                    <span className="block text-6xl font-extrabold text-white">
                        {count}
                    </span>
                    <span className="text-sm text-muted-foreground">
                        de {capacity}
                    </span>
                </p>
                <button
                    type="button"
                    onClick={() => change(1)}
                    disabled={count === capacity}
                    className="flex size-20 items-center justify-center rounded-2xl bg-primary text-primary-foreground active:scale-95 disabled:opacity-40"
                    aria-label="Subió un pasajero"
                >
                    <Plus className="size-8" />
                </button>
            </div>
            <div className="mt-4 h-2 overflow-hidden rounded-full bg-secondary">
                <div
                    className={cn(
                        'h-full rounded-full transition-all',
                        occupancyTone(percent),
                    )}
                    style={{ width: `${percent}%` }}
                />
            </div>
        </section>
    );
}

export default function DriverTripPage({ trip, trips }: Props) {
    const { auth, errors } = usePage().props;
    const { state, lastSent } = useTripGps(trip);
    const [now, setNow] = useState(() => Date.now());
    const inProgress = trip?.status === 'in_progress';

    useEffect(() => {
        if (!inProgress) return;

        const timer = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(timer);
    }, [inProgress]);

    const gps = gpsLabels[state];
    const delayed = trip?.next_stop?.is_delayed ?? false;

    return (
        <div className="mx-auto min-h-screen w-full max-w-md bg-background px-4 py-5 text-foreground">
            <Head title="Modo conductor" />

            <header className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-2.5">
                    <img
                        src="/branding/isotipo-oscuro.svg"
                        alt=""
                        className="size-9"
                    />
                    <div>
                        <p className="text-base leading-none font-bold text-white">
                            Modo conductor
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {auth.user.name}
                        </p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <span
                        className={cn(
                            'flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold',
                            gps.className,
                        )}
                    >
                        <Satellite className="size-3.5" />
                        {gps.label}
                    </span>
                    <Link
                        href={logout()}
                        as="button"
                        className="flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-secondary hover:text-foreground"
                        aria-label="Cerrar sesión"
                    >
                        <LogOut className="size-4" />
                    </Link>
                </div>
            </header>

            {trip === null ? (
                <section className="mt-5 flex flex-col items-center gap-3 rounded-2xl border border-border bg-card px-6 py-12 text-center">
                    <CalendarX2 className="size-8 text-muted-foreground" />
                    <p className="font-semibold text-foreground">
                        No tienes viajes pendientes hoy
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Cuando el área de transporte te asigne uno, aparecerá
                        aquí.
                    </p>
                </section>
            ) : (
                <>
                    <section className="mt-5 rounded-2xl border border-border bg-card p-5">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-[11px] font-semibold tracking-wider text-gold uppercase">
                                    Viaje de las {clockTime(trip.scheduled_at)}
                                </p>
                                <h1 className="mt-1 text-xl font-bold">
                                    Ruta {trip.route.number} · {trip.route.name}
                                </h1>
                                {trip.vehicle && (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {trip.vehicle.code} ·{' '}
                                        {trip.vehicle.plate} ·{' '}
                                        {trip.vehicle.capacity} lugares
                                    </p>
                                )}
                            </div>
                            <span
                                className="flex size-10 shrink-0 items-center justify-center rounded-md text-base font-extrabold text-primary-foreground"
                                style={{ backgroundColor: trip.route.color }}
                            >
                                {trip.route.number}
                            </span>
                        </div>

                        {errors.trip && (
                            <p className="mt-4 rounded-xl bg-destructive/10 p-3 text-sm text-destructive">
                                {errors.trip}
                            </p>
                        )}

                        {inProgress ? (
                            <div className="mt-5 space-y-4">
                                <div className="flex items-center justify-between rounded-xl bg-live/10 px-4 py-3">
                                    <span className="flex items-center gap-2 text-sm font-semibold text-live">
                                        <span className="relative flex size-2.5">
                                            <span className="absolute inline-flex size-full rounded-full bg-live opacity-75 motion-safe:animate-ping" />
                                            <span className="relative inline-flex size-2.5 rounded-full bg-live" />
                                        </span>
                                        En ruta · enviando ubicación
                                    </span>
                                    {lastSent && (
                                        <span className="text-xs text-muted-foreground">
                                            hace{' '}
                                            {Math.max(
                                                0,
                                                Math.round(
                                                    (now - lastSent) / 1000,
                                                ),
                                            )}{' '}
                                            s
                                        </span>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="rounded-xl bg-background/50 p-3">
                                        <p className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                            Siguiente parada
                                        </p>
                                        <p className="mt-1 truncate text-base font-bold text-white">
                                            {trip.next_stop?.name ?? '—'}
                                        </p>
                                        {trip.next_stop && (
                                            <p className="text-xs text-primary">
                                                ETA {trip.next_stop.eta_minutes}{' '}
                                                min
                                            </p>
                                        )}
                                    </div>
                                    <div className="rounded-xl bg-background/50 p-3">
                                        <p className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                            Tiempo de viaje
                                        </p>
                                        <p className="mt-1 text-base font-bold text-white tabular-nums">
                                            {trip.started_at
                                                ? elapsed(trip.started_at, now)
                                                : '—'}
                                        </p>
                                        <p
                                            className={cn(
                                                'text-xs',
                                                delayed
                                                    ? 'text-warning'
                                                    : 'text-live',
                                            )}
                                        >
                                            {delayed ? 'Demorado' : 'A tiempo'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="mt-5 space-y-3">
                                {trip.route.first_stop && (
                                    <div className="flex items-start gap-3 rounded-xl bg-primary/10 p-3 text-sm">
                                        <MapPin className="mt-0.5 size-4 shrink-0 text-primary" />
                                        <span className="text-muted-foreground">
                                            Sales de{' '}
                                            <span className="font-semibold text-foreground">
                                                {trip.route.first_stop}
                                            </span>
                                            . El navegador te va a pedir permiso
                                            para usar tu ubicación.
                                        </span>
                                    </div>
                                )}
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.post(
                                            `/driver/trips/${trip.id}/start`,
                                        )
                                    }
                                    className="flex w-full items-center justify-center gap-3 rounded-2xl bg-primary py-5 text-lg font-extrabold text-primary-foreground shadow-lg shadow-black/30 active:scale-[0.98]"
                                >
                                    <Play className="size-6 fill-current" />
                                    Iniciar viaje
                                </button>
                            </div>
                        )}
                    </section>

                    {inProgress && (
                        <>
                            <OccupancyControl key={trip.id} trip={trip} />
                            <section className="mt-4 grid grid-cols-2 gap-3">
                                <Link
                                    href="/emergency/create"
                                    className="flex items-center justify-center gap-2 rounded-xl border border-border bg-card py-4 text-sm font-semibold text-foreground"
                                >
                                    <TriangleAlert className="size-5 text-warning" />
                                    Reportar incidencia
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (
                                            window.confirm(
                                                '¿Terminar el viaje? Se dejará de enviar tu ubicación.',
                                            )
                                        ) {
                                            router.post(
                                                `/driver/trips/${trip.id}/finish`,
                                            );
                                        }
                                    }}
                                    className="flex items-center justify-center gap-2 rounded-xl border border-destructive/50 bg-destructive/10 py-4 text-sm font-bold text-destructive"
                                >
                                    <Square className="size-5 fill-current" />
                                    Terminar viaje
                                </button>
                            </section>
                        </>
                    )}
                </>
            )}

            {trips.length > 0 && (
                <section className="mt-4 rounded-2xl border border-border bg-card p-5">
                    <h2 className="text-base font-bold">Tus viajes de hoy</h2>
                    <ul className="mt-3 divide-y divide-border text-sm">
                        {trips.map((item) => {
                            const done =
                                item.status === 'completed' ||
                                item.status === 'cancelled';

                            return (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <span
                                        className={
                                            done
                                                ? 'text-muted-foreground'
                                                : 'text-foreground'
                                        }
                                    >
                                        <span
                                            className={cn(
                                                'font-bold',
                                                done
                                                    ? 'text-placeholder line-through'
                                                    : 'text-white',
                                            )}
                                        >
                                            {clockTime(item.scheduled_at)}
                                        </span>{' '}
                                        · Ruta {item.route_number}
                                    </span>
                                    <StatusChip
                                        tone={tripLabels[item.status].tone}
                                        label={tripLabels[item.status].label}
                                    />
                                </li>
                            );
                        })}
                    </ul>
                </section>
            )}
        </div>
    );
}
