import { Head, Link, router, usePoll } from '@inertiajs/react';
import { useState } from 'react';
import {
    Activity,
    AlertTriangle,
    ArrowDownToLine,
    ArrowRight,
    BarChart3,
    BusFront,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Filter,
    Fuel,
    Plus,
    Search,
    ShieldCheck,
    UsersRound,
    Wrench,
} from 'lucide-react';
import type { Paginated } from '@/types/pagination';
import { VehicleFormDialog } from '@/components/admin/vehicle-form-dialog';
import type {
    Incident,
    Trip,
    Vehicle,
    VehicleAmenity,
    VehicleStatus,
} from '@/types/transit';

type FleetTrip = Trip & {
    route: { name: string; number: number } | null;
    driver: { name: string } | null;
    vehicle?: Vehicle | null;
};
type FleetVehicle = Vehicle & { trips: FleetTrip[] };
type Props = {
    vehicles: Paginated<FleetVehicle>;
    filters: { status: VehicleStatus | null; search: string | null };
    statuses: { value: VehicleStatus; label: string }[];
    amenities: { value: VehicleAmenity; label: string }[];
    alert: Incident | null;
    stats: {
        trips_today: number;
        started_trips: number;
        punctuality_percent: number | null;
        average_occupancy_percent: number | null;
        total: number;
        in_service: number;
        standby: number;
        maintenance: number;
    };
    operations: {
        departures: FleetTrip[];
        hourly: { hour: string; occupancy: number }[];
    };
};
const statusStyles: Record<VehicleStatus, string> = {
    in_service: 'bg-live/10 text-live',
    standby: 'bg-primary/10 text-primary',
    maintenance: 'bg-destructive/10 text-destructive',
};
const tabs = [
    { id: 'fleet', label: 'Gestión de Unidades y Rutas', icon: BusFront },
    { id: 'drivers', label: 'Conductores', icon: UsersRound },
    { id: 'maintenance', label: 'Alertas y Mantenimiento', icon: Wrench },
    { id: 'reports', label: 'Reportes de Aforo', icon: BarChart3 },
];
const panel =
    'overflow-hidden rounded-xl border border-border/70 bg-card shadow-sm';

export default function Fleet({
    vehicles,
    filters,
    statuses,
    amenities,
    stats,
    operations,
    alert,
}: Props) {
    usePoll(15_000, { only: ['stats', 'alert', 'operations', 'vehicles'] });
    const [tab, setTab] = useState('fleet');
    const [search, setSearch] = useState(filters.search ?? '');
    const servicePercent = stats.total
        ? Math.round((stats.in_service / stats.total) * 100)
        : 0;
    const maximum = Math.max(
        1,
        ...operations.hourly.map((item) => item.occupancy),
    );
    const applyFilters = (status: string, term = search) =>
        router.get(
            '/admin/fleet',
            {
                ...(status ? { status } : {}),
                ...(term.trim() ? { search: term.trim() } : {}),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    const chooseTab = (id: string) => {
        if (id === 'maintenance') {
            router.visit('/admin/fleet/maintenance');
            return;
        }
        if (id === 'drivers') {
            router.visit('/admin/fleet/drivers');
            return;
        }
        setTab(id);
        if (id === 'maintenance') applyFilters('maintenance');
        else if (tab === 'maintenance') applyFilters('');
    };
    const download = () => {
        const escape = (value: string | number) =>
            `"${String(value).replaceAll('"', '""')}"`;
        const rows = [
            ['Código', 'Placas', 'Modelo', 'Capacidad', 'Estado'],
            ...vehicles.data.map((vehicle) => [
                vehicle.code,
                vehicle.plate,
                vehicle.model,
                vehicle.capacity,
                statuses.find((state) => state.value === vehicle.status)
                    ?.label ?? vehicle.status,
            ]),
        ];
        const url = URL.createObjectURL(
            new Blob(
                [
                    '\uFEFF' +
                        rows
                            .map((row) => row.map(escape).join(','))
                            .join('\r\n'),
                ],
                {
                    type: 'text/csv;charset=utf-8',
                },
            ),
        );
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'uniroute-unidades-pagina.csv';
        anchor.click();
        URL.revokeObjectURL(url);
    };
    const cards = [
        {
            label: 'Total unidades',
            value: `${stats.in_service}/${stats.total}`,
            unit: 'unidades',
            detail: `${servicePercent}% en servicio`,
            foot: `${stats.standby} en reserva`,
            percent: servicePercent,
            icon: BusFront,
            color: 'bg-live',
        },
        {
            label: 'Viajes de hoy',
            value: stats.trips_today,
            unit: '',
            detail: 'Programados, en curso y completados',
            foot: 'Sin cancelados',
            percent: stats.trips_today
                ? (stats.started_trips / stats.trips_today) * 100
                : 0,
            icon: Activity,
            color: 'bg-primary',
        },
        {
            label: 'Puntualidad global',
            value:
                stats.punctuality_percent === null
                    ? '—'
                    : stats.punctuality_percent + '%',
            unit: '',
            detail: 'Salida dentro de ±2 minutos',
            foot: stats.started_trips
                ? stats.started_trips + ' viajes iniciados'
                : 'Sin salidas registradas',
            percent: stats.punctuality_percent ?? 0,
            icon: Clock3,
            color: 'bg-live',
        },
        {
            label: 'Ocupación promedio',
            value:
                stats.average_occupancy_percent === null
                    ? '—'
                    : stats.average_occupancy_percent + '%',
            unit: '',
            detail: 'Ocupación registrada / capacidad',
            foot: 'Viajes iniciados hoy',
            percent: stats.average_occupancy_percent ?? 0,
            icon: UsersRound,
            color: 'bg-primary-hover',
        },
    ];
    const histogram = (
        <section className={`${panel} p-5 xl:p-6`}>
            <div className="flex items-center justify-between gap-3">
                <h2 className="max-w-44 text-lg leading-snug font-bold">
                    Picos de Aforo por Horario
                </h2>
                <BarChart3 className="size-7 shrink-0 rounded-lg bg-primary/10 p-1 text-primary" />
            </div>
            <p className="mt-1 text-xs text-muted-foreground">
                Ocupación registrada en los viajes de hoy
            </p>
            {operations.hourly.length ? (
                <div
                    role="img"
                    aria-label="Ocupación por hora de salida"
                    className="mt-8 flex h-44 items-end gap-2 border-b border-border pb-6"
                >
                    {[...operations.hourly]
                        .sort((a, b) => a.hour.localeCompare(b.hour))
                        .map((item, index) => (
                            <div
                                key={item.hour}
                                className="relative flex h-full min-w-0 flex-1 items-end"
                            >
                                <div
                                    title={`${item.hour}: ${item.occupancy} pasajeros`}
                                    style={{
                                        height: `${Math.max(3, (item.occupancy / maximum) * 100)}%`,
                                    }}
                                    className={`relative w-full rounded-t-sm ${index % 2 ? 'bg-primary' : 'bg-secondary'}`}
                                >
                                    <span className="absolute -top-5 w-full text-center text-[10px] text-muted-foreground">
                                        {item.occupancy}
                                    </span>
                                </div>
                                <span className="absolute -bottom-5 w-full text-center text-[9px] text-muted-foreground">
                                    {item.hour}
                                </span>
                            </div>
                        ))}
                </div>
            ) : (
                <div className="mt-6 flex h-44 flex-col items-center justify-center rounded-lg border border-dashed border-border px-4 text-center">
                    <BarChart3 className="mb-3 size-7 text-placeholder" />
                    <p className="text-sm text-muted-foreground">
                        Sin registros de aforo hoy
                    </p>
                    <p className="mt-1 text-xs text-placeholder">
                        Los viajes con ocupación aparecerán aquí.
                    </p>
                </div>
            )}
            <p className="mt-5 flex items-center gap-2 text-[11px] text-muted-foreground">
                <span className="size-2 rounded-sm bg-primary" />
                Pasajeros registrados · No representa abordajes únicos
            </p>
        </section>
    );
    const departures = (
        <section className={`${panel} p-5 xl:p-6`}>
            <Link
                href="/admin/fleet/assignments"
                className="mb-3 inline-flex text-xs text-primary hover:underline"
            >
                Gestionar asignaciones y relevos →
            </Link>
            <h2 className="text-lg leading-snug font-bold">
                Despacho &amp;
                <br />
                Asignación Rápida
            </h2>
            <p className="mt-1 text-xs text-muted-foreground">
                Próximas salidas programadas
            </p>
            <div className="mt-5 space-y-3">
                {operations.departures.map((trip) => (
                    <div
                        key={trip.id}
                        className="flex items-center gap-3 rounded-lg bg-background/70 p-3"
                    >
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary text-xs font-semibold text-primary">
                            {trip.driver?.name
                                .split(' ')
                                .slice(0, 2)
                                .map((name) => name[0])
                                .join('') ?? '—'}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-xs font-semibold">
                                {trip.driver?.name ?? 'Sin conductor'}
                            </p>
                            <p className="mt-1 text-[11px] text-muted-foreground">
                                {trip.route?.name ?? 'Sin ruta'} ·{' '}
                                {trip.vehicle?.code ?? 'Sin unidad'}
                            </p>
                        </div>
                        <span className="text-[11px] text-primary">
                            {new Date(trip.scheduled_at).toLocaleTimeString(
                                'es-MX',
                                {
                                    timeZone: 'America/Mexico_City',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                },
                            )}
                        </span>
                    </div>
                ))}
                {!operations.departures.length && (
                    <p className="rounded-lg bg-background/70 p-4 text-sm text-muted-foreground">
                        No hay próximas salidas programadas.
                    </p>
                )}
            </div>
        </section>
    );
    return (
        <>
            <Head title="Administración de flota" />
            <main className="min-h-full bg-background px-4 py-7 text-foreground sm:px-7 lg:px-8">
                <div className="mx-auto max-w-[1500px] space-y-6">
                    <header className="flex flex-col justify-between gap-5 xl:flex-row xl:items-center">
                        <div>
                            <p className="mb-2 text-[10px] font-medium tracking-[.15em] text-primary uppercase">
                                UTC Campus Mobility · Operaciones
                            </p>
                            <h1 className="text-2xl leading-tight font-bold tracking-tight 2xl:text-3xl">
                                Consola de Administración de Flota
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                                Supervisión de unidades, capacidad de transporte
                                y gestión operativa de conductores.
                            </p>
                        </div>
                        <div className="flex shrink-0 flex-wrap gap-2 xl:flex-col">
                            <VehicleFormDialog
                                statuses={statuses}
                                amenities={amenities}
                            >
                                <button
                                    type="button"
                                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-xs font-semibold text-primary-foreground hover:bg-primary-hover"
                                >
                                    <Plus className="size-4" />
                                    Registrar Unidad
                                </button>
                            </VehicleFormDialog>
                            <button
                                type="button"
                                onClick={() => chooseTab('drivers')}
                                className="inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-xs font-medium hover:bg-secondary"
                            >
                                <UsersRound className="size-4 text-primary" />
                                Consultar despachos
                            </button>
                        </div>
                    </header>
                    <section
                        aria-label="Indicadores de flota"
                        className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        {cards.map(
                            ({
                                label,
                                value,
                                unit,
                                detail,
                                foot,
                                percent,
                                icon: Icon,
                                color,
                            }) => (
                                <article
                                    key={label}
                                    className={`${panel} flex min-h-44 flex-col p-5 xl:p-6`}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <h2 className="max-w-36 text-[11px] font-medium tracking-wider uppercase">
                                            {label}
                                        </h2>
                                        <span className="rounded-lg bg-secondary p-2 text-primary">
                                            <Icon className="size-4" />
                                        </span>
                                    </div>
                                    <p className="mt-5 text-4xl font-bold tracking-tight">
                                        {value}
                                        <span className="ml-1 text-xs font-normal tracking-normal text-muted-foreground">
                                            {unit}
                                        </span>
                                    </p>
                                    <div className="mt-auto pt-3">
                                        <div className="mb-2 flex justify-between gap-2 text-[10px] text-muted-foreground">
                                            <span>{detail}</span>
                                            <span>{foot}</span>
                                        </div>
                                        <div className="h-1.5 overflow-hidden rounded-full bg-secondary">
                                            <div
                                                className={`h-full rounded-full ${color}`}
                                                style={{ width: `${percent}%` }}
                                            />
                                        </div>
                                    </div>
                                </article>
                            ),
                        )}
                    </section>
                    <section
                        aria-label="Estado operativo"
                        className={
                            panel +
                            ' flex flex-wrap items-center justify-between gap-4 p-4'
                        }
                    >
                        <div className="flex items-center gap-4">
                            <span
                                className={
                                    alert
                                        ? 'rounded-lg bg-destructive/10 p-3 text-destructive'
                                        : 'rounded-lg bg-live/10 p-3 text-live'
                                }
                            >
                                {alert ? (
                                    <AlertTriangle className="size-5" />
                                ) : (
                                    <ShieldCheck className="size-5" />
                                )}
                            </span>
                            <div>
                                <h2 className="text-sm font-semibold">
                                    {alert
                                        ? alert.type_label +
                                          ' · ' +
                                          alert.severity_label +
                                          (alert.route
                                              ? ' · Ruta ' + alert.route.number
                                              : '')
                                        : 'Sin incidencias operativas abiertas'}
                                </h2>
                                <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                    {alert?.message ??
                                        'La flota no tiene incidencias abiertas registradas. ' +
                                            stats.maintenance +
                                            ' unidades en mantenimiento.'}
                                </p>
                            </div>
                        </div>
                        <Link
                            href="/admin/incidents"
                            className="rounded-lg bg-secondary px-4 py-2.5 text-xs hover:text-primary"
                        >
                            Ver incidencias
                            <ArrowRight className="ml-2 inline size-3" />
                        </Link>
                    </section>
                    <section
                        className={`${panel} p-1.5`}
                        aria-label="Secciones de administración"
                    >
                        <div
                            role="tablist"
                            aria-label="Paneles de flota"
                            className="flex flex-wrap gap-1"
                        >
                            {tabs.map(({ id, label, icon: Icon }) => (
                                <button
                                    key={id}
                                    type="button"
                                    role="tab"
                                    id={`tab-${id}`}
                                    aria-selected={tab === id}
                                    aria-controls="fleet-panel"
                                    onClick={() => chooseTab(id)}
                                    className={`flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs whitespace-nowrap transition-colors ${tab === id ? 'bg-primary font-semibold text-primary-foreground' : 'hover:bg-secondary'}`}
                                >
                                    <Icon className="size-4" />
                                    {label}
                                    {id === 'maintenance' &&
                                        stats.maintenance > 0 && (
                                            <span className="size-1.5 rounded-full bg-destructive" />
                                        )}
                                </button>
                            ))}
                        </div>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <label className="flex items-center gap-2 rounded-lg bg-secondary px-3 py-2 text-xs">
                                <Filter className="size-3.5 text-primary" />
                                <span className="sr-only">
                                    Filtrar por estado
                                </span>
                                <select
                                    value={filters.status ?? ''}
                                    onChange={(event) =>
                                        applyFilters(event.target.value)
                                    }
                                    className="max-w-44 bg-transparent outline-offset-4"
                                >
                                    <option value="">Todos los estados</option>
                                    {statuses.map((state) => (
                                        <option
                                            key={state.value}
                                            value={state.value}
                                        >
                                            {state.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <button
                                type="button"
                                onClick={download}
                                title="Exportar unidades de esta página"
                                aria-label="Exportar unidades de esta página en CSV"
                                className="rounded-lg bg-secondary p-2.5 hover:text-primary"
                            >
                                <ArrowDownToLine className="size-3.5" />
                            </button>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    applyFilters(filters.status ?? '');
                                }}
                                className="ml-auto flex items-center gap-2 rounded-lg border border-border px-3"
                            >
                                <Search className="size-3.5 text-placeholder" />
                                <input
                                    aria-label="Buscar unidad por código, placas o modelo"
                                    placeholder="Buscar unidad…"
                                    value={search}
                                    maxLength={100}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    className="h-8 w-36 bg-transparent text-xs outline-none placeholder:text-placeholder sm:w-48"
                                />
                                <button
                                    type="submit"
                                    className="text-xs text-primary"
                                >
                                    Buscar
                                </button>
                            </form>
                        </div>
                    </section>
                    <div
                        id="fleet-panel"
                        role="tabpanel"
                        aria-labelledby={`tab-${tab}`}
                        className="grid items-start gap-5 xl:grid-cols-[minmax(0,2.1fr)_minmax(260px,1fr)]"
                    >
                        <div className="min-w-0 space-y-4">
                            {(tab === 'fleet' || tab === 'maintenance') && (
                                <section className={panel}>
                                    <header className="flex flex-wrap items-center justify-between gap-3 bg-secondary/40 px-5 py-6">
                                        <div>
                                            <h2 className="text-lg font-bold">
                                                {tab === 'maintenance'
                                                    ? 'Unidades en Mantenimiento'
                                                    : 'Unidades de la Flota'}
                                            </h2>
                                            <p className="mt-1 text-[11px] tracking-wide text-muted-foreground">
                                                {tab === 'maintenance'
                                                    ? 'Control de disponibilidad técnica'
                                                    : 'Ruta y conductor del viaje actualmente en servicio'}
                                            </p>
                                        </div>
                                        <span className="flex items-center gap-1.5 rounded bg-background px-2 py-1 text-[10px] text-muted-foreground">
                                            <span className="size-1.5 rounded-full bg-live" />
                                            Datos registrados
                                        </span>
                                    </header>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-xs">
                                            <thead className="text-[10px] tracking-wide text-muted-foreground uppercase">
                                                <tr>
                                                    {[
                                                        'Unidad ID',
                                                        'Ruta asignada',
                                                        'Conductor',
                                                        'Estado',
                                                        'Ocupación',
                                                    ].map((heading) => (
                                                        <th
                                                            key={heading}
                                                            scope="col"
                                                            className="px-4 py-4 font-medium"
                                                        >
                                                            {heading}
                                                        </th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-border/60">
                                                {vehicles.data.map(
                                                    (vehicle) => {
                                                        const trip =
                                                            vehicle.trips[0];
                                                        const occupancy =
                                                            trip &&
                                                            vehicle.capacity
                                                                ? Math.min(
                                                                      100,
                                                                      Math.round(
                                                                          (trip.occupancy /
                                                                              vehicle.capacity) *
                                                                              100,
                                                                      ),
                                                                  )
                                                                : null;
                                                        return (
                                                            <tr
                                                                key={vehicle.id}
                                                                className="hover:bg-secondary/20"
                                                            >
                                                                <th
                                                                    scope="row"
                                                                    className="px-4 py-6"
                                                                >
                                                                    <div className="flex items-start gap-2">
                                                                        <span className="rounded bg-background p-1.5 text-primary">
                                                                            <BusFront className="size-3.5" />
                                                                        </span>
                                                                        <div>
                                                                            <p className="max-w-24 font-semibold wrap-break-word">
                                                                                {
                                                                                    vehicle.code
                                                                                }
                                                                            </p>
                                                                            <p className="mt-1 max-w-24 text-[10px] font-normal text-muted-foreground">
                                                                                {
                                                                                    vehicle.model
                                                                                }
                                                                            </p>
                                                                            <p className="mt-1 text-[10px] font-normal text-placeholder">
                                                                                {
                                                                                    vehicle.plate
                                                                                }
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </th>
                                                                <td className="px-4 py-6">
                                                                    <p className="font-medium">
                                                                        {trip
                                                                            ?.route
                                                                            ?.name ??
                                                                            'Sin asignar'}
                                                                    </p>
                                                                    <p className="mt-1 text-[10px] text-muted-foreground">
                                                                        {trip?.route
                                                                            ? `Ruta ${trip.route.number}`
                                                                            : vehicle.status ===
                                                                                'maintenance'
                                                                              ? 'Fuera de servicio'
                                                                              : 'Sin viaje activo'}
                                                                    </p>
                                                                </td>
                                                                <td className="px-4 py-6">
                                                                    <div className="flex items-center gap-2">
                                                                        <span className="hidden size-6 shrink-0 items-center justify-center rounded-full bg-secondary text-[9px] 2xl:flex">
                                                                            {trip?.driver?.name
                                                                                .split(
                                                                                    ' ',
                                                                                )
                                                                                .slice(
                                                                                    0,
                                                                                    2,
                                                                                )
                                                                                .map(
                                                                                    (
                                                                                        name,
                                                                                    ) =>
                                                                                        name[0],
                                                                                )
                                                                                .join(
                                                                                    '',
                                                                                ) ??
                                                                                '—'}
                                                                        </span>
                                                                        <span className="text-[11px]">
                                                                            {trip
                                                                                ?.driver
                                                                                ?.name ??
                                                                                'No asignado'}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td className="px-4 py-6">
                                                                    <span
                                                                        className={`inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[10px] whitespace-nowrap ${statusStyles[vehicle.status]}`}
                                                                    >
                                                                        <span className="size-1 rounded-full bg-current" />
                                                                        {
                                                                            statuses.find(
                                                                                (
                                                                                    state,
                                                                                ) =>
                                                                                    state.value ===
                                                                                    vehicle.status,
                                                                            )
                                                                                ?.label
                                                                        }
                                                                    </span>
                                                                </td>
                                                                <td className="min-w-24 px-4 py-6">
                                                                    {occupancy !==
                                                                    null ? (
                                                                        <>
                                                                            <div className="mb-1 flex justify-between gap-2 text-[10px]">
                                                                                <span>
                                                                                    {
                                                                                        occupancy
                                                                                    }
                                                                                    %
                                                                                </span>
                                                                                <span className="text-muted-foreground">
                                                                                    {
                                                                                        trip.occupancy
                                                                                    }
                                                                                    /
                                                                                    {
                                                                                        vehicle.capacity
                                                                                    }
                                                                                </span>
                                                                            </div>
                                                                            <div className="h-1.5 rounded-full bg-secondary">
                                                                                <div
                                                                                    style={{
                                                                                        width: `${occupancy}%`,
                                                                                    }}
                                                                                    className={`h-full rounded-full ${occupancy >= 90 ? 'bg-destructive' : occupancy >= 70 ? 'bg-primary' : 'bg-live'}`}
                                                                                />
                                                                            </div>
                                                                        </>
                                                                    ) : (
                                                                        <p className="text-[10px] text-muted-foreground">
                                                                            {vehicle.status ===
                                                                            'maintenance'
                                                                                ? 'Fuera de servicio'
                                                                                : `${vehicle.capacity} plazas`}
                                                                            <span className="mt-1 block text-placeholder">
                                                                                Sin
                                                                                ocupación
                                                                                activa
                                                                            </span>
                                                                        </p>
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        );
                                                    },
                                                )}
                                                {!vehicles.data.length && (
                                                    <tr>
                                                        <td
                                                            colSpan={5}
                                                            className="px-5 py-16 text-center text-muted-foreground"
                                                        >
                                                            No hay unidades que
                                                            coincidan con los
                                                            filtros.
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                    <footer className="flex flex-wrap items-center justify-between gap-3 px-4 py-5">
                                        <p className="text-[10px] text-muted-foreground">
                                            Mostrando {vehicles.from ?? 0}–
                                            {vehicles.to ?? 0} de{' '}
                                            {vehicles.total} unidades
                                        </p>
                                        <nav
                                            aria-label="Paginación de unidades"
                                            className="flex items-center gap-1"
                                        >
                                            {vehicles.prev_page_url ? (
                                                <Link
                                                    href={
                                                        vehicles.prev_page_url
                                                    }
                                                    preserveScroll
                                                    className="rounded-lg bg-secondary p-2"
                                                    aria-label="Página anterior"
                                                >
                                                    <ChevronLeft className="size-3" />
                                                </Link>
                                            ) : (
                                                <span className="p-2 text-placeholder">
                                                    <ChevronLeft className="size-3" />
                                                </span>
                                            )}
                                            <span className="rounded-lg bg-primary px-3 py-2 text-xs text-primary-foreground">
                                                {vehicles.current_page}
                                            </span>
                                            <span className="px-2 text-[10px] text-muted-foreground">
                                                de {vehicles.last_page}
                                            </span>
                                            {vehicles.next_page_url ? (
                                                <Link
                                                    href={
                                                        vehicles.next_page_url
                                                    }
                                                    preserveScroll
                                                    className="rounded-lg bg-secondary p-2"
                                                    aria-label="Página siguiente"
                                                >
                                                    <ChevronRight className="size-3" />
                                                </Link>
                                            ) : (
                                                <span className="p-2 text-placeholder">
                                                    <ChevronRight className="size-3" />
                                                </span>
                                            )}
                                        </nav>
                                    </footer>
                                </section>
                            )}
                            {tab === 'drivers' && departures}
                            {tab === 'reports' && histogram}
                            <section
                                className="grid gap-3 sm:grid-cols-3"
                                aria-label="Indicadores complementarios"
                            >
                                {[
                                    {
                                        title: 'Eficiencia energética',
                                        value: 'Sin mediciones',
                                        detail: 'Consumo no registrado',
                                        icon: Fuel,
                                    },
                                    {
                                        title: 'Velocidad promedio',
                                        value: 'Sin telemetría',
                                        detail: 'Pendiente de datos GPS',
                                        icon: Activity,
                                    },
                                    {
                                        title: 'Capacidad de servicio',
                                        value: `${stats.in_service} unidades`,
                                        detail: 'Flota en servicio',
                                        icon: BusFront,
                                    },
                                ].map(
                                    ({ title, value, detail, icon: Icon }) => (
                                        <article
                                            key={title}
                                            className={`${panel} flex items-center gap-3 p-4`}
                                        >
                                            <span className="rounded-lg bg-secondary p-2 text-primary">
                                                <Icon className="size-5" />
                                            </span>
                                            <div>
                                                <h2 className="text-[9px] leading-relaxed tracking-wide text-muted-foreground uppercase">
                                                    {title}
                                                </h2>
                                                <p className="mt-1 text-sm font-semibold">
                                                    {value}
                                                </p>
                                                <p className="mt-1 text-[10px] text-placeholder">
                                                    {detail}
                                                </p>
                                            </div>
                                        </article>
                                    ),
                                )}
                            </section>
                        </div>
                        <aside
                            className="space-y-4"
                            aria-label="Resumen de operación"
                        >
                            {tab !== 'reports' && histogram}
                            {tab !== 'drivers' && departures}
                            <section className="relative overflow-hidden rounded-xl border border-border bg-[#020E24] p-6">
                                <p className="text-[10px] font-semibold tracking-wider text-primary uppercase">
                                    Condición de tráfico · Saltillo
                                </p>
                                <div className="mt-4 flex items-center justify-between gap-4">
                                    <div>
                                        <h2 className="text-lg font-bold">
                                            Sin información de tráfico
                                        </h2>
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            No hay un proveedor conectado.
                                        </p>
                                    </div>
                                    <Clock3 className="size-8 shrink-0 text-primary" />
                                </div>
                            </section>
                        </aside>
                    </div>
                </div>
            </main>
        </>
    );
}
