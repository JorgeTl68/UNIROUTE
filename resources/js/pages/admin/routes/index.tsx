import { Head, Link } from '@inertiajs/react';
import { CalendarClock, Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

type RouteSummary = {
    id: number;
    number: number;
    name: string;
    color: string;
};

type TransitRoute = RouteSummary & {
    campus: string;
    is_active: boolean;
    stops_count: number;
    schedules_count: number;
    duration_minutes: number | null;
    frequency_minutes: number;
    service_starts_at: string;
    service_ends_at: string;
};

type Stop = {
    id: number;
    name: string;
    has_shelter: boolean;
    routes: RouteSummary[];
};

export default function RoutesIndex({
    routes,
    stops,
}: {
    routes: TransitRoute[];
    stops: Stop[];
}) {
    return (
        <div className="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <Head title="Rutas y paradas" />
            <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                    Rutas y paradas
                </h1>
                <Button asChild>
                    <Link href="/admin/routes/create">
                        <Plus className="size-4" />
                        Nueva ruta
                    </Link>
                </Button>
            </div>

            <section aria-label="Rutas" className="grid gap-4 lg:grid-cols-2">
                {routes.map((route) => (
                    <Card key={route.id} className="overflow-hidden pt-0">
                        <div
                            aria-hidden="true"
                            className="h-1.5"
                            style={{ backgroundColor: route.color }}
                        />
                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                            <div className="flex items-start gap-3">
                                <span className="grid size-10 shrink-0 place-items-center rounded-md bg-secondary font-extrabold tabular-nums">
                                    <span className="sr-only">Ruta </span>
                                    {route.number}
                                </span>
                                <div>
                                    <h2 className="font-bold">{route.name}</h2>
                                    <p className="text-sm text-muted-foreground">
                                        {route.campus}
                                    </p>
                                </div>
                            </div>
                            <Badge
                                variant="secondary"
                                className={
                                    route.is_active
                                        ? 'bg-live/10 text-live'
                                        : ''
                                }
                            >
                                {route.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-2 gap-2 text-center sm:grid-cols-3">
                                {[
                                    ['Paradas', route.stops_count],
                                    ['Horarios', route.schedules_count],
                                    [
                                        'Duración',
                                        route.duration_minutes === null
                                            ? '—'
                                            : `${route.duration_minutes} min`,
                                    ],
                                    [
                                        'Frecuencia',
                                        `${route.frequency_minutes} min`,
                                    ],
                                    [
                                        'Servicio',
                                        `${route.service_starts_at.slice(0, 5)} – ${route.service_ends_at.slice(0, 5)}`,
                                    ],
                                ].map(([label, value]) => (
                                    <div
                                        key={label}
                                        className={`rounded-lg bg-background/50 p-2 ${label === 'Servicio' ? 'col-span-2' : ''}`}
                                    >
                                        <dt className="text-xs text-muted-foreground">
                                            {label}
                                        </dt>
                                        <dd className="text-sm font-bold tabular-nums">
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                            <Button
                                asChild
                                variant="outline"
                                className="mt-4 w-full"
                            >
                                <Link
                                    href={`/admin/routes/${route.id}/schedules`}
                                >
                                    <CalendarClock className="size-4" />
                                    Horarios
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ))}
                {routes.length === 0 && (
                    <p className="rounded-xl border bg-card p-5 text-sm text-muted-foreground lg:col-span-2">
                        No hay rutas registradas.
                    </p>
                )}
            </section>

            <Card>
                <CardHeader>
                    <h2 id="stops-title" className="text-lg font-bold">
                        Paradas
                    </h2>
                </CardHeader>
                {stops.length === 0 ? (
                    <CardContent className="text-sm text-muted-foreground">
                        No hay paradas registradas.
                    </CardContent>
                ) : (
                    <div className="overflow-x-auto">
                        <table
                            aria-labelledby="stops-title"
                            className="w-full text-left text-sm"
                        >
                            <thead className="border-y bg-secondary/50 text-muted-foreground">
                                <tr>
                                    <th scope="col" className="px-4 py-3">
                                        Parada
                                    </th>
                                    <th scope="col" className="px-4 py-3">
                                        Rutas
                                    </th>
                                    <th scope="col" className="px-4 py-3">
                                        Techo
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {stops.map((stop) => (
                                    <tr key={stop.id}>
                                        <th
                                            scope="row"
                                            className="px-4 py-3 font-semibold"
                                        >
                                            {stop.name}
                                        </th>
                                        <td className="px-4 py-3">
                                            {stop.routes.length ? (
                                                <ul className="flex flex-wrap gap-1">
                                                    {stop.routes.map(
                                                        (route) => (
                                                            <li key={route.id}>
                                                                <Badge
                                                                    variant="outline"
                                                                    title={
                                                                        route.name
                                                                    }
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        className="size-2 rounded-full"
                                                                        style={{
                                                                            backgroundColor:
                                                                                route.color,
                                                                        }}
                                                                    />
                                                                    <span className="sr-only">
                                                                        Ruta{' '}
                                                                        {
                                                                            route.name
                                                                        }
                                                                        , número
                                                                    </span>
                                                                    {
                                                                        route.number
                                                                    }
                                                                </Badge>
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Sin rutas
                                                </span>
                                            )}
                                        </td>
                                        <td
                                            className={`px-4 py-3 ${stop.has_shelter ? 'text-live' : 'text-muted-foreground'}`}
                                        >
                                            {stop.has_shelter ? 'Sí' : 'No'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </div>
    );
}

RoutesIndex.layout = {
    breadcrumbs: [{ title: 'Rutas y paradas', href: '/admin/routes' }],
};
