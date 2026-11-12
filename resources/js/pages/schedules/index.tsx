import { Head, router } from '@inertiajs/react';
import { CalendarX2, Timer } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import type { Weekday } from '@/types/transit';

type ScheduleRoute = {
    id: number;
    number: number;
    name: string;
    description: string | null;
    color: string;
    frequency_minutes: number | null;
    stops: { id: number; name: string; minutes_from_start: number }[];
    departures: string[];
};

type Props = {
    campus: { id: number; name: string; slug: string } | null;
    weekday: Weekday;
    today: Weekday;
    now: string;
    routes: ScheduleRoute[];
};

const days: { value: Weekday; label: string }[] = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
];

/** Salidas que se ven antes de "Ver todas". */
const VISIBLE_DEPARTURES = 8;

function addMinutes(time: string, minutes: number): string {
    const [hours, mins] = time.split(':').map(Number);
    const total = (hours * 60 + mins + minutes) % (24 * 60);

    return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}

export default function Schedules({
    campus,
    weekday,
    today,
    now,
    routes,
}: Props) {
    const isToday = weekday === today;
    const nextOf = (route: ScheduleRoute) =>
        isToday
            ? route.departures.find((time) => time >= now)
            : route.departures[0];
    const firstWithDeparture = routes.find((route) => nextOf(route));
    const [selected, setSelected] = useState<{
        routeId: number;
        time: string;
    } | null>(() =>
        firstWithDeparture
            ? {
                  routeId: firstWithDeparture.id,
                  time: nextOf(firstWithDeparture) as string,
              }
            : null,
    );
    const [expanded, setExpanded] = useState<number[]>([]);

    const nextDeparture = routes
        .map((route) => ({ route, time: nextOf(route) }))
        .filter((item): item is { route: ScheduleRoute; time: string } =>
            Boolean(item.time),
        )
        .sort((a, b) => a.time.localeCompare(b.time))[0];
    const selectedRoute = routes.find(
        (route) => route.id === selected?.routeId,
    );

    const chooseDay = (day: Weekday) =>
        router.get(
            '/schedules',
            { day, ...(campus ? { campus: campus.slug } : {}) },
            { preserveScroll: true },
        );

    return (
        <main className="flex-1 space-y-6 px-4 py-6 pb-24 sm:px-6 md:pb-6 lg:px-8">
            <Head title="Horarios e Itinerarios" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    {campus && (
                        <p className="text-[11px] font-semibold tracking-widest text-gold uppercase">
                            {campus.name}
                        </p>
                    )}
                    <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
                        Horarios e Itinerarios
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Salidas de cada ruta y a qué hora pasa por cada parada.
                    </p>
                </div>
                {isToday && nextDeparture && (
                    <div className="flex items-center gap-3 rounded-xl border border-primary/40 bg-primary/10 px-4 py-3">
                        <Timer className="size-6 text-primary" />
                        <div>
                            <p className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                Próxima salida
                            </p>
                            <p className="text-sm font-semibold text-strong">
                                Ruta {nextDeparture.route.number} ·{' '}
                                <span className="text-primary">
                                    {nextDeparture.time}
                                </span>{' '}
                                desde{' '}
                                {nextDeparture.route.stops[0]?.name ??
                                    nextDeparture.route.name}
                            </p>
                        </div>
                    </div>
                )}
            </div>

            <div className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <div
                    className="flex w-max gap-1 rounded-xl border border-border bg-card p-1"
                    role="tablist"
                    aria-label="Día"
                >
                    {days.map((day) => (
                        <button
                            key={day.value}
                            type="button"
                            role="tab"
                            aria-selected={day.value === weekday}
                            onClick={() => chooseDay(day.value)}
                            className={cn(
                                'rounded-md px-4 py-2 text-sm',
                                day.value === weekday
                                    ? 'bg-primary font-semibold text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {day.label}
                            {day.value === today && ' · Hoy'}
                        </button>
                    ))}
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-12">
                <section
                    className="min-w-0 space-y-4 xl:col-span-8"
                    aria-label="Salidas por ruta"
                >
                    {routes.length === 0 && (
                        <p className="rounded-2xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                            Este campus todavía no tiene rutas activas.
                        </p>
                    )}
                    {routes.map((route) => {
                        const next = nextOf(route);
                        const isOpen = expanded.includes(route.id);
                        const duration =
                            route.stops[route.stops.length - 1]
                                ?.minutes_from_start ?? 0;
                        const upcomingIndex = Math.max(
                            0,
                            route.departures.findIndex((time) => time === next),
                        );
                        const shown = isOpen
                            ? route.departures
                            : route.departures.slice(
                                  Math.max(0, upcomingIndex - 2),
                                  Math.max(0, upcomingIndex - 2) +
                                      VISIBLE_DEPARTURES,
                              );

                        return (
                            <article
                                key={route.id}
                                className={cn(
                                    'rounded-2xl border bg-card p-5',
                                    selected?.routeId === route.id
                                        ? 'border-primary/40'
                                        : 'border-border',
                                )}
                            >
                                <div className="flex items-start gap-3">
                                    <span
                                        className="flex size-9 shrink-0 items-center justify-center rounded-md text-sm font-extrabold text-ink"
                                        style={{ backgroundColor: route.color }}
                                    >
                                        {route.number}
                                    </span>
                                    <div>
                                        <h2 className="text-base font-bold">
                                            {route.name}
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            {route.frequency_minutes !== null &&
                                                `Cada ${route.frequency_minutes} min · `}
                                            {route.stops.length} paradas ·{' '}
                                            {duration} min de recorrido
                                        </p>
                                    </div>
                                </div>

                                {route.departures.length === 0 ? (
                                    <p className="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                                        <CalendarX2 className="size-4" />
                                        Sin salidas este día.
                                    </p>
                                ) : (
                                    <div className="mt-4 flex flex-wrap gap-2 text-sm font-semibold">
                                        {shown.map((time) => {
                                            const past = isToday && time < now;
                                            const isNext = time === next;
                                            const isSelected =
                                                selected?.routeId ===
                                                    route.id &&
                                                selected.time === time;

                                            return (
                                                <button
                                                    key={time}
                                                    type="button"
                                                    onClick={() =>
                                                        setSelected({
                                                            routeId: route.id,
                                                            time,
                                                        })
                                                    }
                                                    aria-pressed={isSelected}
                                                    className={cn(
                                                        'rounded-md px-3 py-1.5',
                                                        past &&
                                                            'bg-background/50 text-placeholder line-through decoration-placeholder/50',
                                                        !past &&
                                                            isNext &&
                                                            'bg-primary font-bold text-primary-foreground',
                                                        !past &&
                                                            !isNext &&
                                                            'bg-secondary text-foreground',
                                                        isSelected &&
                                                            'ring-2 ring-gold ring-offset-2 ring-offset-card',
                                                    )}
                                                >
                                                    {time}
                                                </button>
                                            );
                                        })}
                                        {route.departures.length >
                                            VISIBLE_DEPARTURES && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setExpanded((current) =>
                                                        isOpen
                                                            ? current.filter(
                                                                  (id) =>
                                                                      id !==
                                                                      route.id,
                                                              )
                                                            : [
                                                                  ...current,
                                                                  route.id,
                                                              ],
                                                    )
                                                }
                                                className="rounded-md px-3 py-1.5 text-primary hover:underline"
                                            >
                                                {isOpen
                                                    ? 'Ver menos'
                                                    : `Ver todas (${route.departures.length})`}
                                            </button>
                                        )}
                                    </div>
                                )}
                            </article>
                        );
                    })}
                </section>

                <aside className="min-w-0 xl:col-span-4">
                    <div className="rounded-2xl border border-border bg-card p-5 xl:sticky xl:top-24">
                        {selectedRoute && selected ? (
                            <>
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-[11px] font-semibold tracking-wider text-gold uppercase">
                                            Itinerario · salida {selected.time}
                                        </p>
                                        <h2 className="mt-1 text-lg font-bold">
                                            {selectedRoute.name}
                                        </h2>
                                    </div>
                                    <span
                                        className="flex size-9 shrink-0 items-center justify-center rounded-md text-sm font-extrabold text-ink"
                                        style={{
                                            backgroundColor:
                                                selectedRoute.color,
                                        }}
                                    >
                                        {selectedRoute.number}
                                    </span>
                                </div>
                                <ol className="relative mt-5 space-y-4">
                                    <span
                                        className="absolute top-2 bottom-2 left-[7px] w-0.5 bg-border"
                                        aria-hidden="true"
                                    />
                                    {selectedRoute.stops.map((stop, index) => (
                                        <li
                                            key={stop.id}
                                            className="relative flex items-start gap-3"
                                        >
                                            <span
                                                className={cn(
                                                    'relative z-10 mt-1 size-4 shrink-0 rounded-full border-[3px] border-card',
                                                    index === 0
                                                        ? 'bg-route'
                                                        : 'bg-secondary',
                                                )}
                                            />
                                            <div className="flex flex-1 justify-between gap-3">
                                                <span className="text-sm text-foreground">
                                                    {stop.name}
                                                </span>
                                                <span
                                                    className={cn(
                                                        'text-sm',
                                                        index === 0
                                                            ? 'font-bold text-strong'
                                                            : 'font-semibold text-muted-foreground',
                                                    )}
                                                >
                                                    {addMinutes(
                                                        selected.time,
                                                        stop.minutes_from_start,
                                                    )}
                                                </span>
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Elige una salida para ver a qué hora pasa por
                                cada parada.
                            </p>
                        )}
                    </div>
                </aside>
            </div>
        </main>
    );
}
