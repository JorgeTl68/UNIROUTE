import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarClock } from 'lucide-react';
import type { Paginated } from '@/types/pagination';
import type { Trip } from '@/types/transit';
export type AssignmentTrip = Trip & {
    driver: { name: string } | null;
    vehicle: { code: string } | null;
    route: { name: string; number: number } | null;
};
export default function Assignments({
    trips,
}: {
    trips: Paginated<AssignmentTrip>;
}) {
    return (
        <>
            <Head title="Asignaciones y relevos" />
            <main className="min-h-full space-y-6 bg-background p-4 sm:p-8">
                <Link
                    href="/admin/fleet/drivers"
                    className="text-sm text-primary"
                >
                    ← Conductores
                </Link>
                <header>
                    <p className="mb-2 text-xs tracking-widest text-primary uppercase">
                        UniRoute · Despacho
                    </p>
                    <h1 className="text-3xl font-bold">
                        Asignaciones y relevos
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Asigna personal a viajes programados o realiza un relevo
                        en un viaje en curso.
                    </p>
                </header>
                <section className="overflow-hidden rounded-xl border border-border bg-card">
                    <div className="flex items-center gap-2 border-b border-border p-5">
                        <CalendarClock className="size-5 text-primary" />
                        <h2 className="font-semibold">
                            Viajes programados y en curso
                        </h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-secondary/30 text-xs text-muted-foreground">
                                <tr>
                                    {[
                                        'Salida (hora de México)',
                                        'Ruta',
                                        'Conductor',
                                        'Unidad',
                                        'Estado',
                                        'Acción',
                                    ].map((label) => (
                                        <th key={label} className="px-5 py-4">
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {trips.data.map((trip) => (
                                    <tr key={trip.id}>
                                        <td className="px-5 py-5">
                                            {new Date(
                                                trip.scheduled_at,
                                            ).toLocaleString('es-MX', {
                                                timeZone: 'America/Mexico_City',
                                                dateStyle: 'short',
                                                timeStyle: 'short',
                                            })}
                                        </td>
                                        <td className="px-5 py-5">
                                            {trip.route?.name ?? 'Sin ruta'}
                                        </td>
                                        <td className="px-5 py-5">
                                            {trip.driver?.name ?? 'Sin asignar'}
                                        </td>
                                        <td className="px-5 py-5">
                                            {trip.vehicle?.code ??
                                                'Sin asignar'}
                                        </td>
                                        <td className="px-5 py-5">
                                            <span
                                                className={
                                                    trip.status ===
                                                    'in_progress'
                                                        ? 'text-live'
                                                        : 'text-primary'
                                                }
                                            >
                                                {trip.status === 'in_progress'
                                                    ? 'En curso'
                                                    : 'Programado'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-5">
                                            <Link
                                                href={`/admin/fleet/assignments/${trip.id}/edit`}
                                                className="inline-flex items-center gap-2 whitespace-nowrap text-primary"
                                            >
                                                {trip.status === 'in_progress'
                                                    ? 'Realizar relevo'
                                                    : 'Asignar'}
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {!trips.data.length && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="p-12 text-center text-muted-foreground"
                                        >
                                            No hay viajes programados ni en
                                            curso. Las salidas se crean desde el
                                            módulo de horarios.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <footer className="flex justify-between border-t border-border p-5 text-xs">
                        <span>{trips.total} viajes</span>
                        <nav
                            aria-label="Páginas de viajes"
                            className="flex gap-4"
                        >
                            {trips.prev_page_url && (
                                <Link
                                    href={trips.prev_page_url}
                                    className="text-primary"
                                >
                                    Anterior
                                </Link>
                            )}
                            <span>
                                {trips.current_page} / {trips.last_page}
                            </span>
                            {trips.next_page_url && (
                                <Link
                                    href={trips.next_page_url}
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
Assignments.layout = {
    breadcrumbs: [
        { title: 'Conductores', href: '/admin/fleet/drivers' },
        { title: 'Asignaciones', href: '/admin/fleet/assignments' },
    ],
};
