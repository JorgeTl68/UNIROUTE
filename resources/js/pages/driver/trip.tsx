import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import {
    BellRing,
    BusFront,
    CalendarX2,
    Check,
    Flag,
    LogOut,
    MapPin,
    Menu,
    Minus,
    Play,
    Plus,
    Satellite,
    Settings,
    Square,
    TriangleAlert,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { EmergencyDialog } from '@/components/emergency-dialog';
import {
    occupancyPercent,
    occupancyTone,
} from '@/components/transit/occupancy-bar';
import { StatusChip } from '@/components/transit/status-chip';
import { TransitMap } from '@/components/transit/transit-map';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { clockTime } from '@/lib/transit-format';
import { logout } from '@/routes';
import type {
    PositionSnapshot,
    StopState,
    TimelineStop,
    TripStatus,
} from '@/types/transit';

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
        encoded_polyline: string | null;
        first_stop: string | null;
        last_stop: string | null;
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
    stops: TimelineStop[];
    position: PositionSnapshot | null;
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

/** Cada cuánto se actualizan la siguiente parada y la ETA. */
const REFRESH_MILLISECONDS = 15_000;

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

/**
 * Manda la ubicación del navegador mientras el viaje está en curso y la
 * devuelve para dibujar la unidad en el mapa sin esperar al servidor.
 */
function useTripGps(trip: DriverTrip | null) {
    const inProgress = trip?.status === 'in_progress';
    const tripId = trip?.id;
    const [state, setState] = useState<GpsState>('waiting');
    const [lastSent, setLastSent] = useState<number | null>(null);
    const [position, setPosition] = useState<PositionSnapshot | null>(null);
    const latest = useRef<GeolocationPosition | null>(null);

    useEffect(() => {
        if (!inProgress || tripId === undefined) return;

        if (!navigator.geolocation) {
            setState('unavailable');

            return;
        }

        const watch = navigator.geolocation.watchPosition(
            (current) => {
                latest.current = current;
                setState('active');
                setPosition({
                    latitude: current.coords.latitude,
                    longitude: current.coords.longitude,
                    speed_kmh:
                        current.coords.speed === null
                            ? null
                            : current.coords.speed * 3.6,
                    heading: current.coords.heading,
                    recorded_at: new Date(current.timestamp).toISOString(),
                });
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
            const current = latest.current;

            if (!current) return;

            void fetch(`/driver/trips/${tripId}/positions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({
                    latitude: current.coords.latitude,
                    longitude: current.coords.longitude,
                    speed_kmh:
                        current.coords.speed === null
                            ? null
                            : current.coords.speed * 3.6,
                    heading: current.coords.heading,
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

    return {
        state: inProgress ? state : 'waiting',
        lastSent,
        position: inProgress ? position : null,
    };
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
            className="rounded-2xl border border-border bg-card p-5"
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

const stopMarkers: Record<StopState, string> = {
    passed: 'bg-route text-primary-foreground',
    current: 'bg-gold text-primary-foreground ring-4 ring-gold/30',
    next: 'bg-gold text-primary-foreground ring-4 ring-gold/30',
    pending: 'border-2 border-border bg-card text-muted-foreground',
};

/** Paradas del recorrido: ya pasó, siguiente y pendientes. */
function StopsList({ stops }: { stops: TimelineStop[] }) {
    return (
        <section className="rounded-2xl border border-border bg-card p-5">
            <h2 className="text-base font-bold">Paradas del recorrido</h2>
            <ol className="relative mt-4 space-y-3">
                <span
                    className="absolute top-3 bottom-3 left-[13px] w-0.5 bg-border"
                    aria-hidden="true"
                />
                {stops.map((stop, index) => {
                    const isLast = index === stops.length - 1;
                    const Icon =
                        stop.state === 'passed'
                            ? Check
                            : stop.state === 'current' || stop.state === 'next'
                              ? BusFront
                              : isLast
                                ? Flag
                                : MapPin;
                    const ahead =
                        stop.state === 'next' || stop.state === 'current';

                    return (
                        <li
                            key={stop.id}
                            className="relative flex items-center gap-3"
                        >
                            <span
                                className={cn(
                                    'relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full',
                                    stopMarkers[stop.state],
                                )}
                            >
                                <Icon className="size-3.5" />
                            </span>
                            <span
                                className={cn(
                                    'min-w-0 flex-1 truncate text-sm',
                                    ahead
                                        ? 'font-bold text-white'
                                        : stop.state === 'passed'
                                          ? 'text-muted-foreground line-through decoration-muted-foreground/40'
                                          : 'text-foreground',
                                )}
                            >
                                {stop.name}
                            </span>
                            {stop.arrives_at && (
                                <span
                                    className={cn(
                                        'shrink-0 text-xs font-semibold tabular-nums',
                                        ahead
                                            ? 'text-gold'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {clockTime(stop.arrives_at)}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}

function DriverMenu() {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="flex size-10 items-center justify-center rounded-xl border border-border bg-card text-foreground hover:bg-secondary"
                aria-label="Menú del conductor"
            >
                <Menu className="size-5" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-52">
                <DropdownMenuItem asChild>
                    <Link href="/notifications">
                        <BellRing className="size-4" />
                        Mis notificaciones
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href="/settings/profile">
                        <Settings className="size-4" />
                        Perfil y ajustes
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link
                        href={logout()}
                        as="button"
                        className="w-full"
                        onClick={() => router.flushAll()}
                    >
                        <LogOut className="size-4" />
                        Cerrar sesión
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * Modo conductor (S3-07): pantalla de trabajo dentro de la unidad, distinta
 * del resto de la app. Barra de estado del viaje, mapa de su ruta, controles
 * grandes y las paradas del recorrido.
 */
export default function DriverTripPage({ trip, trips }: Props) {
    const { auth, errors } = usePage().props;
    const gps = useTripGps(trip);
    const [now, setNow] = useState(() => Date.now());
    const inProgress = trip?.status === 'in_progress';

    usePoll(REFRESH_MILLISECONDS, { only: ['trip'] });

    useEffect(() => {
        if (!inProgress) return;

        const timer = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(timer);
    }, [inProgress]);

    const mapRoutes = useMemo(
        () =>
            trip
                ? [
                      {
                          id: trip.route.id,
                          color: trip.route.color,
                          encoded_polyline: trip.route.encoded_polyline,
                          stops: trip.stops,
                      },
                  ]
                : [],
        [trip],
    );
    const unitPosition = gps.position ?? trip?.position ?? null;
    const delayed = trip?.next_stop?.is_delayed ?? false;
    const gpsLabel = gpsLabels[gps.state];

    const status = !trip
        ? {
              label: 'Sin viaje asignado',
              className: 'bg-secondary text-muted-foreground',
          }
        : inProgress
          ? {
                label: delayed ? 'En ruta · demorado' : 'En ruta',
                className: delayed
                    ? 'bg-warning text-primary-foreground'
                    : 'bg-live text-primary-foreground',
            }
          : {
                label: `Listo para salir · ${clockTime(trip.scheduled_at)}`,
                className: 'bg-gold text-primary-foreground',
            };

    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title="Modo conductor" />

            <header className="sticky top-0 z-[1100] border-b border-border bg-card/95 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4">
                    <div className="flex min-w-0 items-center gap-3">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                            <BusFront className="size-5" />
                        </span>
                        <div className="min-w-0">
                            <p className="text-[11px] font-bold tracking-widest text-gold uppercase">
                                Modo conductor
                            </p>
                            <p className="truncate font-bold text-white">
                                {auth.user.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <span
                            className={cn(
                                'hidden items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold sm:flex',
                                gpsLabel.className,
                            )}
                        >
                            <Satellite className="size-3.5" />
                            {gpsLabel.label}
                        </span>
                        <DriverMenu />
                    </div>
                </div>
                <div
                    className={cn(
                        'flex items-center justify-center gap-2 py-2 text-sm font-extrabold tracking-wider uppercase',
                        status.className,
                    )}
                    role="status"
                >
                    {inProgress && (
                        <span className="size-2 rounded-full bg-current motion-safe:animate-pulse" />
                    )}
                    {status.label}
                </div>
            </header>

            {trip === null ? (
                <main className="mx-auto max-w-md px-4 py-10">
                    <section className="flex flex-col items-center gap-3 rounded-2xl border border-border bg-card px-6 py-12 text-center">
                        <CalendarX2 className="size-10 text-muted-foreground" />
                        <p className="text-lg font-bold text-white">
                            No tienes viajes pendientes hoy
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Cuando el área de transporte te asigne uno,
                            aparecerá aquí.
                        </p>
                    </section>
                </main>
            ) : (
                <main className="mx-auto grid max-w-6xl gap-4 px-4 py-4 lg:grid-cols-[minmax(0,1fr)_420px] lg:gap-6 lg:py-6">
                    <div className="space-y-4">
                        <div className="overflow-hidden rounded-2xl border border-border">
                            <TransitMap
                                center={
                                    unitPosition
                                        ? [
                                              unitPosition.latitude,
                                              unitPosition.longitude,
                                          ]
                                        : trip.stops[0]
                                          ? [
                                                trip.stops[0].latitude,
                                                trip.stops[0].longitude,
                                            ]
                                          : [25.4232, -101.0005]
                                }
                                routes={mapRoutes}
                                selectedRouteId={trip.route.id}
                                stopStates={Object.fromEntries(
                                    trip.stops.map((stop) => [
                                        stop.id,
                                        stop.state,
                                    ]),
                                )}
                                vehicles={
                                    trip.vehicle
                                        ? [
                                              {
                                                  trip_id: trip.id,
                                                  route_id: trip.route.id,
                                                  code: trip.vehicle.code,
                                                  position: unitPosition,
                                              },
                                          ]
                                        : []
                                }
                                highlightedTripId={trip.id}
                                controlsClassName="bottom-3"
                                className="h-64 sm:h-80 lg:h-[460px]"
                            />
                        </div>
                        <div className="hidden lg:block">
                            <StopsList stops={trip.stops} />
                        </div>
                    </div>

                    <div className="space-y-4">
                        <section className="rounded-2xl border border-border bg-card p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-[11px] font-semibold tracking-wider text-gold uppercase">
                                        Viaje de las{' '}
                                        {clockTime(trip.scheduled_at)}
                                    </p>
                                    <h1 className="mt-1 text-xl font-bold">
                                        Ruta {trip.route.number} ·{' '}
                                        {trip.route.name}
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
                                    className="flex size-12 shrink-0 items-center justify-center rounded-xl text-lg font-extrabold text-primary-foreground"
                                    style={{
                                        backgroundColor: trip.route.color,
                                    }}
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
                                <div className="mt-5 space-y-3">
                                    <div className="rounded-xl bg-background/60 p-4">
                                        <p className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                            Siguiente parada
                                        </p>
                                        <div className="mt-1 flex items-end justify-between gap-3">
                                            <p className="min-w-0 text-2xl leading-tight font-extrabold text-white">
                                                {trip.next_stop?.name ??
                                                    trip.route.last_stop ??
                                                    '—'}
                                            </p>
                                            {trip.next_stop && (
                                                <p className="shrink-0 text-right">
                                                    <span className="block text-4xl font-extrabold text-gold">
                                                        {
                                                            trip.next_stop
                                                                .eta_minutes
                                                        }
                                                    </span>
                                                    <span className="text-xs font-semibold text-muted-foreground">
                                                        min
                                                    </span>
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3 text-sm">
                                        <div className="rounded-xl bg-background/60 p-3">
                                            <p className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Tiempo de viaje
                                            </p>
                                            <p className="mt-1 text-lg font-bold text-white tabular-nums">
                                                {trip.started_at
                                                    ? elapsed(
                                                          trip.started_at,
                                                          now,
                                                      )
                                                    : '—'}
                                            </p>
                                        </div>
                                        <div className="rounded-xl bg-background/60 p-3">
                                            <p className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                                Ubicación enviada
                                            </p>
                                            <p className="mt-1 text-lg font-bold text-white tabular-nums">
                                                {gps.lastSent
                                                    ? `hace ${Math.max(0, Math.round((now - gps.lastSent) / 1000))} s`
                                                    : '—'}
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
                                                . El navegador te va a pedir
                                                permiso para usar tu ubicación.
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
                                        className="flex w-full items-center justify-center gap-3 rounded-2xl bg-primary py-6 text-xl font-extrabold text-primary-foreground shadow-lg shadow-black/30 active:scale-[0.98]"
                                    >
                                        <Play className="size-7 fill-current" />
                                        Iniciar viaje
                                    </button>
                                </div>
                            )}
                        </section>

                        {inProgress && (
                            <>
                                <OccupancyControl key={trip.id} trip={trip} />
                                <section className="grid grid-cols-2 gap-3">
                                    <EmergencyDialog>
                                        <button
                                            type="button"
                                            className="flex items-center justify-center gap-2 rounded-xl border border-border bg-card py-4 text-sm font-semibold text-foreground"
                                        >
                                            <TriangleAlert className="size-5 text-warning" />
                                            Reportar incidencia
                                        </button>
                                    </EmergencyDialog>
                                    <ConfirmDialog
                                        title="¿Terminar el viaje?"
                                        description="Se dejará de enviar tu ubicación y el viaje quedará como completado."
                                        confirmLabel="Terminar viaje"
                                        destructive
                                        onConfirm={() =>
                                            router.post(
                                                `/driver/trips/${trip.id}/finish`,
                                            )
                                        }
                                    >
                                        <button
                                            type="button"
                                            className="flex items-center justify-center gap-2 rounded-xl border border-destructive/50 bg-destructive/10 py-4 text-sm font-bold text-destructive"
                                        >
                                            <Square className="size-5 fill-current" />
                                            Terminar viaje
                                        </button>
                                    </ConfirmDialog>
                                </section>
                            </>
                        )}

                        <div className="lg:hidden">
                            <StopsList stops={trip.stops} />
                        </div>

                        {trips.length > 0 && (
                            <section className="rounded-2xl border border-border bg-card p-5">
                                <h2 className="text-base font-bold">
                                    Tus viajes de hoy
                                </h2>
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
                                                        {clockTime(
                                                            item.scheduled_at,
                                                        )}
                                                    </span>{' '}
                                                    · Ruta {item.route_number}
                                                </span>
                                                <StatusChip
                                                    tone={
                                                        tripLabels[item.status]
                                                            .tone
                                                    }
                                                    label={
                                                        tripLabels[item.status]
                                                            .label
                                                    }
                                                />
                                            </li>
                                        );
                                    })}
                                </ul>
                            </section>
                        )}
                    </div>
                </main>
            )}
        </div>
    );
}
