import {
    BellRing,
    BusFront,
    Clock3,
    LayoutDashboard,
    LayoutGrid,
    Route,
    Settings,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { UserRole } from '@/types/transit';

export type AppNavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    /** Prefijo de la URL que marca la sección como activa. */
    match: string;
    /** Texto corto para la barra inferior del celular; sin él no aparece ahí. */
    short?: string;
    roles?: UserRole[];
    badge?: 'notifications';
    /** false: no va en la barra lateral (está en el menú de la cuenta). */
    sidebar?: false;
};

/** Las secciones de la app (mockup: UR.nav en assets/uniroute.js). */
export const appNavigation: AppNavItem[] = [
    {
        label: 'Mi viaje',
        href: '/driver',
        icon: BusFront,
        match: '/driver',
        short: 'Mi viaje',
        roles: ['driver'],
    },
    {
        label: 'Dashboard en Vivo',
        href: '/dashboard',
        icon: LayoutGrid,
        match: '/dashboard',
        short: 'Mapa',
        // Secciones del estudiante; el conductor trabaja desde "Mi viaje".
        roles: ['student', 'admin'],
    },
    {
        label: 'Detalle de Rutas y Paradas',
        href: '/routes',
        icon: Route,
        match: '/routes',
        short: 'Rutas',
        roles: ['student', 'admin'],
    },
    {
        label: 'Horarios e Itinerarios',
        href: '/schedules',
        icon: Clock3,
        match: '/schedules',
        short: 'Horarios',
        roles: ['student', 'admin'],
    },
    {
        label: 'Mis Notificaciones y Alertas',
        href: '/notifications',
        icon: BellRing,
        match: '/notifications',
        short: 'Alertas',
        badge: 'notifications',
    },
    {
        label: 'Panel de Administración',
        href: '/admin/fleet',
        icon: LayoutDashboard,
        match: '/admin',
        roles: ['admin'],
    },
    {
        // En escritorio está en el menú de la cuenta, junto a "Cerrar sesión";
        // en celular sigue en la barra inferior.
        label: 'Perfil y Ajustes',
        href: '/settings/profile',
        icon: Settings,
        match: '/settings',
        short: 'Perfil',
        sidebar: false,
    },
];

export function navigationFor(role: UserRole): AppNavItem[] {
    return appNavigation.filter(
        (item) => !item.roles || item.roles.includes(role),
    );
}

export function isActiveSection(item: AppNavItem, url: string): boolean {
    const path = url.split('?')[0];

    return path === item.match || path.startsWith(`${item.match}/`);
}
