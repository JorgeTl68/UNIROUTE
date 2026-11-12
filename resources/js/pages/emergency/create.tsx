import { Head, useForm } from '@inertiajs/react';
import {
    CarFront,
    CircleHelp,
    HeartPulse,
    LoaderCircle,
    LocateFixed,
    Phone,
    PhoneCall,
    Send,
    ShieldAlert,
    Siren,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { StatusChip } from '@/components/transit/status-chip';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/transit-format';
import type { Incident, TransitRoute } from '@/types/transit';

type Props = {
    routes: Pick<TransitRoute, 'id' | 'number' | 'name' | 'color'>[];
    selected_route_id: number | null;
    categories: { value: string; label: string }[];
    reports: Incident[];
};

const categoryIcons: Record<string, { icon: typeof Siren; className: string }> =
    {
        accident: { icon: CarFront, className: 'text-destructive' },
        breakdown: { icon: Wrench, className: 'text-warning' },
        safety: { icon: ShieldAlert, className: 'text-destructive' },
        health: { icon: HeartPulse, className: 'text-destructive' },
        other: { icon: CircleHelp, className: 'text-muted-foreground' },
    };

const fieldClass =
    'w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50';

const steps = [
    'El área de transporte recibe tu reporte al instante.',
    'Si eliges una ruta, el aviso aparece en el detalle de esa ruta.',
    'Puedes ver el estado de tu reporte en esta misma página.',
];

export default function EmergencyCreate({
    routes,
    selected_route_id,
    categories,
    reports,
}: Props) {
    const form = useForm<{
        category: string;
        route_id: number | '';
        message: string;
        latitude: number | null;
        longitude: number | null;
    }>({
        category: categories[0]?.value ?? 'other',
        route_id: selected_route_id ?? '',
        message: '',
        latitude: null,
        longitude: null,
    });
    const [locating, setLocating] = useState(false);
    const [locationError, setLocationError] = useState<string | null>(null);

    const shareLocation = () => {
        if (!navigator.geolocation) {
            setLocationError('Tu navegador no permite compartir la ubicación.');

            return;
        }

        setLocating(true);
        setLocationError(null);
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                form.setData((data) => ({
                    ...data,
                    latitude: coords.latitude,
                    longitude: coords.longitude,
                }));
                setLocating(false);
            },
            () => {
                setLocationError(
                    'No pudimos obtener tu ubicación. Describe dónde estás en el mensaje.',
                );
                setLocating(false);
            },
            { enableHighAccuracy: true, timeout: 10_000 },
        );
    };

    return (
        <main className="flex-1 space-y-6 px-4 py-6 pb-24 sm:px-6 md:pb-6 lg:px-8">
            <Head title="Reportar emergencia" />

            <div>
                <p className="flex items-center gap-2 text-[11px] font-semibold tracking-widest text-destructive uppercase">
                    <Siren className="size-4" />
                    Soporte en Ruta
                </p>
                <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
                    Reportar emergencia
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    El área de transporte recibe tu reporte al momento.
                </p>
            </div>

            <div className="flex flex-col gap-4 rounded-2xl border border-destructive/40 bg-destructive/10 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-start gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-destructive text-destructive-foreground">
                        <PhoneCall className="size-5" />
                    </span>
                    <div>
                        <p className="text-sm font-bold text-white">
                            Si hay riesgo para tu vida o la de alguien más,
                            llama al 911
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Después repórtalo aquí para que transporte lo
                            atienda.
                        </p>
                    </div>
                </div>
                <a
                    href="tel:911"
                    className="flex shrink-0 items-center justify-center gap-2 rounded-md bg-destructive px-5 py-2.5 text-sm font-bold text-destructive-foreground"
                >
                    <Phone className="size-4" />
                    Llamar al 911
                </a>
            </div>

            <div className="grid gap-6 xl:grid-cols-12">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/emergency');
                    }}
                    className="min-w-0 space-y-5 rounded-2xl border border-border bg-card p-5 sm:p-6 xl:col-span-8"
                    noValidate
                >
                    <fieldset>
                        <legend className="text-sm font-semibold text-white">
                            ¿Qué pasó?
                        </legend>
                        <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            {categories.map((category) => {
                                const { icon: Icon, className } =
                                    categoryIcons[category.value] ??
                                    categoryIcons.other;

                                return (
                                    <label
                                        key={category.value}
                                        className="cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            name="category"
                                            value={category.value}
                                            checked={
                                                form.data.category ===
                                                category.value
                                            }
                                            onChange={() =>
                                                form.setData(
                                                    'category',
                                                    category.value,
                                                )
                                            }
                                            className="peer sr-only"
                                        />
                                        <span className="flex h-full flex-col gap-2 rounded-xl border border-border bg-background/50 p-3 text-sm font-semibold text-foreground peer-checked:border-primary peer-checked:bg-primary/10 peer-focus-visible:ring-2 peer-focus-visible:ring-ring">
                                            <Icon
                                                className={cn(
                                                    'size-5',
                                                    className,
                                                )}
                                            />
                                            {category.label}
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                        <InputError
                            className="mt-2"
                            message={form.errors.category}
                        />
                    </fieldset>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="block space-y-2">
                            <span className="text-sm font-semibold text-foreground">
                                Ruta
                            </span>
                            <select
                                value={form.data.route_id}
                                onChange={(event) =>
                                    form.setData(
                                        'route_id',
                                        event.target.value === ''
                                            ? ''
                                            : Number(event.target.value),
                                    )
                                }
                                className={fieldClass}
                            >
                                <option value="">No estoy en una ruta</option>
                                {routes.map((route) => (
                                    <option key={route.id} value={route.id}>
                                        Ruta {route.number} · {route.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.route_id} />
                        </label>
                        <div className="space-y-2">
                            <span className="text-sm font-semibold text-foreground">
                                Ubicación
                            </span>
                            <button
                                type="button"
                                onClick={shareLocation}
                                disabled={locating}
                                className="flex w-full items-center justify-center gap-2 rounded-md border border-border bg-secondary py-2.5 text-sm font-semibold text-foreground hover:bg-secondary/70"
                            >
                                {locating ? (
                                    <LoaderCircle className="size-4 animate-spin" />
                                ) : (
                                    <LocateFixed className="size-4" />
                                )}
                                {form.data.latitude !== null
                                    ? 'Ubicación agregada'
                                    : 'Compartir mi ubicación'}
                            </button>
                            {locationError && (
                                <p className="text-xs text-warning">
                                    {locationError}
                                </p>
                            )}
                        </div>
                    </div>

                    <label className="block space-y-2">
                        <span className="text-sm font-semibold text-foreground">
                            Cuéntanos qué está pasando
                        </span>
                        <textarea
                            value={form.data.message}
                            onChange={(event) =>
                                form.setData('message', event.target.value)
                            }
                            rows={4}
                            maxLength={1000}
                            placeholder="Por ejemplo: la unidad BUS-UTC-01 se detuvo en Blvd. Fundadores y sale humo del motor."
                            className={fieldClass}
                            aria-invalid={Boolean(form.errors.message)}
                        />
                        <InputError message={form.errors.message} />
                    </label>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="flex w-full items-center justify-center gap-2 rounded-md bg-destructive py-3 text-sm font-bold text-destructive-foreground shadow-lg shadow-black/30 active:scale-[0.98] disabled:opacity-60 sm:w-auto sm:px-8"
                    >
                        {form.processing ? (
                            <LoaderCircle className="size-4 animate-spin" />
                        ) : (
                            <Send className="size-4" />
                        )}
                        {form.processing ? 'Enviando…' : 'Enviar reporte'}
                    </button>
                </form>

                <aside className="min-w-0 space-y-4 xl:col-span-4">
                    <div className="rounded-2xl border border-border bg-card p-5">
                        <h2 className="text-lg font-bold">Qué pasa después</h2>
                        <ol className="mt-3 space-y-3">
                            {steps.map((step, index) => (
                                <li
                                    key={step}
                                    className="flex gap-3 text-sm text-muted-foreground"
                                >
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary">
                                        {index + 1}
                                    </span>
                                    {step}
                                </li>
                            ))}
                        </ol>
                    </div>

                    <div className="rounded-2xl border border-border bg-card p-5">
                        <h2 className="text-lg font-bold">Mis reportes</h2>
                        {reports.length === 0 ? (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Aún no has enviado reportes.
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-2">
                                {reports.map((report) => (
                                    <li
                                        key={report.id}
                                        className="rounded-lg bg-background/50 p-3"
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="text-xs text-placeholder">
                                                {timeAgo(report.created_at)}
                                                {report.route &&
                                                    ` · Ruta ${report.route.number}`}
                                            </span>
                                            <StatusChip
                                                tone={
                                                    report.resolved_at
                                                        ? 'live'
                                                        : 'warning'
                                                }
                                                label={
                                                    report.resolved_at
                                                        ? 'Atendido'
                                                        : 'En revisión'
                                                }
                                            />
                                        </div>
                                        <p className="mt-1 line-clamp-2 text-sm text-foreground">
                                            {report.message}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </aside>
            </div>
        </main>
    );
}
