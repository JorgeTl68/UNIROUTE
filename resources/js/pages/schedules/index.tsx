
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type Stop = {
    id: number;
    name: string;
    pivot: {
        position: number;
        minutes_from_start: number;
    };
};

type EstimatedArrival = {
    stop_id: number;
    position: number;
    arrival_time: string;
};

type Schedule = {
    id: number;
    route_id: number;
    weekday: number;
    departs_at: string;
    estimated_arrivals: EstimatedArrival[];
};

type TransitRoute = {
    id: number;
    number: number;
    name: string;
    color: string;
    is_active: boolean;
    frequency_minutes?: number | null;
    service_starts_at?: string | null;
    service_ends_at?: string | null;
    stops: Stop[];
    schedules: Schedule[];
};

type Props = {
    routes: TransitRoute[];
    selectedDay: number;
};

const days = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
];

function toMinutes(time: string): number {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}

function formatTime(time?: string | null): string {
    return time ? time.slice(0, 5) : '--:--';
}

function getScheduleKey(routeId: number, scheduleId: number): string {
    return `${routeId}-${scheduleId}`;
}

export default function SchedulesIndex({
    routes,
    selectedDay,
}: Props) {
    const [day, setDay] = useState(selectedDay);
    const [selectedRoute, setSelectedRoute] = useState<number | null>(null);
    const [selectedSchedule, setSelectedSchedule] = useState<string | null>(null);
    const [expandedRoutes, setExpandedRoutes] = useState<number[]>([]);

    const availableRoutes = useMemo(
        () => routes.filter((route) => route.is_active),
        [routes],
    );

    const now = new Date();
    const today = ((now.getDay() + 6) % 7) + 1;
    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    const departures = useMemo(
        () =>
            availableRoutes
                .flatMap((route) =>
                    route.schedules.map((schedule) => ({
                        ...schedule,
                        route,
                        key: getScheduleKey(route.id, schedule.id),
                    })),
                )
                .filter((item) => item.weekday === day)
                .sort(
                    (a, b) =>
                        toMinutes(a.departs_at) - toMinutes(b.departs_at),
                ),
        [availableRoutes, day],
    );

    const nextDeparture =
        day === today
            ? departures.find(
                  (departure) =>
                      toMinutes(departure.departs_at) >= currentMinutes,
              )
            : departures[0];

    const routeSelected =
        availableRoutes.find((route) => route.id === selectedRoute) ??
        nextDeparture?.route ??
        availableRoutes[0];

    const routeDepartures = departures.filter(
        (departure) => departure.route.id === routeSelected?.id,
    );

    const activeDeparture =
        routeDepartures.find(
            (departure) => departure.key === selectedSchedule,
        ) ??
        (day === today
            ? routeDepartures.find(
                  (departure) =>
                      toMinutes(departure.departs_at) >= currentMinutes,
              )
            : routeDepartures[0]) ??
        routeDepartures[0];

    function selectRoute(routeId: number) {
        setSelectedRoute(routeId);
        setSelectedSchedule(null);
    }

    function toggleExpanded(routeId: number) {
        setExpandedRoutes((current) =>
            current.includes(routeId)
                ? current.filter((id) => id !== routeId)
                : [...current, routeId],
        );
    }

    return (
        <>
            <Head title="Horarios e Itinerarios" />

            <main className="min-h-full w-full space-y-6 bg-background p-4 text-foreground sm:p-6 lg:p-8">
                {/* Encabezado */}
                <header className="flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.2em] text-primary">
                            UniRoute · Transporte universitario
                        </p>

                        <h1 className="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">
                            Horarios e Itinerarios
                        </h1>

                        <p className="mt-2 text-sm text-muted-foreground">
                            Consulta las salidas de cada ruta y sus tiempos estimados.
                        </p>
                    </div>

                    <div className="flex min-w-[260px] items-center gap-4 rounded-xl border border-border bg-card px-5 py-4 shadow-sm">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                className="size-6"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                        </div>

                        <div>
                            <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">
                                Tu próxima salida
                            </p>

                            {nextDeparture ? (
                                <p className="mt-1 text-sm font-bold">
                                    Ruta {nextDeparture.route.number}
                                    <span className="ml-2 text-primary">
                                        {formatTime(nextDeparture.departs_at)}
                                    </span>
                                </p>
                            ) : (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Sin salidas disponibles
                                </p>
                            )}
                        </div>
                    </div>
                </header>

                {/* Selector de días */}
                <div className="overflow-x-auto">
                    <div
                        className="flex w-max gap-1 rounded-xl border border-border bg-card p-1"
                        role="tablist"
                        aria-label="Seleccionar día"
                    >
                        {days.map((item) => (
                            <button
                                key={item.value}
                                type="button"
                                role="tab"
                                aria-selected={day === item.value}
                                onClick={() => {
                                    setDay(item.value);
                                    setSelectedSchedule(null);
                                }}
                                className={`rounded-lg px-5 py-2.5 text-sm font-semibold transition ${
                                    day === item.value
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                }`}
                            >
                                {item.label}
                                {item.value === today && (
                                    <span className="ml-1 text-[10px]">· Hoy</span>
                                )}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Contenido */}
                <div className="grid min-w-0 gap-6 xl:grid-cols-12">
                    {/* Salidas por ruta */}
                    <section className="min-w-0 space-y-4 xl:col-span-8">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold">
                                Salidas por ruta
                            </h2>

                            <span className="rounded-full bg-muted px-3 py-1 text-xs text-muted-foreground">
                                {departures.length} salidas
                            </span>
                        </div>

                        {availableRoutes.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-border bg-card p-10 text-center">
                                <p className="font-semibold">
                                    No hay rutas disponibles.
                                </p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    No encontramos rutas activas para tu campus.
                                </p>
                            </div>
                        ) : (
                            availableRoutes.map((route) => {
                                const routeSchedules = departures.filter(
                                    (departure) =>
                                        departure.route.id === route.id,
                                );

                                const routeNext =
                                    day === today
                                        ? routeSchedules.find(
                                              (departure) =>
                                                  toMinutes(departure.departs_at) >= currentMinutes,
                                          )
                                        : routeSchedules[0];

                                const isSelected =
                                    routeSelected?.id === route.id;

                                const expanded = expandedRoutes.includes(route.id);

                                const visibleSchedules = expanded
                                    ? routeSchedules
                                    : routeSchedules.slice(0, 10);

                                const duration =
                                    route.stops.length > 0
                                        ? route.stops[route.stops.length - 1]
                                              .pivot.minutes_from_start
                                        : 0;

                                return (
                                    <article
                                        key={route.id}
                                        className={`rounded-2xl border bg-card p-5 transition ${
                                            isSelected
                                                ? 'border-primary/60 shadow-md'
                                                : 'border-border hover:border-primary/40'
                                        }`}
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div className="flex items-start gap-3">
                                                <span
                                                    className="flex size-10 shrink-0 items-center justify-center rounded-lg text-sm font-extrabold text-white"
                                                    style={{
                                                        backgroundColor: route.color || '#2563eb',
                                                    }}
                                                >
                                                    {route.number}
                                                </span>

                                                <div>
                                                    <h3 className="font-bold">
                                                        {route.name}
                                                    </h3>

                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {route.frequency_minutes
                                                            ? `Cada ${route.frequency_minutes} min`
                                                            : 'Horario programado'}
                                                        {' · '}
                                                        {formatTime(route.service_starts_at)}
                                                        {' a '}
                                                        {formatTime(route.service_ends_at)}
                                                        {' · '}
                                                        {route.stops.length} paradas
                                                        {' · '}
                                                        {duration} min de recorrido
                                                    </p>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                onClick={() => selectRoute(route.id)}
                                                aria-pressed={isSelected}
                                                className={`rounded-lg px-3 py-2 text-xs font-bold ${
                                                    isSelected
                                                        ? 'bg-primary/10 text-primary'
                                                        : 'bg-muted text-muted-foreground hover:bg-muted/70'
                                                }`}
                                            >
                                                {isSelected
                                                    ? 'Itinerario seleccionado'
                                                    : 'Ver itinerario'}
                                            </button>
                                        </div>

                                        <div className="mt-5 flex flex-wrap gap-2">
                                            {visibleSchedules.length === 0 ? (
                                                <p className="rounded-lg bg-muted px-3 py-2 text-xs text-muted-foreground">
                                                    Sin salidas para este día.
                                                </p>
                                            ) : (
                                                visibleSchedules.map((departure) => {
                                                    const isPast =
                                                        day === today &&
                                                        toMinutes(departure.departs_at) < currentMinutes;

                                                    const isNext =
                                                        routeNext?.key === departure.key;

                                                    const isActive =
                                                        activeDeparture?.key === departure.key &&
                                                        isSelected;

                                                    return (
                                                        <button
                                                            key={departure.key}
                                                            type="button"
                                                            onClick={() => {
                                                                setSelectedRoute(route.id);
                                                                setSelectedSchedule(departure.key);
                                                            }}
                                                            className={`rounded-lg px-3.5 py-2 text-sm font-bold transition ${
                                                                isActive
                                                                    ? 'bg-primary text-primary-foreground'
                                                                    : isNext
                                                                      ? 'bg-amber-400 text-amber-950'
                                                                      : isPast
                                                                        ? 'bg-muted text-muted-foreground/60 line-through'
                                                                        : 'bg-muted text-foreground hover:bg-muted/70'
                                                            }`}
                                                        >
                                                            {isNext && !isActive && '◷ '}
                                                            {formatTime(departure.departs_at)}
                                                        </button>
                                                    );
                                                })
                                            )}

                                            {routeSchedules.length > 10 && (
                                                <button
                                                    type="button"
                                                    onClick={() => toggleExpanded(route.id)}
                                                    className="rounded-lg px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/10"
                                                >
                                                    {expanded
                                                        ? 'Mostrar menos'
                                                        : `+ ${routeSchedules.length - 10} más`}
                                                </button>
                                            )}
                                        </div>
                                    </article>
                                );
                            })
                        )}
                    </section>

                    {/* Itinerario */}
                    <aside className="min-w-0 xl:col-span-4">
                        <div className="rounded-2xl border border-border bg-card p-5 shadow-sm xl:sticky xl:top-24">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className="text-[11px] font-bold uppercase tracking-widest text-primary">
                                        Itinerario
                                    </p>

                                    <h2 className="mt-1 text-lg font-extrabold">
                                        {routeSelected
                                            ? `Ruta ${routeSelected.number}`
                                            : 'Sin ruta seleccionada'}
                                    </h2>
                                </div>

                                {routeSelected && (
                                    <span
                                        className="flex size-10 items-center justify-center rounded-lg text-sm font-extrabold text-white"
                                        style={{
                                            backgroundColor: routeSelected.color || '#2563eb',
                                        }}
                                    >
                                        {routeSelected.number}
                                    </span>
                                )}
                            </div>

                            {routeSelected && activeDeparture ? (
                                <>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {routeSelected.name}
                                    </p>

                                    <div className="mt-3 rounded-lg bg-muted p-3">
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                                            Salida seleccionada
                                        </p>
                                        <p className="mt-1 text-xl font-extrabold text-primary">
                                            {formatTime(activeDeparture.departs_at)}
                                        </p>
                                    </div>

                                    <ol className="relative mt-6 space-y-5">
                                        {routeSelected.stops.map((stop, index) => {
                                            const estimatedArrival =
                                                activeDeparture.estimated_arrivals.find(
                                                    (arrival) =>
                                                        arrival.stop_id === stop.id,
                                                );

                                            const arrivalTime =
                                                estimatedArrival?.arrival_time ?? '--:--';

                                            const isFirst = index === 0;
                                            const isLast =
                                                index === routeSelected.stops.length - 1;

                                            return (
                                                <li
                                                    key={stop.id}
                                                    className="relative"
                                                >
                                                    <div className="relative flex items-start gap-3">
                                                        {!isLast && (
                                                            <span className="absolute left-[7px] top-4 h-[calc(100%+12px)] w-0.5 bg-border" />
                                                        )}

                                                        <span
                                                            className={`relative z-10 mt-1 size-4 shrink-0 rounded-full border-[3px] border-card ${
                                                                isFirst
                                                                    ? 'bg-primary'
                                                                    : isLast
                                                                      ? 'bg-amber-500'
                                                                      : 'bg-muted-foreground'
                                                            }`}
                                                        />

                                                        <div className="flex min-w-0 flex-1 justify-between gap-3">
                                                            <div>
                                                                <p
                                                                    className={`text-sm ${
                                                                        isLast
                                                                            ? 'font-bold text-amber-600'
                                                                            : 'text-foreground'
                                                                    }`}
                                                                >
                                                                    {stop.name}
                                                                </p>

                                                                <p className="mt-1 text-[11px] text-muted-foreground">
                                                                    {isFirst
                                                                        ? 'Salida'
                                                                        : `+${stop.pivot.minutes_from_start} minutos`}
                                                                </p>
                                                            </div>

                                                            <span
                                                                className={`shrink-0 text-sm font-bold tabular-nums ${
                                                                    isFirst
                                                                        ? 'text-primary'
                                                                        : isLast
                                                                          ? 'text-amber-600'
                                                                          : 'text-muted-foreground'
                                                                }`}
                                                            >
                                                                {arrivalTime}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </li>
                                            );
                                        })}
                                    </ol>

                                    <p className="mt-6 rounded-lg border border-border bg-muted/50 p-3 text-xs leading-relaxed text-muted-foreground">
                                        Los horarios de llegada por parada son
                                        estimados y son proporcionados por el
                                        servidor a partir de la hora de salida
                                        y los minutos de recorrido.
                                    </p>
                                </>
                            ) : (
                                <div className="mt-6 rounded-xl border border-dashed border-border p-6 text-center">
                                    <p className="text-sm text-muted-foreground">
                                        {routeSelected
                                            ? 'Esta ruta no tiene salidas programadas para el día seleccionado.'
                                            : 'No hay rutas disponibles para mostrar.'}
                                    </p>
                                </div>
                            )}
                        </div>
                    </aside>
                </div>
            </main>
        </>
    );
}
