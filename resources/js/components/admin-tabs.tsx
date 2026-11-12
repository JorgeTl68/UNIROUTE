import { Link, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    ChartColumn,
    IdCard,
    Route,
    TriangleAlert,
    Wrench,
    BusFront,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const tabs = [
    {
        label: 'Unidades',
        href: '/admin/fleet',
        icon: BusFront,
        active: (path: string) =>
            path === '/admin/fleet' ||
            path.startsWith('/admin/fleet/assignments'),
    },
    {
        label: 'Conductores',
        href: '/admin/fleet/drivers',
        icon: IdCard,
        active: (path: string) => path.startsWith('/admin/fleet/drivers'),
    },
    {
        label: 'Mantenimiento',
        href: '/admin/fleet/maintenance',
        icon: Wrench,
        active: (path: string) => path.startsWith('/admin/fleet/maintenance'),
    },
    {
        label: 'Rutas y paradas',
        href: '/admin/routes',
        icon: Route,
        active: (path: string) =>
            path === '/admin/routes' || path === '/admin/routes/create',
    },
    {
        label: 'Horarios de salida',
        href: '/admin/schedules',
        icon: CalendarClock,
        active: (path: string) => /^\/admin\/routes\/\d+\/schedules/.test(path),
    },
    {
        label: 'Incidencias',
        href: '/admin/incidents',
        icon: TriangleAlert,
        active: (path: string) => path.startsWith('/admin/incidents'),
    },
    {
        label: 'Reportes de aforo',
        href: '/admin/reports/occupancy',
        icon: ChartColumn,
        active: (path: string) => path.startsWith('/admin/reports'),
    },
];

/** Pestañas del panel de administración (mockup: UR.tabsAdmin). */
export function AdminTabs() {
    const path = usePage().url.split('?')[0];

    return (
        <nav
            className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0"
            aria-label="Secciones de administración"
        >
            <div className="flex w-max gap-1 rounded-xl border border-border bg-card p-1">
                {tabs.map((tab) => {
                    const active = tab.active(path);

                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                'flex items-center gap-2 rounded-md px-3 py-2 text-sm whitespace-nowrap',
                                active
                                    ? 'bg-primary font-semibold text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
                            )}
                        >
                            <tab.icon className="size-4" />
                            {tab.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
