import { Head, Link, useForm } from '@inertiajs/react';
import { History, Save } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { AssignmentTrip } from './index';

type Change = {
    id: number;
    created_at: string;
    reason: string;
    actor: string | null;
    previous_driver: string | null;
    driver: string | null;
    previous_vehicle: string | null;
    vehicle: string | null;
    previous_route: string | null;
    route: string | null;
};
type Props = {
    trip: AssignmentTrip;
    drivers: { id: number; name: string }[];
    vehicles: { id: number; code: string; capacity: number }[];
    routes: { id: number; name: string; number: number }[];
    history: Change[];
};
export default function EditAssignment({
    trip,
    drivers,
    vehicles,
    routes,
    history,
}: Props) {
    const form = useForm({
        driver_id: trip.driver_id?.toString() ?? '',
        vehicle_id: trip.vehicle_id?.toString() ?? '',
        route_id: trip.route_id.toString(),
        reason: '',
    });
    const routeLocked =
        trip.status === 'in_progress' || trip.schedule_id !== null;
    const currentRoute =
        trip.route && !routes.some((route) => route.id === trip.route_id)
            ? [{ id: trip.route_id, ...trip.route }, ...routes]
            : routes;
    const selectClass =
        'h-11 w-full rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring disabled:opacity-60';
    return (
        <>
            <Head title="Asignar viaje" />
            <main className="min-h-full bg-background p-4 sm:p-8">
                <div className="mx-auto max-w-4xl space-y-6">
                    <Link
                        href="/admin/fleet/assignments"
                        className="text-sm text-primary"
                    >
                        ← Volver a asignaciones
                    </Link>
                    <header>
                        <p className="mb-2 text-xs tracking-widest text-primary uppercase">
                            UniRoute · Viaje #{trip.id}
                        </p>
                        <h1 className="text-3xl font-bold">
                            {trip.status === 'in_progress'
                                ? 'Relevo de conductor o unidad'
                                : 'Asignar conductor, unidad y ruta'}
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {trip.route?.name} ·{' '}
                            {new Date(trip.scheduled_at).toLocaleString(
                                'es-MX',
                                {
                                    timeZone: 'America/Mexico_City',
                                },
                            )}{' '}
                            ·{' '}
                            {trip.status === 'in_progress'
                                ? 'En curso'
                                : 'Programado'}
                        </p>
                    </header>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.patch(`/admin/fleet/assignments/${trip.id}`, {
                                preserveScroll: true,
                                onSuccess: () => form.reset('reason'),
                            });
                        }}
                        className="rounded-xl border border-border bg-card"
                    >
                        <fieldset
                            disabled={form.processing}
                            className="grid gap-6 p-6 sm:grid-cols-2"
                        >
                            <legend className="sr-only">
                                Asignación del viaje
                            </legend>
                            <div className="grid gap-2">
                                <Label htmlFor="driver_id">Conductor *</Label>
                                <select
                                    id="driver_id"
                                    required
                                    value={form.data.driver_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'driver_id',
                                            event.target.value,
                                        )
                                    }
                                    className={selectClass}
                                    aria-invalid={Boolean(
                                        form.errors.driver_id,
                                    )}
                                >
                                    <option value="">
                                        Selecciona un conductor
                                    </option>
                                    {drivers.map((driver) => (
                                        <option
                                            key={driver.id}
                                            value={driver.id}
                                        >
                                            {driver.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.driver_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="vehicle_id">Unidad *</Label>
                                <select
                                    id="vehicle_id"
                                    required
                                    value={form.data.vehicle_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'vehicle_id',
                                            event.target.value,
                                        )
                                    }
                                    className={selectClass}
                                    aria-invalid={Boolean(
                                        form.errors.vehicle_id,
                                    )}
                                >
                                    <option value="">
                                        Selecciona una unidad
                                    </option>
                                    {trip.vehicle_id &&
                                        !vehicles.some(
                                            (vehicle) =>
                                                vehicle.id === trip.vehicle_id,
                                        ) && (
                                            <option
                                                value={trip.vehicle_id}
                                                disabled
                                            >
                                                {trip.vehicle?.code} · No
                                                disponible
                                            </option>
                                        )}
                                    {vehicles.map((vehicle) => (
                                        <option
                                            key={vehicle.id}
                                            value={vehicle.id}
                                        >
                                            {vehicle.code} · {vehicle.capacity}{' '}
                                            plazas
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.vehicle_id} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="route_id">Ruta *</Label>
                                <select
                                    id="route_id"
                                    required
                                    disabled={routeLocked}
                                    value={form.data.route_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'route_id',
                                            event.target.value,
                                        )
                                    }
                                    className={selectClass}
                                    aria-invalid={Boolean(form.errors.route_id)}
                                >
                                    <option value="">
                                        Selecciona una ruta
                                    </option>
                                    {currentRoute.map((route) => (
                                        <option key={route.id} value={route.id}>
                                            Ruta {route.number} · {route.name}
                                        </option>
                                    ))}
                                </select>
                                <p className="text-xs text-muted-foreground">
                                    {routeLocked
                                        ? 'La ruta se conserva en viajes en curso o vinculados a un horario.'
                                        : 'La ruta puede cambiarse mientras el viaje no tenga un horario vinculado.'}
                                </p>
                                <InputError message={form.errors.route_id} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="reason">
                                    Motivo de asignación o relevo *
                                </Label>
                                <textarea
                                    id="reason"
                                    required
                                    maxLength={500}
                                    value={form.data.reason}
                                    onChange={(event) =>
                                        form.setData(
                                            'reason',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Ej. Relevo de turno o sustitución de unidad"
                                    className="min-h-24 rounded-lg border border-input bg-background p-3 text-sm placeholder:text-placeholder focus:ring-2 focus:ring-ring"
                                    aria-invalid={Boolean(form.errors.reason)}
                                />
                                <InputError message={form.errors.reason} />
                            </div>
                            <p className="text-xs leading-relaxed text-muted-foreground sm:col-span-2">
                                No se permiten unidades en mantenimiento,
                                capacidad menor a los {trip.occupancy} pasajeros
                                registrados ni recursos con otro viaje activo o
                                una salida a la misma hora. Horarios distintos
                                requieren revisar manualmente el tiempo entre
                                recorridos.
                            </p>
                        </fieldset>
                        <footer className="flex justify-end gap-3 border-t border-border p-6">
                            <Button variant="outline" asChild>
                                <Link href="/admin/fleet/assignments">
                                    Cancelar
                                </Link>
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                {form.processing
                                    ? 'Guardando…'
                                    : 'Guardar asignación'}
                            </Button>
                        </footer>
                    </form>
                    <section className="rounded-xl border border-border bg-card p-6">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <History className="size-5 text-primary" />
                            Historial de asignaciones
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Últimos 20 cambios registrados en este viaje.
                        </p>
                        <ol className="mt-5 space-y-4">
                            {history.map((change) => (
                                <li
                                    key={change.id}
                                    className="rounded-lg border border-border bg-background p-4"
                                >
                                    <div className="flex flex-wrap justify-between gap-2 text-xs">
                                        <span className="font-semibold text-primary">
                                            {change.actor ??
                                                'Administrador eliminado'}
                                        </span>
                                        <time className="text-muted-foreground">
                                            {new Date(
                                                change.created_at.includes('T')
                                                    ? change.created_at
                                                    : change.created_at.replace(
                                                          ' ',
                                                          'T',
                                                      ) + 'Z',
                                            ).toLocaleString('es-MX', {
                                                timeZone: 'America/Mexico_City',
                                            })}
                                        </time>
                                    </div>
                                    <p className="mt-2 text-sm">
                                        {change.reason}
                                    </p>
                                    <dl className="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-3">
                                        <div>
                                            <dt>Conductor</dt>
                                            <dd className="mt-1 text-foreground">
                                                {change.previous_driver ??
                                                    'Sin asignar'}{' '}
                                                →{' '}
                                                {change.driver ??
                                                    'No disponible'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Unidad</dt>
                                            <dd className="mt-1 text-foreground">
                                                {change.previous_vehicle ??
                                                    'Sin asignar'}{' '}
                                                →{' '}
                                                {change.vehicle ??
                                                    'No disponible'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Ruta</dt>
                                            <dd className="mt-1 text-foreground">
                                                {change.previous_route ??
                                                    'Sin ruta'}{' '}
                                                →{' '}
                                                {change.route ??
                                                    'No disponible'}
                                            </dd>
                                        </div>
                                    </dl>
                                </li>
                            ))}
                            {!history.length && (
                                <li className="py-5 text-sm text-muted-foreground">
                                    Todavía no hay cambios registrados. La
                                    asignación original del viaje se conservará
                                    al realizar el primer cambio.
                                </li>
                            )}
                        </ol>
                    </section>
                </div>
            </main>
        </>
    );
}
