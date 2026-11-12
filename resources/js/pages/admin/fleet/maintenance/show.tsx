import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Wrench } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { severityLabels } from '@/components/admin/maintenance-form-dialog';
import { recordDate } from './index';
import type { MaintenanceRecord } from './index';
type Props = {
    record: MaintenanceRecord & {
        model: string;
        reporter: string | null;
        closer: string | null;
    };
    scheduledTrips: number;
};
export default function MaintenanceDetail({ record, scheduledTrips }: Props) {
    const form = useForm({ resolution: '', cost: '' });
    return (
        <>
            <Head title={`Mantenimiento · ${record.code}`} />
            <main className="min-h-full bg-background p-4 sm:p-8">
                <div className="mx-auto max-w-4xl space-y-6">
                    <Link
                        href="/admin/fleet/maintenance"
                        className="text-sm text-primary"
                    >
                        ← Historial de mantenimiento
                    </Link>
                    <header className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="mb-2 text-xs tracking-widest text-primary uppercase">
                                UniRoute · Reporte #{record.id}
                            </p>
                            <h1 className="text-3xl font-bold">
                                Mantenimiento de {record.code}
                            </h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {record.model} · {record.plate}
                            </p>
                        </div>
                        <span
                            className={`rounded-full px-4 py-2 text-xs ${record.completed_at ? 'bg-live/10 text-live' : 'bg-primary/10 text-primary'}`}
                        >
                            {record.completed_at
                                ? 'Reparación completada'
                                : 'Unidad en taller'}
                        </span>
                    </header>
                    {!record.completed_at && scheduledTrips > 0 && (
                        <aside className="rounded-xl border border-primary/40 bg-primary/5 p-5">
                            <p className="text-sm font-semibold text-primary">
                                Esta unidad tiene {scheduledTrips} salidas
                                futuras asignadas.
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Revisa las salidas y reasígnalas si la
                                reparación no estará lista.
                            </p>
                            <Link
                                href="/admin/fleet/assignments"
                                className="mt-3 inline-block text-xs text-primary underline"
                            >
                                Revisar asignaciones
                            </Link>
                        </aside>
                    )}
                    <section className="rounded-xl border border-border bg-card p-6">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Wrench className="size-5 text-primary" />
                            Reporte de falla
                        </h2>
                        <dl className="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                            {[
                                {
                                    label: 'Prioridad',
                                    value: severityLabels[record.severity],
                                },
                                {
                                    label: 'Taller / responsable técnico',
                                    value: record.workshop,
                                },
                                {
                                    label: 'Fecha de ingreso',
                                    value: recordDate(record.created_at),
                                },
                                {
                                    label: 'Registrado por',
                                    value:
                                        record.reporter ??
                                        'Usuario no disponible',
                                },
                            ].map(({ label, value }) => (
                                <div key={label}>
                                    <dt className="text-xs text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="mt-1">{value}</dd>
                                </div>
                            ))}
                        </dl>
                        <p className="mt-6 text-xs text-muted-foreground">
                            Falla reportada
                        </p>
                        <p className="mt-2 text-sm leading-relaxed whitespace-pre-wrap">
                            {record.description}
                        </p>
                        <Link
                            href={`/admin/fleet/maintenance?vehicle_id=${record.vehicle_id}`}
                            className="mt-5 inline-block text-xs text-primary underline"
                        >
                            Ver todos los reportes de esta unidad
                        </Link>
                    </section>
                    {record.completed_at ? (
                        <section className="rounded-xl border border-live/30 bg-card p-6">
                            <h2 className="flex items-center gap-2 text-lg font-semibold">
                                <CheckCircle2 className="size-5 text-live" />
                                Reparación y reincorporación
                            </h2>
                            <p className="mt-4 text-sm leading-relaxed whitespace-pre-wrap">
                                {record.resolution}
                            </p>
                            <dl className="mt-5 grid gap-5 text-sm sm:grid-cols-3">
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Cierre
                                    </dt>
                                    <dd className="mt-1">
                                        {recordDate(record.completed_at)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Autorizado por
                                    </dt>
                                    <dd className="mt-1">
                                        {record.closer ??
                                            'Usuario no disponible'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Costo registrado (MXN)
                                    </dt>
                                    <dd className="mt-1">
                                        {record.cost === null
                                            ? 'No registrado'
                                            : Number(
                                                  record.cost,
                                              ).toLocaleString('es-MX', {
                                                  style: 'currency',
                                                  currency: 'MXN',
                                              })}
                                    </dd>
                                </div>
                            </dl>
                            <p className="mt-5 text-xs text-muted-foreground">
                                Al cerrar este reporte, la unidad se reincorporó
                                en estado “En espera”. Este registro es parte de
                                su historial y no se modifica.
                            </p>
                        </section>
                    ) : (
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.patch(
                                    `/admin/fleet/maintenance/${record.id}/complete`,
                                    {
                                        preserveScroll: true,
                                    },
                                );
                            }}
                            className="rounded-xl border border-border bg-card"
                        >
                            <header className="border-b border-border p-6">
                                <h2 className="text-lg font-semibold">
                                    Completar reparación y reincorporar
                                </h2>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Confirma los trabajos realizados. La unidad
                                    quedará en espera, disponible para
                                    asignación.
                                </p>
                            </header>
                            <fieldset
                                disabled={form.processing}
                                className="space-y-5 p-6"
                            >
                                <legend className="sr-only">
                                    Resultado del mantenimiento
                                </legend>
                                <div className="grid gap-2">
                                    <Label htmlFor="resolution">
                                        Reparación realizada *
                                    </Label>
                                    <textarea
                                        id="resolution"
                                        required
                                        minLength={10}
                                        maxLength={3000}
                                        value={form.data.resolution}
                                        onChange={(event) =>
                                            form.setData(
                                                'resolution',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Describe la reparación y las verificaciones realizadas"
                                        className="min-h-32 rounded-lg border border-input bg-background p-3 text-sm placeholder:text-placeholder focus:ring-2 focus:ring-ring"
                                        aria-invalid={Boolean(
                                            form.errors.resolution,
                                        )}
                                    />
                                    <InputError
                                        message={form.errors.resolution}
                                    />
                                </div>
                                <div className="grid max-w-xs gap-2">
                                    <Label htmlFor="cost">
                                        Costo en MXN (opcional)
                                    </Label>
                                    <Input
                                        id="cost"
                                        type="number"
                                        min={0}
                                        max={9999999999.99}
                                        step="0.01"
                                        value={form.data.cost}
                                        onChange={(event) =>
                                            form.setData(
                                                'cost',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Ej. 1500.00"
                                        aria-invalid={Boolean(form.errors.cost)}
                                    />
                                    <InputError message={form.errors.cost} />
                                </div>
                            </fieldset>
                            <footer className="flex justify-end border-t border-border p-6">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    <CheckCircle2 />
                                    {form.processing
                                        ? 'Guardando…'
                                        : 'Confirmar reparación y reincorporar'}
                                </Button>
                            </footer>
                        </form>
                    )}
                </div>
            </main>
        </>
    );
}
