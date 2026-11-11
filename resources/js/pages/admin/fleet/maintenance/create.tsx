import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, Save } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { severityLabels } from './index';
type Props = {
    vehicles: { id: number; code: string; plate: string; status: string }[];
    selectedVehicle: number | null;
};
export default function CreateMaintenance({
    vehicles,
    selectedVehicle,
}: Props) {
    const form = useForm({
        vehicle_id: selectedVehicle?.toString() ?? '',
        severity: 'major',
        workshop: '',
        description: '',
    });
    return (
        <>
            <Head title="Reportar falla" />
            <main className="min-h-full bg-background p-4 sm:p-8">
                <div className="mx-auto max-w-3xl space-y-6">
                    <Link
                        href="/admin/fleet/maintenance"
                        className="text-sm text-primary"
                    >
                        ← Volver a mantenimiento
                    </Link>
                    <header>
                        <p className="mb-2 text-xs tracking-widest text-primary uppercase">
                            UniRoute · Reporte técnico
                        </p>
                        <h1 className="text-3xl font-bold">
                            Reportar falla e ingresar al taller
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            La unidad quedará fuera de servicio hasta completar
                            la reparación.
                        </p>
                    </header>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/admin/fleet/maintenance');
                        }}
                        className="rounded-xl border border-border bg-card"
                    >
                        <fieldset
                            disabled={form.processing}
                            className="grid gap-6 p-6 sm:grid-cols-2"
                        >
                            <legend className="sr-only">
                                Reporte de falla
                            </legend>
                            <div className="grid gap-2">
                                <Label htmlFor="vehicle_id">Unidad *</Label>
                                <select
                                    id="vehicle_id"
                                    required
                                    autoFocus
                                    value={form.data.vehicle_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'vehicle_id',
                                            event.target.value,
                                        )
                                    }
                                    className="h-11 rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring"
                                    aria-invalid={Boolean(
                                        form.errors.vehicle_id,
                                    )}
                                >
                                    <option value="">
                                        Selecciona una unidad
                                    </option>
                                    {vehicles.map((vehicle) => (
                                        <option
                                            key={vehicle.id}
                                            value={vehicle.id}
                                        >
                                            {vehicle.code} · {vehicle.plate}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.vehicle_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="severity">Prioridad *</Label>
                                <select
                                    id="severity"
                                    required
                                    value={form.data.severity}
                                    onChange={(event) =>
                                        form.setData(
                                            'severity',
                                            event.target.value,
                                        )
                                    }
                                    className="h-11 rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring"
                                >
                                    {Object.entries(severityLabels).map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>
                                <InputError message={form.errors.severity} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="workshop">
                                    Taller o responsable técnico *
                                </Label>
                                <Input
                                    id="workshop"
                                    required
                                    maxLength={255}
                                    value={form.data.workshop}
                                    onChange={(event) =>
                                        form.setData(
                                            'workshop',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Ej. Taller de transporte del campus"
                                    aria-invalid={Boolean(form.errors.workshop)}
                                />
                                <InputError message={form.errors.workshop} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="description">
                                    Descripción de la falla *
                                </Label>
                                <textarea
                                    id="description"
                                    required
                                    minLength={10}
                                    maxLength={3000}
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Describe la falla y lo que debe revisar el taller"
                                    className="min-h-32 rounded-lg border border-input bg-background p-3 text-sm placeholder:text-placeholder focus:ring-2 focus:ring-ring"
                                    aria-invalid={Boolean(
                                        form.errors.description,
                                    )}
                                />
                                <InputError message={form.errors.description} />
                            </div>
                            <p className="flex items-start gap-2 rounded-lg bg-primary/5 p-3 text-xs leading-relaxed text-muted-foreground sm:col-span-2">
                                <AlertTriangle className="size-4 shrink-0 text-primary" />
                                Si la unidad tiene un viaje activo, realiza un
                                relevo primero. Los viajes futuros no se
                                cancelan: revisa sus asignaciones después de
                                registrar la falla.
                            </p>
                        </fieldset>
                        <footer className="flex justify-end gap-3 border-t border-border p-6">
                            <Button variant="outline" asChild>
                                <Link href="/admin/fleet/maintenance">
                                    Cancelar
                                </Link>
                            </Button>
                            <Button disabled={form.processing} type="submit">
                                <Save />
                                {form.processing
                                    ? 'Registrando…'
                                    : 'Registrar e ingresar al taller'}
                            </Button>
                        </footer>
                    </form>
                </div>
            </main>
        </>
    );
}
CreateMaintenance.layout = {
    breadcrumbs: [
        { title: 'Mantenimiento', href: '/admin/fleet/maintenance' },
        { title: 'Reportar falla', href: '/admin/fleet/maintenance/create' },
    ],
};
