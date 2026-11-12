import { Head, Link, usePage } from '@inertiajs/react';
import {
    BellPlus,
    BusFront,
    CalendarClock,
    ChevronRight,
    ClockAlert,
    LocateFixed,
    MapPin,
    MapPinOff,
    Share2,
    Star,
    TriangleAlert,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { ArrivalAlarmDialog } from '@/components/transit/arrival-alarm-dialog';
import {
    OccupancyBar,
    occupancyPercent,
    SATURATED_PERCENT,
} from '@/components/transit/occupancy-bar';
import { StatusChip } from '@/components/transit/status-chip';
import { TransitMap } from '@/components/transit/transit-map';
import { UpdatedAgo } from '@/components/transit/updated-ago';
import { useLivePositions } from '@/hooks/use-live-positions';
import { cn } from '@/lib/utils';
import { clockTime, distanceLabel, distanceMeters } from '@/lib/transit-format';
import type {
    Incident,
    LiveDashboardProps,
    LiveRoute,
    LiveVehicle,
} from '@/types/transit';

type Filter = 'all' | 'live' | 'near' | 'favorites';

/** Radio para "Cercanas": rutas con alguna parada a menos de esta distancia. */
const NEAR_METERS = 1500;

function routeStatus(route: LiveRoute) {
    const lead = route.vehicles[0];

    if (lead?.is_delayed)
        return { tone: 'warning', label: 'Demorado' } as const;
    if (lead) return { tone: 'live', label: 'En vivo' } as const;
    if (route.next_departure)
        return { tone: 'neutral', label: 'Programado' } as const;

    return { tone: 'neutral', label: 'Sin servicio' } as const;
}

function VehicleBox({ vehicle }: { vehicle: LiveVehicle }) {
    const percent = occupancyPercent(
        vehicle.occupancy,
        vehicle.vehicle.capacity,
    );

    return (
        <div className="mt-3 rounded-lg bg-background/60 p-3">
            <div className="flex items-center justify-between gap-2 text-xs">
                <span className="flex min-w-0 items-center gap-1.5 font-semibold text-foreground">
                    <BusFront className="size-3.5 shrink-0 text-muted-foreground" />
                    <span className="truncate">
                        {vehicle.vehicle.code} · {vehicle.vehicle.model}
                    </span>
                </span>
                <span
                    className={cn(
                        percent >= SATURATED_PERCENT
                            ? 'font-semibold text-destructive'
                            : 'text-muted-foreground',
                    )}
                >
                    {percent} %
                </span>
            </div>
            <OccupancyBar percent={percent} className="mt-2" />
            <div className="mt-2 flex items-center justify-between gap-2 text-[11px] text-muted-foreground">
                <span className="truncate">
                    Conductor: {vehicle.driver?.name ?? '—'}
                </span>
                {vehicle.next_stop && (
                    <span className="truncate">
                        Próx.: {vehicle.next_stop.name}
                    </span>
                )}
            </div>
        </div>
    );
}

function RouteCard({
    route,
    selected,
    incident,
    onSelect,
}: {
    route: LiveRoute;
    selected: boolean;
    incident: Incident | undefined;
    onSelect: () => void;
}) {
    const lead = route.vehicles[0];
    const status = routeStatus(route);

    return (
        <article
            className={cn(
                'rounded-xl border p-4 transition-colors',
                selected
                    ? 'border-primary/50 bg-card ring-1 ring-primary/30'
                    : lead
                      ? 'border-border bg-card'
                      : 'border-border bg-card/60',
            )}
        >
            <button
                type="button"
                onClick={onSelect}
                className="flex w-full items-start justify-between gap-3 text-left"
                aria-pressed={selected}
            >
                <span className="flex min-w-0 items-start gap-3">
                    <span
                        className="flex size-8 shrink-0 items-center justify-center rounded-md text-sm font-extrabold text-ink"
                        style={{ backgroundColor: route.color }}
                    >
                        {route.number}
                    </span>
                    <span className="min-w-0">
                        <h2 className="flex items-center gap-1.5 text-base leading-tight font-bold">
                            {route.name}
                            {route.is_favorite && (
                                <Star
                                    className="size-3.5 shrink-0 fill-gold text-gold"
                                    aria-label="Favorita"
                                />
                            )}
                        </h2>
                        {route.description && (
                            <span
                                className={cn(
                                    'mt-0.5 block text-xs',
                                    selected
                                        ? 'text-gold'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {route.description}
                            </span>
                        )}
                    </span>
                </span>
                <span className="shrink-0 text-right">
                    <StatusChip
                        tone={status.tone}
                        label={status.label}
                        dot={status.tone === 'live'}
                    />
                    {lead?.next_stop ? (
                        <span className="mt-1 block text-2xl font-extrabold text-strong">
                            {lead.next_stop.eta_minutes}{' '}
                            <span className="text-sm font-semibold text-muted-foreground">
                                min
                            </span>
                        </span>
                    ) : (
                        route.next_departure && (
                            <span className="mt-1 block text-lg font-extrabold text-strong">
                                {route.next_departure}
                            </span>
                        )
                    )}
                </span>
            </button>

            {lead ? (
                <>
                    <VehicleBox vehicle={lead} />
                    <div className="mt-3 flex items-center justify-between gap-2">
                        {lead.is_delayed ? (
                            <span className="flex min-w-0 items-center gap-1.5 text-xs text-warning">
                                <ClockAlert className="size-3.5 shrink-0" />
                                <span className="truncate">
                                    {incident?.message ??
                                        `Retraso de ${lead.delay_minutes} min`}
                                </span>
                            </span>
                        ) : (
                            lead.next_stop && (
                                <span className="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
                                    <MapPin className="size-3.5 shrink-0 text-gold" />
                                    <span className="truncate">
                                        {lead.next_stop.name} a{' '}
                                        {distanceLabel(
                                            lead.next_stop.distance_meters,
                                        )}
                                    </span>
                                </span>
                            )
                        )}
                        <Link
                            href={`/routes/${route.id}`}
                            className="flex shrink-0 items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-bold text-primary-foreground hover:bg-primary-hover"
                        >
                            Ver ruta
                            <ChevronRight className="size-3.5" />
                        </Link>
                    </div>
                </>
            ) : (
                <div className="mt-3 flex items-center justify-between gap-2">
                    <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <CalendarClock className="size-3.5 shrink-0" />
                        {route.next_departure
                            ? `Próxima salida hoy a las ${route.next_departure}`
                            : 'No quedan salidas hoy'}
                    </p>
                    <Link
                        href={`/routes/${route.id}`}
                        className="shrink-0 rounded-md bg-secondary px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-secondary/70"
                    >
                        Detalles
                    </Link>
                </div>
            )}
        </article>
    );
}

/** Llegada de la unidad de la ruta elegida: arriba en celular, sobre el mapa en escritorio. */
function ArrivalCard({
    route,
    vehicle,
    className,
}: {
    route: LiveRoute;
    vehicle: LiveVehicle;
    className?: string;
}) {
    const next = vehicle.next_stop;
    const { auth } = usePage().props;
    const share = async () => {
        const url = `${window.location.origin}/routes/${route.id}`;

        try {
            await navigator.clipboard.writeText(url);
            toast.success('Enlace copiado');
        } catch {
            toast.error('No se pudo copiar el enlace');
        }
    };

    return (
        <div className={className}>
            <div className="flex items-center gap-5">
                <div className="shrink-0">
                    <p className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                        Hora de arribo
                    </p>
                    <p className="text-3xl font-extrabold text-strong xl:text-4xl">
                        {next ? clockTime(next.arrives_at) : '—'}
                    </p>
                </div>
                <div className="h-12 w-px bg-border" />
                <div className="min-w-0">
                    <p className="flex items-center gap-2 font-bold text-strong xl:text-lg">
                        <span className="size-2 shrink-0 rounded-full bg-live" />
                        <span className="truncate">
                            Línea {route.number} – {route.name}
                        </span>
                    </p>
                    {next && (
                        <p className="mt-0.5 flex items-center gap-1.5 text-sm text-muted-foreground">
                            <MapPin className="size-4 shrink-0 text-gold" />
                            <span className="truncate">
                                Próxima parada: {next.name} · a{' '}
                                {distanceLabel(next.distance_meters)}
                            </span>
                        </p>
                    )}
                </div>
            </div>
            <div className="mt-4 flex flex-wrap items-center gap-2">
                <ArrivalAlarmDialog
                    routeId={route.id}
                    stops={route.stops}
                    defaultStopId={next?.id}
                    defaultMinutes={auth.user.alarm_minutes_before}
                >
                    <button
                        type="button"
                        className="flex items-center gap-2 rounded-md border border-border bg-secondary px-4 py-2 text-sm font-semibold text-foreground hover:bg-secondary/70"
                    >
                        <BellPlus className="size-4" />
                        Avisarme al llegar
                    </button>
                </ArrivalAlarmDialog>
                <button
                    type="button"
                    onClick={share}
                    className="flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-bold text-primary-foreground hover:bg-primary-hover"
                >
                    <Share2 className="size-4" />
                    Compartir
                </button>
            </div>
            {vehicle.progress !== null && (
                <div className="mt-4 flex items-center gap-3">
                    <div className="h-2 flex-1 overflow-hidden rounded-full bg-secondary">
                        <div
                            className="h-full rounded-full bg-gradient-to-r from-route to-gold"
                            style={{
                                width: `${Math.round(vehicle.progress * 100)}%`,
                            }}
                        />
                    </div>
                    <span className="text-xs font-bold text-foreground">
                        {Math.round(vehicle.progress * 100)} % del trayecto
                    </span>
                </div>
            )}
        </div>
    );
}

function LiveDashboard({
    campus,
    routes: serverRoutes,
    incidents,
}: LiveDashboardProps & {
    campus: NonNullable<LiveDashboardProps['campus']>;
}) {
    const { withLive, updatedAt } = useLivePositions(`campus.${campus.id}`, [
        'routes',
        'incidents',
    ]);
    const routes = serverRoutes.map((route) => ({
        ...route,
        vehicles: route.vehicles.map(withLive),
    }));
    const [selectedId, setSelectedId] = useState<number | null>(
        () =>
            (
                serverRoutes.find((route) => route.vehicles.length) ??
                serverRoutes[0]
            )?.id ?? null,
    );
    const [filter, setFilter] = useState<Filter>('all');
    const [search, setSearch] = useState('');
    const [userPosition, setUserPosition] = useState<[number, number] | null>(
        null,
    );
    const [locationDenied, setLocationDenied] = useState(false);

    const selected = routes.find((route) => route.id === selectedId) ?? null;
    const lead = selected?.vehicles[0];
    const incidentByRoute = new Map(
        incidents.map((incident) => [incident.route_id, incident]),
    );

    const nearRouteIds = useMemo(() => {
        if (!userPosition) return new Set<number>();

        return new Set(
            serverRoutes
                .filter((route) =>
                    route.stops.some(
                        (stop) =>
                            distanceMeters(userPosition, [
                                stop.latitude,
                                stop.longitude,
                            ]) <= NEAR_METERS,
                    ),
                )
                .map((route) => route.id),
        );
    }, [serverRoutes, userPosition]);

    const term = search.trim().toLowerCase();
    const visible = routes.filter((route) => {
        const matchesFilter =
            filter === 'all' ||
            (filter === 'live' && route.vehicles.length > 0) ||
            (filter === 'favorites' && route.is_favorite) ||
            (filter === 'near' && nearRouteIds.has(route.id));
        const matchesSearch =
            term === '' ||
            [
                String(route.number),
                route.name,
                route.description ?? '',
                ...route.stops.map((stop) => stop.name),
            ].some((text) => text.toLowerCase().includes(term));

        return matchesFilter && matchesSearch;
    });

    const chooseNear = () => {
        setFilter('near');

        if (userPosition || !navigator.geolocation) return;

        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                setLocationDenied(false);
                setUserPosition([coords.latitude, coords.longitude]);
            },
            () => setLocationDenied(true),
        );
    };

    const filters: {
        id: Filter;
        label: string;
        count: number | null;
        icon?: typeof Star;
        dot?: boolean;
    }[] = [
        { id: 'all', label: 'Todas', count: routes.length },
        {
            id: 'live',
            label: 'En camino',
            count: routes.filter((route) => route.vehicles.length).length,
            dot: true,
        },
        {
            id: 'near',
            label: 'Cercanas',
            count: userPosition ? nearRouteIds.size : null,
            icon: LocateFixed,
        },
        {
            id: 'favorites',
            label: 'Favoritas',
            count: routes.filter((route) => route.is_favorite).length,
            icon: Star,
        },
    ];

    const mapVehicles = routes.flatMap((route) =>
        route.vehicles.map((vehicle) => ({
            trip_id: vehicle.trip_id,
            route_id: route.id,
            code: vehicle.vehicle.code,
            position: vehicle.position,
        })),
    );
    const mapRoutes = useMemo(
        () =>
            serverRoutes.map(({ id, color, encoded_polyline, stops }) => ({
                id,
                color,
                encoded_polyline,
                stops,
            })),
        [serverRoutes],
    );
    const stopStates = lead?.next_stop
        ? { [lead.next_stop.id]: 'next' as const }
        : {};

    return (
        <main className="flex flex-1 flex-col lg:h-[calc(100vh-4rem)] lg:flex-row">
            <section
                className="relative z-10 order-2 -mt-6 flex flex-col rounded-t-2xl border-t border-border bg-background lg:order-1 lg:mt-0 lg:w-[400px] lg:shrink-0 lg:rounded-none lg:border-t-0 lg:border-r"
                aria-label="Rutas del campus"
            >
                <div className="mx-auto mt-2.5 h-1.5 w-12 rounded-full bg-secondary lg:hidden" />

                {selected && lead && (
                    <ArrivalCard
                        route={selected}
                        vehicle={lead}
                        className="mx-4 mt-4 rounded-xl border border-border bg-card p-4 xl:hidden"
                    />
                )}

                <div className="space-y-3 p-4">
                    <label className="relative block">
                        <span className="sr-only">Buscar ruta</span>
                        <BusFront className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-placeholder" />
                        <input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Buscar ruta, parada o colonia…"
                            className="w-full rounded-md border border-border bg-card py-2.5 pr-3 pl-9 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                        />
                    </label>
                    <div
                        className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
                        role="group"
                        aria-label="Filtrar rutas"
                    >
                        {filters.map(
                            ({ id, label, count, icon: Icon, dot }) => (
                                <button
                                    key={id}
                                    type="button"
                                    onClick={() =>
                                        id === 'near'
                                            ? chooseNear()
                                            : setFilter(id)
                                    }
                                    aria-pressed={filter === id}
                                    className={cn(
                                        'flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs',
                                        filter === id
                                            ? 'bg-primary font-bold text-primary-foreground'
                                            : 'bg-card font-semibold text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {dot && (
                                        <span className="size-1.5 rounded-full bg-live" />
                                    )}
                                    {Icon && <Icon className="size-3.5" />}
                                    {label}
                                    {count !== null && ` (${count})`}
                                </button>
                            ),
                        )}
                    </div>
                </div>

                <div className="flex-1 space-y-3 px-4 pb-24 md:pb-6 lg:overflow-y-auto">
                    {filter === 'near' && locationDenied && (
                        <p className="flex items-center gap-2 rounded-xl border border-border bg-card p-4 text-sm text-muted-foreground">
                            <MapPinOff className="size-4 shrink-0" />
                            Activa la ubicación del navegador para ver las rutas
                            cercanas.
                        </p>
                    )}
                    {visible.map((route) => (
                        <RouteCard
                            key={route.id}
                            route={route}
                            selected={route.id === selectedId}
                            incident={incidentByRoute.get(route.id)}
                            onSelect={() => setSelectedId(route.id)}
                        />
                    ))}
                    {visible.length === 0 && !locationDenied && (
                        <p className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                            {routes.length === 0
                                ? 'Este campus todavía no tiene rutas activas.'
                                : 'Ninguna ruta coincide con la búsqueda.'}
                        </p>
                    )}
                </div>
            </section>

            <TransitMap
                center={[campus.latitude, campus.longitude]}
                routes={mapRoutes}
                selectedRouteId={selectedId}
                stopStates={stopStates}
                vehicles={mapVehicles}
                highlightedTripId={lead?.trip_id}
                onSelectRoute={setSelectedId}
                controlsClassName="bottom-10 xl:bottom-52"
                className="order-1 h-[55vh] bg-card lg:order-2 lg:h-auto lg:flex-1"
            >
                {selected && (
                    <div className="absolute top-3 left-3 z-[1000] hidden max-w-[calc(100%-12rem)] flex-col items-start gap-2 sm:flex">
                        <div className="flex items-center gap-2 rounded-full border border-border bg-card/95 px-3 py-1.5 text-xs shadow-lg backdrop-blur">
                            <span
                                className={cn(
                                    'size-2 shrink-0 rounded-full',
                                    lead
                                        ? 'bg-live motion-safe:animate-pulse'
                                        : 'bg-muted-foreground',
                                )}
                            />
                            <span className="truncate font-semibold text-foreground">
                                Ruta {selected.number} · {selected.name}
                            </span>
                            <UpdatedAgo since={updatedAt} />
                        </div>
                        {incidentByRoute.get(selected.id) && (
                            <div className="flex items-center gap-2 rounded-full border border-warning/40 bg-card/95 px-3 py-1.5 text-xs text-warning shadow-lg backdrop-blur">
                                <TriangleAlert className="size-3.5 shrink-0" />
                                <span className="truncate">
                                    {incidentByRoute.get(selected.id)?.message}
                                </span>
                            </div>
                        )}
                    </div>
                )}
                {selected && lead && (
                    <ArrivalCard
                        route={selected}
                        vehicle={lead}
                        className="absolute right-20 bottom-6 left-6 z-[1000] hidden max-w-xl rounded-2xl border border-border bg-card/95 p-5 shadow-2xl shadow-black/50 backdrop-blur xl:block"
                    />
                )}
            </TransitMap>
        </main>
    );
}

export default function Dashboard(props: LiveDashboardProps) {
    return (
        <>
            <Head title="Dashboard en Vivo" />
            {props.campus ? (
                <LiveDashboard {...props} campus={props.campus} />
            ) : (
                <div className="flex flex-1 items-center justify-center p-8 text-center text-sm text-muted-foreground">
                    Todavía no hay campus registrados.
                </div>
            )}
        </>
    );
}
