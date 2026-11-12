import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Plus, Wrench } from 'lucide-react';
import type { Paginated } from '@/types/pagination';
export type MaintenanceRecord = {
    id: number;
    vehicle_id: number;
    code: string;
    plate: string;
    severity: 'minor' | 'major' | 'critical';
    workshop: string;
    description: string;
    resolution: string | null;
    cost: string | null;
    completed_at: string | null;
    created_at: string;
};
export const severityLabels = {
    minor: 'Menor',
    major: 'Alta',
    critical: 'Crítica',
};
export const recordDate = (value: string) =>
    new Date(
        value.includes('T') ? value : value.replace(' ', 'T') + 'Z',
    ).toLocaleString('es-MX', {
        timeZone: 'America/Mexico_City',
        dateStyle: 'short',
        timeStyle: 'short',
    });
type Props = {
    records: Paginated<MaintenanceRecord>;
    filters: { state: string; vehicle_id: string | number };
    vehicles: { id: number; code: string }[];
    stats: { in_workshop: number; open: number; closed: number };
    without_report: { id: number; code: string }[];
};
export default function Maintenance({
    records,
    filters,
    vehicles,
    stats,
    without_report,
}: Props) {
    const filter = (key: string, value: string) =>
        router.get(
            '/admin/fleet/maintenance',
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    return (
        <>
            <Head title="Mantenimiento de flota" />
            <main className="min-h-full space-y-6 bg-background p-4 sm:p-8">
                <Link href="/admin/fleet" className="text-sm text-primary">
                    ← Consola de flota
                </Link>
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="mb-2 text-xs tracking-widest text-primary uppercase">
                            UniRoute · Control técnico
                        </p>
                        <h1 className="text-3xl font-bold">
                            Alertas y Mantenimiento
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Reportes de falla, unidades en taller e historial de
                            reparaciones.
                        </p>
                    </div>
                    <Link
                        href="/admin/fleet/maintenance/create"
                        className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary-hover"
                    >
                        <Plus className="size-4" />
                        Reportar falla
                    </Link>
                </header>
                <section
                    aria-label="Resumen de mantenimiento"
                    className="grid gap-4 sm:grid-cols-3"
                >
                    {[
                        {
                            label: 'Unidades en taller',
                            value: stats.in_workshop,
                        },
                        { label: 'Reportes abiertos', value: stats.open },
                        {
                            label: 'Reparaciones completadas',
                            value: stats.closed,
                        },
                    ].map((item) => (
                        <article
                            key={item.label}
                            className="rounded-xl border border-border bg-card p-5"
                        >
                            <p className="text-xs text-muted-foreground">
                                {item.label}
                            </p>
                            <p className="mt-3 text-3xl font-bold">
                                {item.value}
                            </p>
                        </article>
                    ))}
                </section>
                {without_report.length > 0 && (
                    <section className="rounded-xl border border-primary/30 bg-primary/5 p-5">
                        <h2 className="flex items-center gap-2 text-sm font-semibold text-primary">
                            <Wrench className="size-4" />
                            Unidades en mantenimiento sin reporte abierto
                        </h2>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Registra la falla para documentar la reparación y
                            poder reincorporarlas.
                        </p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {without_report.map((vehicle) => (
                                <Link
                                    key={vehicle.id}
                                    href={`/admin/fleet/maintenance/create?vehicle_id=${vehicle.id}`}
                                    className="rounded-lg border border-border bg-card px-3 py-2 text-xs text-primary"
                                >
                                    {vehicle.code} · Abrir reporte
                                </Link>
                            ))}
                        </div>
                    </section>
                )}
                <section className="overflow-hidden rounded-xl border border-border bg-card">
                    <header className="flex flex-wrap items-center justify-between gap-4 border-b border-border p-5">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <ClipboardList className="size-5 text-primary" />
                            Historial de mantenimiento
                        </h2>
                        <div className="flex flex-wrap gap-3">
                            <label className="grid gap-1 text-xs text-muted-foreground">
                                Estado
                                <select
                                    value={filters.state}
                                    onChange={(event) =>
                                        filter('state', event.target.value)
                                    }
                                    className="h-9 rounded-lg border border-input bg-background px-3 text-foreground"
                                >
                                    <option value="">Todos</option>
                                    <option value="open">En taller</option>
                                    <option value="closed">Completados</option>
                                </select>
                            </label>
                            <label className="grid gap-1 text-xs text-muted-foreground">
                                Unidad
                                <select
                                    value={filters.vehicle_id}
                                    onChange={(event) =>
                                        filter('vehicle_id', event.target.value)
                                    }
                                    className="h-9 rounded-lg border border-input bg-background px-3 text-foreground"
                                >
                                    <option value="">Todas las unidades</option>
                                    {vehicles.map((vehicle) => (
                                        <option
                                            key={vehicle.id}
                                            value={vehicle.id}
                                        >
                                            {vehicle.code}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>
                    </header>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-secondary/30 text-xs text-muted-foreground">
                                <tr>
                                    {[
                                        'Unidad',
                                        'Falla reportada',
                                        'Prioridad',
                                        'Taller',
                                        'Ingreso',
                                        'Estado',
                                        'Detalle',
                                    ].map((label) => (
                                        <th key={label} className="px-5 py-4">
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {records.data.map((record) => (
                                    <tr key={record.id}>
                                        <td className="px-5 py-5">
                                            <p className="font-semibold">
                                                {record.code}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {record.plate}
                                            </p>
                                        </td>
                                        <td className="max-w-xs px-5 py-5">
                                            <p className="line-clamp-2 text-xs">
                                                {record.description}
                                            </p>
                                        </td>
                                        <td
                                            className={`px-5 py-5 text-xs ${record.severity === 'critical' ? 'text-destructive' : 'text-primary'}`}
                                        >
                                            {severityLabels[record.severity]}
                                        </td>
                                        <td className="px-5 py-5 text-xs">
                                            {record.workshop}
                                        </td>
                                        <td className="px-5 py-5 text-xs whitespace-nowrap">
                                            {recordDate(record.created_at)}
                                        </td>
                                        <td className="px-5 py-5 text-xs whitespace-nowrap">
                                            <span
                                                className={
                                                    record.completed_at
                                                        ? 'text-live'
                                                        : 'text-primary'
                                                }
                                            >
                                                {record.completed_at
                                                    ? 'Completado'
                                                    : 'En taller'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-5">
                                            <Link
                                                href={`/admin/fleet/maintenance/${record.id}`}
                                                className="text-xs whitespace-nowrap text-primary"
                                            >
                                                {record.completed_at
                                                    ? 'Ver historial'
                                                    : 'Ver y reincorporar'}{' '}
                                                →
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {!records.data.length && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="p-12 text-center text-muted-foreground"
                                        >
                                            No hay reportes que coincidan con
                                            los filtros.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <footer className="flex flex-wrap justify-between gap-3 border-t border-border p-5 text-xs">
                        <span className="text-muted-foreground">
                            Mostrando {records.from ?? 0}–{records.to ?? 0} de{' '}
                            {records.total} reportes
                        </span>
                        <nav
                            aria-label="Páginas de mantenimiento"
                            className="flex gap-4"
                        >
                            {records.prev_page_url && (
                                <Link
                                    href={records.prev_page_url}
                                    preserveScroll
                                    className="text-primary"
                                >
                                    Anterior
                                </Link>
                            )}
                            <span>
                                {records.current_page} / {records.last_page}
                            </span>
                            {records.next_page_url && (
                                <Link
                                    href={records.next_page_url}
                                    preserveScroll
                                    className="text-primary"
                                >
                                    Siguiente
                                </Link>
                            )}
                        </nav>
                    </footer>
                </section>
            </main>
        </>
    );
}
