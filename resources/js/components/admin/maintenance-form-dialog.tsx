import { useForm } from '@inertiajs/react';
import { AlertTriangle, Save } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export const severityLabels = {
    minor: 'Menor',
    major: 'Alta',
    critical: 'Crítica',
};

const selectClass =
    'h-11 rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring';

/**
 * Reportar falla e ingresar al taller (S4-03) en un modal. Al guardar se abre
 * el detalle del reporte.
 */
export function MaintenanceFormDialog({
    vehicles,
    vehicleId,
    children,
}: {
    vehicles: { id: number; code: string; plate: string }[];
    vehicleId?: number;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        vehicle_id: vehicleId?.toString() ?? '',
        severity: 'major',
        workshop: '',
        description: '',
    });

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                setOpen(value);
                if (value) {
                    form.reset();
                    form.clearErrors();
                }
            }}
        >
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto bg-card sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        Reportar falla e ingresar al taller
                    </DialogTitle>
                    <DialogDescription>
                        La unidad quedará fuera de servicio hasta completar la
                        reparación.
                    </DialogDescription>
                </DialogHeader>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/admin/fleet/maintenance');
                    }}
                >
                    <fieldset
                        disabled={form.processing}
                        className="grid gap-5 sm:grid-cols-2"
                    >
                        <legend className="sr-only">Reporte de falla</legend>
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
                                className={selectClass}
                                aria-invalid={Boolean(form.errors.vehicle_id)}
                            >
                                <option value="">Selecciona una unidad</option>
                                {vehicles.map((vehicle) => (
                                    <option key={vehicle.id} value={vehicle.id}>
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
                                    form.setData('severity', event.target.value)
                                }
                                className={selectClass}
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
                                    form.setData('workshop', event.target.value)
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
                                className="min-h-28 rounded-lg border border-input bg-background p-3 text-sm placeholder:text-placeholder focus:ring-2 focus:ring-ring"
                                aria-invalid={Boolean(form.errors.description)}
                            />
                            <InputError message={form.errors.description} />
                        </div>
                        <p className="flex items-start gap-2 rounded-lg bg-primary/5 p-3 text-xs leading-relaxed text-muted-foreground sm:col-span-2">
                            <AlertTriangle className="size-4 shrink-0 text-primary" />
                            Si la unidad tiene un viaje activo, realiza un
                            relevo primero. Los viajes futuros no se cancelan:
                            revisa sus asignaciones después de registrar la
                            falla.
                        </p>
                    </fieldset>
                    <DialogFooter className="mt-6">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button disabled={form.processing} type="submit">
                            <Save />
                            {form.processing
                                ? 'Registrando…'
                                : 'Registrar e ingresar al taller'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
