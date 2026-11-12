import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, UsersRound } from 'lucide-react';
import { useState } from 'react';
import type { Paginated } from '@/types/pagination';
import type { DriverProfile } from '@/types/transit';

type Driver = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    driver_profile: DriverProfile | null;
    campus: { name: string } | null;
    driven_trips: {
        id: number;
        route: { name: string } | null;
        vehicle: { code: string } | null;
    }[];
};
type Props = {
    drivers: Paginated<Driver>;
    filters: { search: string | null };
    stats: { total: number; on_trip: number };
};
export default function Drivers({ drivers, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    return (
        <>
            <Head title="Conductores" />
            <main className="min-h-full space-y-6 bg-background p-4 sm:p-8">
                <Link href="/admin/fleet" className="text-sm text-primary">
                    ← Consola de flota
                </Link>
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="mb-2 text-xs tracking-widest text-primary uppercase">
                            UniRoute · Personal de transporte
                        </p>
                        <h1 className="text-3xl font-bold">
                            Conductores y relevos
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {stats.total} conductores registrados ·{' '}
                            {stats.on_trip} en viaje activo
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link
                            href="/admin/fleet/assignments"
                            className="rounded-lg border border-border px-4 py-2.5 text-sm hover:bg-secondary"
                        >
                            Asignaciones y relevos
                        </Link>
                        <Link
                            href="/admin/fleet/drivers/create"
                            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary-hover"
                        >
                            <Plus className="size-4" />
                            Registrar conductor
                        </Link>
                    </div>
                </header>
                <section className="overflow-hidden rounded-xl border border-border bg-card">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-border p-5">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <UsersRound className="size-5 text-primary" />
                            Personal registrado
                        </h2>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                router.get(
                                    '/admin/fleet/drivers',
                                    search.trim() ? { search } : {},
                                    { preserveState: true, replace: true },
                                );
                            }}
                            className="flex items-center gap-2 rounded-lg border border-border bg-background px-3"
                        >
                            <Search className="size-4 text-placeholder" />
                            <input
                                aria-label="Buscar por nombre, correo o licencia"
                                placeholder="Nombre, correo o licencia"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                maxLength={100}
                                className="h-10 min-w-0 bg-transparent text-sm outline-none placeholder:text-placeholder"
                            />
                            <button className="text-sm text-primary">
                                Buscar
                            </button>
                        </form>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-secondary/30 text-xs text-muted-foreground uppercase">
                                <tr>
                                    {[
                                        'Conductor',
                                        'Licencia',
                                        'Contratación',
                                        'Campus',
                                        'Viaje actual',
                                        'Cuenta',
                                    ].map((label) => (
                                        <th key={label} className="px-5 py-4">
                                            {label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {drivers.data.map((driver) => (
                                    <tr key={driver.id}>
                                        <td className="px-5 py-5">
                                            <p className="font-semibold">
                                                {driver.name}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {driver.email}
                                            </p>
                                        </td>
                                        <td className="px-5 py-5">
                                            {driver.driver_profile
                                                ?.license_number ??
                                                'Sin perfil'}
                                        </td>
                                        <td className="px-5 py-5 whitespace-nowrap">
                                            {driver.driver_profile?.hired_at ??
                                                '—'}
                                        </td>
                                        <td className="px-5 py-5">
                                            {driver.campus?.name ??
                                                'Sin campus'}
                                        </td>
                                        <td className="px-5 py-5">
                                            {driver.driven_trips.length ? (
                                                driver.driven_trips.map(
                                                    (trip) => (
                                                        <Link
                                                            key={trip.id}
                                                            href={`/admin/fleet/assignments/${trip.id}/edit`}
                                                            className="block text-xs text-primary"
                                                        >
                                                            {trip.route?.name} ·{' '}
                                                            {trip.vehicle
                                                                ?.code ??
                                                                'Sin unidad'}
                                                        </Link>
                                                    ),
                                                )
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    Sin viaje activo
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-5 py-5 text-xs">
                                            <span
                                                className={
                                                    driver.email_verified_at
                                                        ? 'text-live'
                                                        : 'text-primary'
                                                }
                                            >
                                                {driver.email_verified_at
                                                    ? 'Verificada'
                                                    : 'Correo pendiente'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                                {!drivers.data.length && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="p-12 text-center text-muted-foreground"
                                        >
                                            No hay conductores que coincidan con
                                            la búsqueda.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <footer className="flex flex-wrap justify-between gap-3 border-t border-border p-5 text-xs">
                        <span className="text-muted-foreground">
                            Mostrando {drivers.from ?? 0}–{drivers.to ?? 0} de{' '}
                            {drivers.total}
                        </span>
                        <nav
                            className="flex gap-4"
                            aria-label="Páginas de conductores"
                        >
                            {drivers.prev_page_url && (
                                <Link
                                    preserveScroll
                                    href={drivers.prev_page_url}
                                    className="text-primary"
                                >
                                    Anterior
                                </Link>
                            )}
                            <span>
                                {drivers.current_page} / {drivers.last_page}
                            </span>
                            {drivers.next_page_url && (
                                <Link
                                    preserveScroll
                                    href={drivers.next_page_url}
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
