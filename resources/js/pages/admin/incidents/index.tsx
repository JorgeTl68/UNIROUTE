import { Head, router, useForm } from '@inertiajs/react';
import {
    CircleCheck,
    LoaderCircle,
    Megaphone,
    Plus,
    Siren,
    Split,
    TrafficCone,
    Wrench,
} from 'lucide-react';
import InputError from '@/components/input-error';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/transit-format';
import type {
    Incident,
    IncidentSeverity,
    IncidentType,
    TransitRoute,
    UserRole,
} from '@/types/transit';

type Option = { value: string; label: string };

type Props = {
    open: Incident[];
    resolved: Incident[];
    routes: Pick<TransitRoute, 'id' | 'number' | 'name' | 'color'>[];
    vehicles: { id: number; code: string }[];
    types: Option[];
    severities: Option[];
};

const typeIcons: Record<IncidentType, typeof Siren> = {
    traffic: TrafficCone,
    detour: Split,
    breakdown: Wrench,
    emergency: Siren,
};

const severityStyles: Record<
    IncidentSeverity,
    { card: string; icon: string; tag: string }
> = {
    critical: {
        card: 'border-destructive/50',
        icon: 'bg-destructive text-destructive-foreground',
        tag: 'bg-destructive/15 text-destructive',
    },
    high: {
        card: 'border-warning/40',
        icon: 'bg-warning/15 text-warning',
        tag: 'bg-warning/15 text-warning',
    },
    medium: {
        card: 'border-border',
        icon: 'bg-gold/15 text-gold',
        tag: 'bg-gold/15 text-gold',
    },
    low: {
        card: 'border-border',
        icon: 'bg-secondary text-muted-foreground',
        tag: 'bg-secondary text-muted-foreground',
    },
};

const roleLabels: Record<UserRole, string> = {
    student: 'estudiante',
    driver: 'conductor',
    admin: 'transporte',
};

const fieldClass =
    'w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50';

function IncidentCard({ incident }: { incident: Incident }) {
    const Icon = typeIcons[incident.type];
    const style = severityStyles[incident.severity];
    const context = [
        incident.route && `Ruta ${incident.route.number}`,
        incident.vehicle?.code,
        timeAgo(incident.created_at),
    ].filter(Boolean);

    return (
        <article className={cn('rounded-2xl border bg-card p-5', style.card)}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-start gap-3">
                    <span
                        className={cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl',
                            style.icon,
                        )}
                    >
                        <Icon className="size-5" />
                    </span>
                    <div>
                        <p className="flex flex-wrap items-center gap-2 font-bold text-white">
                            {incident.type_label}
                            <span
                                className={cn(
                                    'rounded px-1.5 py-0.5 text-[10px] font-extrabold tracking-wider uppercase',
                                    style.tag,
                                )}
                            >
                                {incident.severity_label}
                            </span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {context.join(' · ')}
                        </p>
                    </div>
                </div>
                {incident.reporter && (
                    <span className="text-xs text-muted-foreground">
                        Reportó: {incident.reporter.name} (
                        {roleLabels[incident.reporter.role]})
                    </span>
                )}
            </div>
            <p className="mt-3 rounded-lg bg-background/50 p-3 text-sm text-foreground">
                {incident.message}
            </p>
            {incident.latitude !== null && incident.longitude !== null && (
                <a
                    href={`https://www.openstreetmap.org/?mlat=${incident.latitude}&mlon=${incident.longitude}#map=17/${incident.latitude}/${incident.longitude}`}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-2 inline-block text-xs font-semibold text-primary hover:underline"
                >
                    Ver ubicación en el mapa
                </a>
            )}
            <div className="mt-4 flex flex-wrap gap-2">
                {incident.route && (
                    <button
                        type="button"
                        disabled={incident.students_notified_at !== null}
                        onClick={() =>
                            router.post(
                                `/admin/incidents/${incident.id}/notify`,
                                {},
                                { preserveScroll: true },
                            )
                        }
                        className="flex items-center gap-2 rounded-md border border-border bg-secondary px-3 py-2 text-xs font-semibold text-foreground hover:bg-secondary/70 disabled:opacity-60"
                    >
                        <Megaphone className="size-4" />
                        {incident.students_notified_at
                            ? 'Estudiantes avisados'
                            : 'Avisar a estudiantes'}
                    </button>
                )}
                <button
                    type="button"
                    onClick={() =>
                        router.patch(
                            `/admin/incidents/${incident.id}/resolve`,
                            {},
                            { preserveScroll: true },
                        )
                    }
                    className="ml-auto flex items-center gap-2 rounded-md px-3 py-2 text-xs font-semibold text-live hover:bg-live/10"
                >
                    <CircleCheck className="size-4" />
                    Marcar resuelta
                </button>
            </div>
        </article>
    );
}

export default function AdminIncidents({
    open,
    resolved,
    routes,
    vehicles,
    types,
    severities,
}: Props) {
    const form = useForm({
        type: types[0]?.value ?? 'traffic',
        severity: 'medium',
        route_id: routes[0]?.id ?? '',
        vehicle_id: '' as number | '',
        message: '',
    });
    const critical = open.filter(
        (incident) => incident.severity === 'critical',
    ).length;
    const resolvedToday = resolved.filter(
        (incident) =>
            incident.resolved_at &&
            new Date(incident.resolved_at).toDateString() ===
                new Date().toDateString(),
    ).length;

    return (
        <main className="flex-1 space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <Head title="Incidencias" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="text-[11px] font-semibold tracking-widest text-gold uppercase">
                        Panel de administración
                    </p>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
                        Incidencias
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Tráfico, desvíos, fallas y emergencias reportadas en
                        ruta.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {critical > 0 && (
                        <span className="flex items-center gap-2 rounded-full bg-destructive/15 px-3 py-1.5 text-xs font-semibold text-destructive">
                            <span className="size-1.5 rounded-full bg-destructive motion-safe:animate-pulse" />
                            {critical} {critical === 1 ? 'crítica' : 'críticas'}
                        </span>
                    )}
                    <span className="flex items-center gap-2 rounded-full bg-warning/15 px-3 py-1.5 text-xs font-semibold text-warning">
                        {open.length}{' '}
                        {open.length === 1 ? 'abierta' : 'abiertas'}
                    </span>
                    <span className="flex items-center gap-2 rounded-full bg-live/15 px-3 py-1.5 text-xs font-semibold text-live">
                        {resolvedToday}{' '}
                        {resolvedToday === 1 ? 'resuelta hoy' : 'resueltas hoy'}
                    </span>
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-12">
                <section
                    className="min-w-0 space-y-3 xl:col-span-8"
                    aria-label="Incidencias abiertas"
                >
                    {open.length === 0 ? (
                        <p className="flex items-center gap-2 rounded-2xl border border-border bg-card p-6 text-sm text-muted-foreground">
                            <CircleCheck className="size-5 text-live" />
                            No hay incidencias abiertas.
                        </p>
                    ) : (
                        open.map((incident) => (
                            <IncidentCard
                                key={incident.id}
                                incident={incident}
                            />
                        ))
                    )}

                    {resolved.length > 0 && (
                        <div className="rounded-2xl border border-border bg-card p-5">
                            <h2 className="text-lg font-bold">Resueltas</h2>
                            <ul className="mt-3 divide-y divide-border">
                                {resolved.map((incident) => (
                                    <li
                                        key={incident.id}
                                        className="flex items-start justify-between gap-3 py-3 text-sm"
                                    >
                                        <span className="min-w-0">
                                            <span className="font-semibold text-foreground">
                                                {incident.type_label}
                                                {incident.route &&
                                                    ` · Ruta ${incident.route.number}`}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {incident.message}
                                            </span>
                                        </span>
                                        <span className="shrink-0 text-xs text-placeholder">
                                            {incident.resolved_at &&
                                                timeAgo(incident.resolved_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </section>

                <aside className="min-w-0 xl:col-span-4">
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/admin/incidents', {
                                preserveScroll: true,
                                onSuccess: () => form.reset('message'),
                            });
                        }}
                        className="space-y-4 rounded-2xl border border-border bg-card p-5"
                    >
                        <h2 className="text-lg font-bold">Nueva incidencia</h2>
                        <label className="block space-y-2">
                            <span className="text-sm font-semibold">Tipo</span>
                            <select
                                value={form.data.type}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                                className={fieldClass}
                            >
                                {types.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.type} />
                        </label>
                        <label className="block space-y-2">
                            <span className="text-sm font-semibold">
                                Severidad
                            </span>
                            <select
                                value={form.data.severity}
                                onChange={(event) =>
                                    form.setData('severity', event.target.value)
                                }
                                className={fieldClass}
                            >
                                {severities.map((severity) => (
                                    <option
                                        key={severity.value}
                                        value={severity.value}
                                    >
                                        {severity.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.severity} />
                        </label>
                        <label className="block space-y-2">
                            <span className="text-sm font-semibold">Ruta</span>
                            <select
                                value={form.data.route_id}
                                onChange={(event) =>
                                    form.setData(
                                        'route_id',
                                        Number(event.target.value),
                                    )
                                }
                                className={fieldClass}
                            >
                                {routes.map((route) => (
                                    <option key={route.id} value={route.id}>
                                        Ruta {route.number} · {route.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.route_id} />
                        </label>
                        <label className="block space-y-2">
                            <span className="text-sm font-semibold">
                                Unidad (opcional)
                            </span>
                            <select
                                value={form.data.vehicle_id}
                                onChange={(event) =>
                                    form.setData(
                                        'vehicle_id',
                                        event.target.value === ''
                                            ? ''
                                            : Number(event.target.value),
                                    )
                                }
                                className={fieldClass}
                            >
                                <option value="">Sin unidad</option>
                                {vehicles.map((vehicle) => (
                                    <option key={vehicle.id} value={vehicle.id}>
                                        {vehicle.code}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="block space-y-2">
                            <span className="text-sm font-semibold">
                                Descripción
                            </span>
                            <textarea
                                value={form.data.message}
                                onChange={(event) =>
                                    form.setData('message', event.target.value)
                                }
                                rows={3}
                                maxLength={500}
                                placeholder="Por ejemplo: tráfico denso en Blvd. Venustiano Carranza."
                                className={fieldClass}
                            />
                            <InputError message={form.errors.message} />
                        </label>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="flex w-full items-center justify-center gap-2 rounded-md bg-primary py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary-hover disabled:opacity-60"
                        >
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : (
                                <Plus className="size-4" />
                            )}
                            Registrar incidencia
                        </button>
                    </form>
                </aside>
            </div>
        </main>
    );
}
