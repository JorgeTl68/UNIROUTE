import { Head, Link, router } from '@inertiajs/react';
import {
    Accessibility,
    ArrowLeft,
    BellRing,
    BusFront,
    Check,
    Clock3,
    Compass,
    Flag,
    MapPin,
    Share2,
    Snowflake,
    Star,
    Trash2,
    TriangleAlert,
    Wifi,
} from 'lucide-react';
import { useMemo } from 'react';
import { toast } from 'sonner';
import { EmergencyDialog } from '@/components/emergency-dialog';
import { ArrivalAlarmDialog } from '@/components/transit/arrival-alarm-dialog';
import { occupancyPercent } from '@/components/transit/occupancy-bar';
import { StatusChip } from '@/components/transit/status-chip';
import { StreetViewDialog } from '@/components/transit/street-view-dialog';
import { TransitMap } from '@/components/transit/transit-map';
import { UpdatedAgo } from '@/components/transit/updated-ago';
import { useLivePositions } from '@/hooks/use-live-positions';
import { cn } from '@/lib/utils';
import { clockTime } from '@/lib/transit-format';
import type {
    RouteDetailProps,
    StopState,
    TimelineStop,
    TripStatus,
    VehicleAmenity,
} from '@/types/transit';

const amenities: Record<VehicleAmenity, { label: string; icon: typeof Wifi }> =
    {
        air_conditioning: { label: 'Clima', icon: Snowflake },
        wifi: { label: 'Wi-Fi', icon: Wifi },
        accessible_ramp: { label: 'Rampa', icon: Accessibility },
    };

const tripStatus: Record<
    TripStatus,
    { label: string; tone: 'live' | 'neutral' | 'danger' }
> = {
    scheduled: { label: 'Programado', tone: 'neutral' },
    in_progress: { label: 'En ruta', tone: 'live' },
    completed: { label: 'Completado', tone: 'neutral' },
    cancelled: { label: 'Cancelado', tone: 'danger' },
};

const compass = [
    'norte',
    'noreste',
    'este',
    'sureste',
    'sur',
    'suroeste',
    'oeste',
    'noroeste',
];

/** Rumbo en grados a texto: 135 → "sureste". */
function headingLabel(heading: number): string {
    return compass[Math.round(heading / 45) % 8];
}

/** Hora de llegada programada a una parada desde la salida del viaje. */
function stopTime(stop: TimelineStop): string | null {
    return stop.arrives_at ? clockTime(stop.arrives_at) : null;
}

function TimelineItem({
    stop,
    isLast,
    vehicleCode,
}: {
    stop: TimelineStop;
    isLast: boolean;
    vehicleCode: string | null;
}) {
    const marker: Record<StopState, string> = {
        passed: 'bg-route text-ink',
        current: 'bg-primary text-primary-foreground ring-4 ring-gold/30',
        next: 'border-2 border-gold bg-card text-gold',
        pending: 'border-2 border-border bg-card text-muted-foreground',
    };
    const Icon =
        stop.state === 'passed'
            ? Check
            : stop.state === 'current'
              ? BusFront
              : isLast
                ? Flag
                : MapPin;

    return (
        <li className="relative flex gap-4">
            <span
                className={cn(
                    'relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full',
                    marker[stop.state],
                )}
            >
                <Icon className="size-4" />
            </span>
            {stop.state === 'current' ? (
                <div className="flex-1 rounded-xl border border-primary/40 bg-primary/10 p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-lg font-bold text-strong">
                            {stop.name}
                        </p>
                        <span className="rounded bg-primary px-2 py-0.5 text-[10px] font-extrabold tracking-wider text-primary-foreground uppercase">
                            Unidad en la parada
                        </span>
                    </div>
                    {vehicleCode && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {vehicleCode} está recogiendo pasajeros.
                        </p>
                    )}
                    <StreetViewDialog stop={stop} className="mt-2" />
                </div>
            ) : (
                <div
                    className={cn(
                        'flex-1 rounded-lg px-4 py-3',
                        stop.state === 'next'
                            ? 'border border-gold/40 bg-gold/10'
                            : 'bg-background/50',
                    )}
                >
                    <div className="flex items-start justify-between gap-3">
                        <p className="flex flex-wrap items-center gap-2 text-sm font-semibold text-foreground">
                            {stop.name}
                            {stop.state === 'next' && (
                                <span className="rounded bg-gold/15 px-1.5 py-0.5 text-[10px] font-semibold text-gold">
                                    Siguiente parada
                                </span>
                            )}
                        </p>
                        {stopTime(stop) && (
                            <span className="shrink-0 text-xs font-semibold text-foreground">
                                {stopTime(stop)}
                                <span className="text-muted-foreground">
                                    {' '}
                                    ({stop.eta_minutes} min)
                                </span>
                            </span>
                        )}
                    </div>
                    <p
                        className={cn(
                            'mt-0.5 text-xs',
                            stop.state === 'passed'
                                ? 'text-live'
                                : 'text-muted-foreground',
                        )}
                    >
                        {[
                            stop.state === 'passed' && 'Ya pasó',
                            stop.state !== 'passed' &&
                                `A ${stop.minutes_from_start} min del inicio`,
                            stop.has_shelter && 'Parada con techo',
                            isLast && 'Fin del recorrido',
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                    <StreetViewDialog stop={stop} className="mt-1.5" />
                </div>
            )}
        </li>
    );
}

export default function RouteShow({
    route,
    stops,
    vehicles: serverVehicles,
    upcoming_departures,
    routes,
    alarms,
    incidents,
    default_alarm_minutes,
}: RouteDetailProps) {
    const { withLive, updatedAt } = useLivePositions(`route.${route.id}`, [
        'route',
        'stops',
        'vehicles',
        'upcoming_departures',
        'incidents',
    ]);
    const vehicles = serverVehicles.map(withLive);
    const lead = vehicles[0] ?? null;
    const nextStop = stops.find(
        (stop) => stop.state === 'next' || stop.state === 'current',
    );
    const passed = stops.filter((stop) => stop.state === 'passed').length;
    const percent = lead
        ? occupancyPercent(lead.occupancy, lead.vehicle.capacity)
        : null;
    const stopStates = Object.fromEntries(
        stops.map((stop) => [stop.id, stop.state]),
    );
    const mapRoutes = useMemo(
        () => [
            {
                id: route.id,
                color: route.color,
                encoded_polyline: route.encoded_polyline,
                stops,
            },
        ],
        [route.id, route.color, route.encoded_polyline, stops],
    );
    const stopName = (stopId: number) =>
        stops.find((stop) => stop.id === stopId)?.name ?? 'Parada';

    const toggleFavorite = () => {
        const url = `/routes/${route.id}/favorite`;
        const options = {
            preserveScroll: true,
            onSuccess: () =>
                toast.success(
                    route.is_favorite
                        ? 'Ruta quitada de favoritas'
                        : 'Ruta agregada a favoritas',
                ),
        };

        if (route.is_favorite) {
            router.delete(url, options);
        } else {
            router.post(url, {}, options);
        }
    };
    const share = async () => {
        try {
            await navigator.clipboard.writeText(window.location.href);
            toast.success('Enlace copiado');
        } catch {
            toast.error('No se pudo copiar el enlace');
        }
    };

    const metrics = [
        {
            label: 'Próximo ETA',
            value:
                lead?.next_stop != null ? (
                    <span className="text-live">
                        {lead.next_stop.eta_minutes}{' '}
                        <span className="text-sm">min</span>
                    </span>
                ) : (
                    '—'
                ),
            hint: lead?.next_stop?.name ?? 'Sin unidad en ruta',
        },
        {
            label: 'Aforo actual',
            value: lead ? (
                <>
                    {lead.occupancy}
                    <span className="text-sm text-muted-foreground">
                        {' '}
                        / {lead.vehicle.capacity}
                    </span>
                </>
            ) : (
                '—'
            ),
            hint: percent === null ? '—' : `${percent} % ocupación`,
        },
        {
            label: 'Velocidad',
            value:
                lead?.position?.speed_kmh != null ? (
                    <>
                        {Math.round(lead.position.speed_kmh)}{' '}
                        <span className="text-sm">km/h</span>
                    </>
                ) : (
                    '—'
                ),
            hint:
                lead?.position?.heading != null
                    ? `Rumbo ${headingLabel(lead.position.heading)}`
                    : '—',
        },
        {
            label: 'Retraso',
            value: lead ? (
                <>
                    {Math.max(0, lead.delay_minutes)}{' '}
                    <span className="text-sm">min</span>
                </>
            ) : (
                '—'
            ),
            hint: lead ? (lead.is_delayed ? 'Demorado' : 'A tiempo') : '—',
            hintClass: lead
                ? lead.is_delayed
                    ? 'text-warning'
                    : 'text-live'
                : undefined,
        },
    ];

    return (
        <main className="flex-1 space-y-6 px-4 py-6 pb-24 sm:px-6 md:pb-6 lg:px-8">
            <Head title={`Ruta ${route.number} · ${route.name}`} />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex min-w-0 items-center gap-3">
                    <Link
                        href="/dashboard"
                        className="flex shrink-0 items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-semibold text-foreground hover:bg-secondary"
                    >
                        <ArrowLeft className="size-4" />
                        <span className="hidden sm:inline">
                            Volver al dashboard
                        </span>
                    </Link>
                    <label className="min-w-0">
                        <span className="sr-only">Cambiar de ruta</span>
                        <select
                            value={route.id}
                            onChange={(event) =>
                                router.visit(`/routes/${event.target.value}`)
                            }
                            className="w-full max-w-72 truncate rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                        >
                            {routes.map((option) => (
                                <option key={option.id} value={option.id}>
                                    Ruta {option.number} · {option.name}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>
                <div className="flex items-center gap-2">
                    {lead && (
                        <span className="hidden items-center gap-2 rounded-full bg-live/15 px-3 py-1.5 text-xs font-semibold text-live sm:flex">
                            <span className="size-1.5 rounded-full bg-live motion-safe:animate-pulse" />
                            GPS activo · {lead.vehicle.code}
                        </span>
                    )}
                    <button
                        type="button"
                        onClick={toggleFavorite}
                        className="flex size-9 items-center justify-center rounded-md border border-border bg-card text-primary hover:bg-secondary"
                        aria-label={
                            route.is_favorite
                                ? 'Quitar de favoritas'
                                : 'Agregar a favoritas'
                        }
                        aria-pressed={route.is_favorite}
                    >
                        <Star
                            className={cn(
                                'size-[18px]',
                                route.is_favorite && 'fill-current',
                            )}
                        />
                    </button>
                    <button
                        type="button"
                        onClick={share}
                        className="flex size-9 items-center justify-center rounded-md border border-border bg-card text-foreground hover:bg-secondary"
                        aria-label="Compartir"
                    >
                        <Share2 className="size-[18px]" />
                    </button>
                </div>
            </div>

            <section className="relative overflow-hidden rounded-2xl border border-border bg-card p-5 sm:p-6">
                <BusFront className="pointer-events-none absolute -right-6 -bottom-10 size-56 text-foreground/[0.03]" />
                <div className="relative grid gap-6 xl:grid-cols-[1fr_auto] xl:items-end">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="rounded bg-gold px-2 py-0.5 text-[11px] font-extrabold tracking-wider text-on-accent uppercase">
                                {route.campus.name}
                            </span>
                            <StatusChip
                                tone={lead ? 'live' : 'neutral'}
                                label={
                                    lead
                                        ? 'Servicio activo'
                                        : 'Sin unidad en ruta'
                                }
                                dot
                                className="rounded"
                            />
                            {route.frequency_minutes !== null && (
                                <span className="rounded bg-foreground/10 px-2 py-0.5 text-[11px] font-semibold text-muted-foreground">
                                    Cada {route.frequency_minutes} min
                                </span>
                            )}
                            <span className="rounded bg-foreground/10 px-2 py-0.5 text-[11px] font-semibold text-muted-foreground">
                                {route.service_starts_at} –{' '}
                                {route.service_ends_at}
                            </span>
                        </div>
                        <h1 className="mt-3 text-2xl font-bold tracking-tight sm:text-4xl">
                            Ruta {route.number}: {route.name}
                        </h1>
                        {route.description && (
                            <p className="mt-2 max-w-2xl text-sm text-muted-foreground sm:text-base">
                                {route.description}
                            </p>
                        )}
                    </div>
                    <dl className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        {metrics.map((metric) => (
                            <div
                                key={metric.label}
                                className="rounded-xl bg-foreground/5 p-3 xl:w-32"
                            >
                                <dt className="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase">
                                    {metric.label}
                                </dt>
                                <dd className="mt-1 text-2xl font-extrabold text-strong">
                                    {metric.value}
                                </dd>
                                <dd
                                    className={cn(
                                        'truncate text-[11px] text-muted-foreground',
                                        metric.hintClass,
                                    )}
                                >
                                    {metric.hint}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            <div className="grid gap-6 xl:grid-cols-12">
                <section className="min-w-0 space-y-4 xl:col-span-7">
                    <div className="rounded-2xl border border-border bg-card p-5 sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-bold">
                                    Línea de tiempo de paradas
                                </h2>
                                <p className="text-xs">
                                    <UpdatedAgo since={updatedAt} />
                                </p>
                            </div>
                            {stops.length > 0 && (
                                <span className="rounded-md bg-primary/15 px-3 py-1.5 text-xs font-semibold text-primary">
                                    Sentido: hacia{' '}
                                    {stops[stops.length - 1].name}
                                </span>
                            )}
                        </div>

                        {stops.length === 0 ? (
                            <p className="mt-6 rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                                Esta ruta todavía no tiene paradas.
                            </p>
                        ) : (
                            <ol className="relative mt-6 space-y-3">
                                <span
                                    className="absolute top-6 bottom-6 left-[17px] w-0.5 bg-border"
                                    aria-hidden="true"
                                />
                                <span
                                    className="absolute top-6 left-[17px] w-0.5 bg-route transition-[height]"
                                    style={{
                                        height: `${(passed / Math.max(1, stops.length - 1)) * 100}%`,
                                        maxHeight: 'calc(100% - 3rem)',
                                    }}
                                    aria-hidden="true"
                                />
                                {stops.map((stop, index) => (
                                    <TimelineItem
                                        key={stop.id}
                                        stop={stop}
                                        isLast={index === stops.length - 1}
                                        vehicleCode={lead?.vehicle.code ?? null}
                                    />
                                ))}
                            </ol>
                        )}
                    </div>

                    {incidents.map((incident) => (
                        <div
                            key={incident.id}
                            className="flex items-start gap-4 rounded-2xl border border-warning/40 bg-card p-5"
                        >
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/15 text-warning">
                                <TriangleAlert className="size-5" />
                            </span>
                            <div className="flex-1">
                                <p className="text-sm font-semibold text-strong">
                                    {incident.type_label}
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {incident.message}
                                </p>
                            </div>
                        </div>
                    ))}
                </section>

                <aside className="min-w-0 space-y-4 xl:col-span-5">
                    <div className="overflow-hidden rounded-2xl border border-border bg-card">
                        <TransitMap
                            center={
                                stops[0]
                                    ? [stops[0].latitude, stops[0].longitude]
                                    : [25.4232, -101.0005]
                            }
                            routes={mapRoutes}
                            selectedRouteId={route.id}
                            stopStates={stopStates}
                            vehicles={vehicles.map((vehicle) => ({
                                trip_id: vehicle.trip_id,
                                route_id: route.id,
                                code: vehicle.vehicle.code,
                                position: vehicle.position,
                            }))}
                            highlightedTripId={lead?.trip_id}
                            controlsClassName="bottom-3"
                            className="h-72"
                        />
                        <div className="flex items-center justify-between gap-3 px-4 py-3 text-xs">
                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                <Compass className="size-4 shrink-0" />
                                {lead?.position?.heading != null && nextStop
                                    ? `Rumbo ${headingLabel(lead.position.heading)} hacia ${nextStop.name}`
                                    : 'Sin unidad en ruta'}
                            </span>
                            <Link
                                href="/dashboard"
                                className="shrink-0 font-semibold text-primary hover:underline"
                            >
                                Ver en el mapa
                            </Link>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border bg-card p-5">
                        {lead ? (
                            <>
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-[11px] font-semibold tracking-wider text-gold uppercase">
                                            Unidad asignada
                                        </p>
                                        <h2 className="mt-1 text-xl font-bold">
                                            {lead.vehicle.code} ·{' '}
                                            {lead.vehicle.model}
                                        </h2>
                                    </div>
                                    <span className="shrink-0 rounded-md bg-foreground/10 px-2 py-1 text-xs font-semibold text-muted-foreground">
                                        {lead.vehicle.plate}
                                    </span>
                                </div>
                                {lead.driver && (
                                    <div className="mt-4 flex items-center gap-3 rounded-xl bg-background/50 p-3">
                                        <span className="flex size-11 items-center justify-center rounded-full bg-secondary text-sm font-bold text-foreground">
                                            {lead.driver.name
                                                .split(' ')
                                                .slice(0, 2)
                                                .map((part) => part[0])
                                                .join('')}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-semibold text-foreground">
                                                {lead.driver.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Conductor de la unidad
                                            </p>
                                        </div>
                                    </div>
                                )}
                                {lead.vehicle.amenities.length > 0 && (
                                    <ul className="mt-4 grid grid-cols-3 gap-2 text-center text-xs text-muted-foreground">
                                        {lead.vehicle.amenities.map((key) => {
                                            const { label, icon: Icon } =
                                                amenities[key];

                                            return (
                                                <li
                                                    key={key}
                                                    className="rounded-lg bg-background/50 p-3"
                                                >
                                                    <Icon className="mx-auto mb-1.5 size-5 text-foreground" />
                                                    {label}
                                                </li>
                                            );
                                        })}
                                    </ul>
                                )}
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Ahora no hay una unidad recorriendo esta ruta.
                                Revisa las próximas salidas.
                            </p>
                        )}
                        {stops.length > 0 && (
                            <ArrivalAlarmDialog
                                routeId={route.id}
                                stops={stops}
                                defaultStopId={nextStop?.id}
                                defaultMinutes={default_alarm_minutes}
                            >
                                <button
                                    type="button"
                                    className="mt-4 flex w-full items-center justify-center gap-2 rounded-md bg-primary py-3 text-sm font-bold text-primary-foreground shadow-lg shadow-black/30 hover:bg-primary-hover active:scale-[0.98]"
                                >
                                    <BellRing className="size-[18px]" />
                                    Avisarme al llegar
                                </button>
                            </ArrivalAlarmDialog>
                        )}
                        <div className="mt-2 grid grid-cols-2 gap-2">
                            <EmergencyDialog routeId={route.id}>
                                <button
                                    type="button"
                                    className="flex items-center justify-center gap-2 rounded-md border border-border bg-secondary py-2.5 text-xs font-semibold text-foreground hover:bg-secondary/70"
                                >
                                    <TriangleAlert className="size-4" />
                                    Reportar incidencia
                                </button>
                            </EmergencyDialog>
                            <Link
                                href="/schedules"
                                className="flex items-center justify-center gap-2 rounded-md border border-border bg-secondary py-2.5 text-xs font-semibold text-foreground hover:bg-secondary/70"
                            >
                                <Clock3 className="size-4" />
                                Ver horarios
                            </Link>
                        </div>
                    </div>

                    {alarms.length > 0 && (
                        <div className="rounded-2xl border border-border bg-card p-5">
                            <h2 className="text-lg font-bold">Mis alarmas</h2>
                            <ul className="mt-3 space-y-2">
                                {alarms.map((alarm) => (
                                    <li
                                        key={alarm.id}
                                        className="flex items-center gap-3 rounded-lg bg-background/50 px-3 py-2.5 text-sm"
                                    >
                                        <BellRing className="size-4 shrink-0 text-primary" />
                                        <span className="min-w-0 flex-1 truncate text-foreground">
                                            {stopName(alarm.stop_id)} ·{' '}
                                            {alarm.minutes_before} min antes
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.delete(
                                                    `/arrival-alarms/${alarm.id}`,
                                                    { preserveScroll: true },
                                                )
                                            }
                                            className="text-muted-foreground hover:text-destructive"
                                            aria-label={`Quitar alarma de ${stopName(alarm.stop_id)}`}
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div className="rounded-2xl border border-border bg-card p-5">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold">
                                Próximas salidas
                            </h2>
                            <span className="text-xs font-semibold text-gold">
                                Hoy
                            </span>
                        </div>
                        {upcoming_departures.length === 0 ? (
                            <p className="mt-3 text-sm text-muted-foreground">
                                No quedan salidas hoy.
                            </p>
                        ) : (
                            <div className="relative mt-3 overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="text-[11px] tracking-wider text-muted-foreground uppercase">
                                        <tr>
                                            <th className="py-2 font-semibold">
                                                Salida
                                            </th>
                                            <th className="py-2 font-semibold">
                                                Unidad
                                            </th>
                                            <th className="py-2 font-semibold">
                                                Conductor
                                            </th>
                                            <th className="py-2 text-right font-semibold">
                                                Estado
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {upcoming_departures.map(
                                            (departure) => (
                                                <tr key={departure.schedule_id}>
                                                    <td className="py-2.5 font-bold text-strong">
                                                        {departure.departs_at}
                                                    </td>
                                                    <td className="py-2.5 text-muted-foreground">
                                                        {departure.vehicle_code ??
                                                            '—'}
                                                    </td>
                                                    <td className="py-2.5 whitespace-nowrap text-muted-foreground">
                                                        {departure.driver_name ??
                                                            '—'}
                                                    </td>
                                                    <td className="py-2.5 text-right">
                                                        <StatusChip
                                                            tone={
                                                                tripStatus[
                                                                    departure
                                                                        .status
                                                                ].tone
                                                            }
                                                            label={
                                                                tripStatus[
                                                                    departure
                                                                        .status
                                                                ].label
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </aside>
            </div>
        </main>
    );
}
