import { Link, router, usePage } from '@inertiajs/react';
import { Bell, Building2, ChevronDown, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { NotificationRow } from '@/components/transit/notification-row';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import type { UserRole } from '@/types/transit';

const roleLabels: Record<UserRole, string> = {
    student: 'Estudiante',
    driver: 'Conductor',
    admin: 'Administración',
};

/**
 * Header de la app (DESIGN.md · Layout de la app): campus, búsqueda (Ctrl K),
 * unidades en servicio, centro de notificaciones (S3-08) y usuario.
 */
export function AppSidebarHeader() {
    const { auth, campuses, currentCampus, liveUnits, notifications } =
        usePage().props;
    const { url } = usePage();
    const getInitials = useInitials();
    const searchRef = useRef<HTMLInputElement>(null);
    const [search, setSearch] = useState('');

    useEffect(() => {
        const focusSearch = (event: KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
                event.preventDefault();
                searchRef.current?.focus();
            }
        };

        window.addEventListener('keydown', focusSearch);

        return () => window.removeEventListener('keydown', focusSearch);
    }, []);

    const chooseCampus = (slug: string) =>
        router.get(url.split('?')[0], { campus: slug });

    return (
        <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-border bg-background/85 px-4 backdrop-blur sm:px-6 lg:px-8">
            <SidebarTrigger className="-ml-1 shrink-0" />

            {currentCampus && campuses.length > 0 && (
                <DropdownMenu>
                    <DropdownMenuTrigger className="hidden shrink-0 items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium text-foreground hover:bg-secondary md:flex">
                        <Building2 className="size-4 text-gold" />
                        {currentCampus.name}
                        <ChevronDown className="size-4 text-muted-foreground" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        {campuses.map((campus) => (
                            <DropdownMenuItem
                                key={campus.id}
                                onSelect={() => chooseCampus(campus.slug)}
                                className={
                                    campus.id === currentCampus.id
                                        ? 'font-semibold text-primary'
                                        : undefined
                                }
                            >
                                {campus.name}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}

            <form
                role="search"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get('/dashboard', { buscar: search.trim() });
                }}
                className="relative min-w-0 flex-1 md:max-w-sm"
            >
                <label htmlFor="app-search" className="sr-only">
                    Buscar
                </label>
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-placeholder" />
                <input
                    id="app-search"
                    ref={searchRef}
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Buscar ruta, parada o colonia…"
                    className="w-full rounded-md border border-border bg-card py-2 pr-3 pl-9 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50 md:pr-16"
                />
                <kbd className="pointer-events-none absolute top-1/2 right-2 hidden -translate-y-1/2 rounded bg-secondary px-1.5 py-0.5 text-[11px] font-semibold text-muted-foreground md:block">
                    Ctrl K
                </kbd>
            </form>

            <div className="hidden items-center gap-2 rounded-full bg-card px-3 py-1.5 text-xs xl:flex">
                <span className="relative flex size-2">
                    <span className="absolute inline-flex size-full rounded-full bg-live opacity-75 motion-safe:animate-ping" />
                    <span className="relative inline-flex size-2 rounded-full bg-live" />
                </span>
                <span className="font-semibold text-foreground">
                    En servicio:
                </span>
                <span className="text-muted-foreground">
                    {liveUnits}{' '}
                    {liveUnits === 1
                        ? 'unidad en circulación'
                        : 'unidades en circulación'}
                </span>
            </div>

            <div className="ml-auto flex shrink-0 items-center gap-3">
                <DropdownMenu>
                    <DropdownMenuTrigger
                        className="relative flex size-9 items-center justify-center rounded-md bg-card text-foreground hover:bg-secondary"
                        aria-label={
                            notifications?.unread
                                ? `Notificaciones, ${notifications.unread} sin leer`
                                : 'Notificaciones'
                        }
                    >
                        <Bell className="size-[18px]" />
                        {(notifications?.unread ?? 0) > 0 && (
                            <span className="absolute top-2 right-2 size-2 rounded-full bg-primary ring-2 ring-card" />
                        )}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        className="w-[calc(100vw-2rem)] max-w-sm overflow-hidden p-0"
                    >
                        <DropdownMenuLabel className="flex items-center justify-between border-b border-border px-4 py-3">
                            <span className="text-sm font-semibold text-foreground">
                                Notificaciones
                            </span>
                            {(notifications?.unread ?? 0) > 0 && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.post(
                                            '/notifications/read-all',
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                    className="text-xs font-semibold text-primary hover:underline"
                                >
                                    Marcar todas como leídas
                                </button>
                            )}
                        </DropdownMenuLabel>
                        {notifications && notifications.latest.length > 0 ? (
                            <ul className="divide-y divide-border">
                                {notifications.latest.map((notification) => (
                                    <NotificationRow
                                        key={notification.id}
                                        notification={notification}
                                        compact
                                    />
                                ))}
                            </ul>
                        ) : (
                            <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                                No tienes notificaciones
                            </p>
                        )}
                        <Link
                            href="/notifications"
                            className="block border-t border-border px-4 py-3 text-center text-sm font-semibold text-primary hover:bg-secondary/50"
                        >
                            Ver todas
                        </Link>
                    </DropdownMenuContent>
                </DropdownMenu>

                <div className="hidden h-6 w-px bg-border sm:block" />

                <DropdownMenu>
                    <DropdownMenuTrigger className="flex items-center gap-3 text-left">
                        <span className="hidden sm:block">
                            <span className="block text-sm leading-tight font-semibold text-foreground">
                                {auth.user.name}
                            </span>
                            <span className="block text-xs leading-tight text-gold">
                                {roleLabels[auth.user.role]}
                            </span>
                        </span>
                        <span className="flex size-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                            {getInitials(auth.user.name)}
                        </span>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="min-w-56">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
