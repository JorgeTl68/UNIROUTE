import { Head, router } from '@inertiajs/react';
import { Download, Filter } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { occupancyTone } from '@/components/transit/occupancy-bar';
import { cn } from '@/lib/utils';
import { clockTime } from '@/lib/transit-format';

type Row = {
    id: number;
    scheduled_at: string;
    date: string;
    hour: string;
    route_number: number;
    route_name: string;
    vehicle_code: string;
    occupancy: number;
    capacity: number;
    percent: number;
};

type Props = {
    filters: { from: string; to: string; route: number | null };
    routes: { id: number; number: number; name: string }[];
    summary: {
        trips: number;
        average_percent: number | null;
        saturated: number;
        peak_hour: string | null;
    };
    hourly: { hour: string; percent: number; trips: number }[];
    rows: Row[];
    saturated_percent: number;
};

const fieldClass =
    'rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50';

/** Colores de DESIGN.md: barras en secondary y la hora pico en primary. */
const barColor = '#e0c297';
const peakColor = '#fddeb1';

export default function OccupancyReport({
    filters,
    routes,
    summary,
    hourly,
    rows,
    saturated_percent,
}: Props) {
    const apply = (changes: Partial<Props['filters']>) => {
        const next = { ...filters, ...changes };

        router.get(
            '/admin/reports/occupancy',
            {
                from: next.from,
                to: next.to,
                ...(next.route ? { route: next.route } : {}),
            },
            { preserveScroll: true, preserveState: true },
        );
    };
    const exportUrl = `/admin/reports/occupancy/export?${new URLSearchParams({
        from: filters.from,
        to: filters.to,
        ...(filters.route ? { route: String(filters.route) } : {}),
    }).toString()}`;

    const kpis = [
        { label: 'Viajes con aforo', value: String(summary.trips) },
        {
            label: 'Ocupación promedio',
            value:
                summary.average_percent === null
                    ? '—'
                    : `${summary.average_percent} %`,
        },
        {
            label: `Viajes saturados (≥ ${saturated_percent} %)`,
            value: String(summary.saturated),
            accent: summary.saturated > 0 ? 'text-destructive' : undefined,
        },
        { label: 'Hora pico', value: summary.peak_hour ?? '—' },
    ];

    return (
        <main className="flex-1 space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <Head title="Reportes de aforo" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="text-[11px] font-semibold tracking-widest text-gold uppercase">
                        Panel de administración
                    </p>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
                        Reportes de aforo
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ocupación que registran los conductores en cada viaje.
                    </p>
                </div>
                <a
                    href={exportUrl}
                    className="flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-bold text-primary-foreground hover:bg-primary-hover"
                >
                    <Download className="size-4" />
                    Exportar CSV
                </a>
            </div>

            <div className="flex flex-wrap items-end gap-3 rounded-2xl border border-border bg-card p-4">
                <Filter className="mb-2.5 size-4 text-primary" />
                <label className="space-y-1 text-xs text-muted-foreground">
                    <span className="block">Desde</span>
                    <input
                        type="date"
                        value={filters.from}
                        max={filters.to}
                        onChange={(event) =>
                            apply({ from: event.target.value })
                        }
                        className={fieldClass}
                    />
                </label>
                <label className="space-y-1 text-xs text-muted-foreground">
                    <span className="block">Hasta</span>
                    <input
                        type="date"
                        value={filters.to}
                        min={filters.from}
                        onChange={(event) => apply({ to: event.target.value })}
                        className={fieldClass}
                    />
                </label>
                <label className="space-y-1 text-xs text-muted-foreground">
                    <span className="block">Ruta</span>
                    <select
                        value={filters.route ?? ''}
                        onChange={(event) =>
                            apply({
                                route:
                                    event.target.value === ''
                                        ? null
                                        : Number(event.target.value),
                            })
                        }
                        className={fieldClass}
                    >
                        <option value="">Todas las rutas</option>
                        {routes.map((route) => (
                            <option key={route.id} value={route.id}>
                                Ruta {route.number} · {route.name}
                            </option>
                        ))}
                    </select>
                </label>
            </div>

            <dl className="grid grid-cols-2 gap-3 xl:grid-cols-4">
                {kpis.map((kpi) => (
                    <div
                        key={kpi.label}
                        className="rounded-2xl border border-border bg-card p-4"
                    >
                        <dt className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            {kpi.label}
                        </dt>
                        <dd
                            className={cn(
                                'mt-1 text-3xl font-extrabold text-white',
                                kpi.accent,
                            )}
                        >
                            {kpi.value}
                        </dd>
                    </div>
                ))}
            </dl>

            <section className="rounded-2xl border border-border bg-card p-5">
                <h2 className="text-lg font-bold">
                    Ocupación por hora de salida
                </h2>
                {hourly.length === 0 ? (
                    <p className="mt-3 text-sm text-muted-foreground">
                        No hay viajes con aforo en este periodo.
                    </p>
                ) : (
                    <div className="mt-4 h-72">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={hourly}>
                                <CartesianGrid
                                    vertical={false}
                                    stroke="#2f3e57"
                                />
                                <XAxis
                                    dataKey="hour"
                                    stroke="#bac7e4"
                                    fontSize={12}
                                    tickLine={false}
                                />
                                <YAxis
                                    stroke="#bac7e4"
                                    fontSize={12}
                                    tickLine={false}
                                    domain={[0, 100]}
                                    unit=" %"
                                />
                                <Tooltip
                                    cursor={{ fill: '#2f3e5766' }}
                                    contentStyle={{
                                        background: '#17243a',
                                        border: '1px solid #2f3e57',
                                        borderRadius: 12,
                                        color: '#ecf1f9',
                                    }}
                                    formatter={(value) => [
                                        `${String(value)} %`,
                                        'Ocupación promedio',
                                    ]}
                                />
                                <Bar dataKey="percent" radius={[6, 6, 0, 0]}>
                                    {hourly.map((item) => (
                                        <Cell
                                            key={item.hour}
                                            fill={
                                                item.hour === summary.peak_hour
                                                    ? peakColor
                                                    : barColor
                                            }
                                        />
                                    ))}
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                )}
            </section>

            {rows.length > 0 && (
                <section className="overflow-hidden rounded-2xl border border-border bg-card">
                    <h2 className="px-5 pt-5 text-lg font-bold">
                        Viajes recientes
                    </h2>
                    <div className="relative mt-3 overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-y border-border bg-secondary/40 text-[11px] tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-5 py-2.5 font-semibold">
                                        Fecha
                                    </th>
                                    <th className="px-5 py-2.5 font-semibold">
                                        Salida
                                    </th>
                                    <th className="px-5 py-2.5 font-semibold">
                                        Ruta
                                    </th>
                                    <th className="px-5 py-2.5 font-semibold">
                                        Unidad
                                    </th>
                                    <th className="px-5 py-2.5 text-right font-semibold">
                                        Ocupación
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.map((row) => (
                                    <tr key={row.id}>
                                        <td className="px-5 py-2.5 whitespace-nowrap text-muted-foreground">
                                            {row.date}
                                        </td>
                                        <td className="px-5 py-2.5 font-bold text-white">
                                            {clockTime(row.scheduled_at)}
                                        </td>
                                        <td className="px-5 py-2.5 whitespace-nowrap text-foreground">
                                            Ruta {row.route_number} ·{' '}
                                            {row.route_name}
                                        </td>
                                        <td className="px-5 py-2.5 text-muted-foreground">
                                            {row.vehicle_code}
                                        </td>
                                        <td className="px-5 py-2.5 text-right whitespace-nowrap">
                                            <span className="text-foreground">
                                                {row.occupancy}/{row.capacity}
                                            </span>{' '}
                                            <span
                                                className={cn(
                                                    'ml-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-bold text-primary-foreground',
                                                    occupancyTone(row.percent),
                                                )}
                                            >
                                                {row.percent} %
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            )}
        </main>
    );
}
