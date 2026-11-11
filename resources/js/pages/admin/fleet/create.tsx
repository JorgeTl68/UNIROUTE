import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BusFront, Check, Save } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { VehicleAmenity, VehicleStatus } from '@/types/transit';

type Props = {
    statuses: { value: VehicleStatus; label: string }[];
    amenities: { value: VehicleAmenity; label: string }[];
};
type VehicleForm = {
    code: string;
    plate: string;
    model: string;
    capacity: string;
    status: VehicleStatus;
    amenities: VehicleAmenity[];
};

export default function CreateVehicle({ statuses, amenities }: Props) {
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
        <>
            <Head title="Registrar unidad" />
            <main className="min-h-full bg-background p-4 sm:p-8">
                <div className="mx-auto max-w-4xl space-y-6">
                    <Link
                        href="/admin/fleet"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary"
                    >
                        <ArrowLeft className="size-4" />
                        Volver a la flota
                    </Link>
                    <header>
                        <p className="mb-2 text-xs font-medium tracking-widest text-primary uppercase">
                            UniRoute · Administración
                        </p>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Registrar unidad
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Agrega un vehículo al inventario de transporte
                            universitario.
                        </p>
                    </header>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/admin/fleet');
                        }}
                        className="overflow-hidden rounded-xl border border-border bg-card"
                    >
                        <div className="flex items-center gap-3 border-b border-border px-6 py-5">
                            <span className="rounded-lg bg-primary/10 p-2 text-primary">
                                <BusFront className="size-5" />
                            </span>
                            <div>
                                <h2 className="font-semibold">
                                    Datos de la unidad
                                </h2>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Los campos con * son obligatorios.
                                </p>
                            </div>
                        </div>
                        <fieldset
                            disabled={form.processing}
                            className="space-y-7 p-6 disabled:opacity-70 sm:p-8"
                        >
                            <legend className="sr-only">
                                Información del vehículo
                            </legend>
                            <div className="grid gap-6 sm:grid-cols-2">
                                {field(
                                    'code',
                                    'Código de unidad',
                                    'Ej. BUS-UTC-21',
                                )}
                                {field('plate', 'Placas', 'Ej. ABC-123-A')}
                                {field(
                                    'model',
                                    'Modelo',
                                    'Ej. Mercedes Sprinter',
                                )}
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
                                <p className="mb-4 text-xs text-muted-foreground">
                                    Selecciona el equipamiento disponible. Es
                                    opcional.
                                </p>
                                <div className="grid gap-3 sm:grid-cols-3">
                                    {amenities.map((amenity) => (
                                        <label
                                            key={amenity.value}
                                            className={`flex cursor-pointer items-center gap-3 rounded-lg border p-4 text-sm transition-colors ${form.data.amenities.includes(amenity.value) ? 'border-primary bg-primary/10' : 'border-border bg-background hover:border-placeholder'}`}
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
                                    .filter(([key]) =>
                                        key.startsWith('amenities.'),
                                    )
                                    .map(([key, message]) => (
                                        <InputError
                                            key={key}
                                            message={message}
                                        />
                                    ))}
                            </fieldset>
                        </fieldset>
                        <footer className="flex flex-wrap items-center justify-between gap-4 border-t border-border bg-background/40 p-6">
                            <p className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Check className="size-4 text-live" />
                                Código y placas deben ser únicos.
                            </p>
                            <div className="flex gap-3">
                                <Button asChild variant="outline">
                                    <Link href="/admin/fleet">Cancelar</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    <Save className="size-4" />
                                    {form.processing
                                        ? 'Guardando…'
                                        : 'Guardar unidad'}
                                </Button>
                            </div>
                        </footer>
                    </form>
                </div>
            </main>
        </>
    );
}
CreateVehicle.layout = {
    breadcrumbs: [
        { title: 'Administración de flota', href: '/admin/fleet' },
        { title: 'Registrar unidad', href: '/admin/fleet/create' },
    ],
};
