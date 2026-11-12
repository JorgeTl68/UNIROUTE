import { useForm } from '@inertiajs/react';
import { Check, Save } from 'lucide-react';
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
import type { VehicleAmenity, VehicleStatus } from '@/types/transit';

type VehicleForm = {
    code: string;
    plate: string;
    model: string;
    capacity: string;
    status: VehicleStatus;
    amenities: VehicleAmenity[];
};

/** Registrar unidad (S2-04) en un modal desde la consola de flota. */
export function VehicleFormDialog({
    statuses,
    amenities,
    children,
}: {
    statuses: { value: VehicleStatus; label: string }[];
    amenities: { value: VehicleAmenity; label: string }[];
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm<VehicleForm>({
        code: '',
        plate: '',
        model: '',
        capacity: '',
        status: 'standby',
        amenities: [],
    });
    const field = (
        name: 'code' | 'plate' | 'model' | 'capacity',
        label: string,
        placeholder: string,
    ) => (
        <div className="grid gap-2">
            <Label htmlFor={name}>
                {label}{' '}
                <span aria-hidden="true" className="text-primary">
                    *
                </span>
            </Label>
            <Input
                id={name}
                name={name}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                required
                maxLength={name === 'capacity' ? undefined : 255}
                type={name === 'capacity' ? 'number' : 'text'}
                min={name === 'capacity' ? 1 : undefined}
                max={name === 'capacity' ? 65535 : undefined}
                step={name === 'capacity' ? 1 : undefined}
                autoFocus={name === 'code'}
                placeholder={placeholder}
                aria-invalid={Boolean(form.errors[name])}
                aria-describedby={
                    form.errors[name] ? `${name}-error` : undefined
                }
                className="h-11"
            />
            <InputError id={`${name}-error`} message={form.errors[name]} />
        </div>
    );

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
                    <DialogTitle>Registrar unidad</DialogTitle>
                    <DialogDescription>
                        Agrega un vehículo al inventario de transporte
                        universitario. Los campos con * son obligatorios.
                    </DialogDescription>
                </DialogHeader>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/admin/fleet', {
                            preserveScroll: true,
                            onSuccess: () => setOpen(false),
                        });
                    }}
                >
                    <fieldset
                        disabled={form.processing}
                        className="space-y-6 disabled:opacity-70"
                    >
                        <legend className="sr-only">
                            Información del vehículo
                        </legend>
                        <div className="grid gap-5 sm:grid-cols-2">
                            {field(
                                'code',
                                'Código de unidad',
                                'Ej. BUS-UTC-21',
                            )}
                            {field('plate', 'Placas', 'Ej. ABC-123-A')}
                            {field('model', 'Modelo', 'Ej. Mercedes Sprinter')}
                            {field(
                                'capacity',
                                'Capacidad de pasajeros',
                                'Ej. 40',
                            )}
                        </div>
                        <div className="grid max-w-sm gap-2">
                            <Label htmlFor="status">Estado inicial *</Label>
                            <select
                                id="status"
                                name="status"
                                value={form.data.status}
                                onChange={(event) =>
                                    form.setData(
                                        'status',
                                        event.target.value as VehicleStatus,
                                    )
                                }
                                required
                                aria-invalid={Boolean(form.errors.status)}
                                aria-describedby={
                                    form.errors.status
                                        ? 'status-error'
                                        : undefined
                                }
                                className="h-11 rounded-lg border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                            >
                                {statuses.map((status) => (
                                    <option
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                id="status-error"
                                message={form.errors.status}
                            />
                        </div>
                        <fieldset>
                            <legend className="mb-1 text-sm font-medium">
                                Amenidades
                            </legend>
                            <p className="mb-3 text-xs text-muted-foreground">
                                Selecciona el equipamiento disponible. Es
                                opcional.
                            </p>
                            <div className="grid gap-3 sm:grid-cols-3">
                                {amenities.map((amenity) => (
                                    <label
                                        key={amenity.value}
                                        className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition-colors ${form.data.amenities.includes(amenity.value) ? 'border-primary bg-primary/10' : 'border-border bg-background hover:border-placeholder'}`}
                                    >
                                        <input
                                            type="checkbox"
                                            name="amenities[]"
                                            value={amenity.value}
                                            checked={form.data.amenities.includes(
                                                amenity.value,
                                            )}
                                            onChange={(event) =>
                                                form.setData(
                                                    'amenities',
                                                    event.target.checked
                                                        ? [
                                                              ...form.data
                                                                  .amenities,
                                                              amenity.value,
                                                          ]
                                                        : form.data.amenities.filter(
                                                              (value) =>
                                                                  value !==
                                                                  amenity.value,
                                                          ),
                                                )
                                            }
                                            className="size-4 accent-[#FDDEB1]"
                                        />
                                        {amenity.label}
                                    </label>
                                ))}
                            </div>
                            <InputError
                                className="mt-2"
                                message={form.errors.amenities}
                            />
                            {Object.entries(form.errors)
                                .filter(([key]) => key.startsWith('amenities.'))
                                .map(([key, message]) => (
                                    <InputError key={key} message={message} />
                                ))}
                        </fieldset>
                    </fieldset>
                    <DialogFooter className="mt-6 items-center sm:justify-between">
                        <p className="flex items-center gap-2 text-xs text-muted-foreground">
                            <Check className="size-4 text-live" />
                            Código y placas deben ser únicos.
                        </p>
                        <div className="flex gap-3">
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancelar
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={form.processing}>
                                <Save className="size-4" />
                                {form.processing
                                    ? 'Guardando…'
                                    : 'Guardar unidad'}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
