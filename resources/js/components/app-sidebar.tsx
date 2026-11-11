import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    Route,
    Clock3,
    BellRing,
    Settings,
    ShieldCheck,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard en Vivo',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const navigation =
        auth.user.role === 'admin'
            ? [
                  ...mainNavItems,
                  {
                      title: 'Panel de Administración',
                      href: '/admin/fleet',
                      icon: LayoutGrid,
                  },
              ]
            : mainNavItems;
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <div className="mx-3 mb-3 flex items-center gap-2 rounded-lg bg-secondary px-3 py-2 text-[10px] group-data-[collapsible=icon]:hidden">
                    <span className="size-1.5 rounded-full bg-live" />
                    UniRoute · Red universitaria
                </div>
                <NavMain items={navigation} />
                <div className="space-y-1 px-3 group-data-[collapsible=icon]:hidden">
                    {[
                        { label: 'Detalle de Rutas y Paradas', Icon: Route },
                        { label: 'Horarios e Itinerarios', Icon: Clock3 },
                        {
                            label: 'Mis Notificaciones y Alertas',
                            Icon: BellRing,
                        },
                    ].map(({ label, Icon }) => (
                        <div
                            key={label}
                            aria-disabled="true"
                            title="Módulo pendiente de integración"
                            className="flex items-center gap-3 rounded-lg px-3 py-3 text-xs text-placeholder"
                        >
                            <Icon className="size-4 shrink-0" />
                            <span>
                                {label}
                                <span className="mt-0.5 block text-[9px]">
                                    Próximamente
                                </span>
                            </span>
                        </div>
                    ))}
                    <Link
                        href="/settings/profile"
                        className="flex items-center gap-3 rounded-lg px-3 py-3 text-xs hover:bg-secondary"
                    >
                        <Settings className="size-4" />
                        Perfil y Ajustes
                    </Link>
                </div>
            </SidebarContent>

            <SidebarFooter>
                <div className="rounded-xl border border-border bg-background/50 p-4 group-data-[collapsible=icon]:hidden">
                    <p className="flex items-center gap-2 text-xs font-semibold">
                        <ShieldCheck className="size-4 text-primary" />
                        Soporte en Ruta
                    </p>
                    <p className="mt-2 text-[11px] leading-relaxed text-muted-foreground">
                        Para reportar una incidencia, contacta al área de
                        transporte de tu campus.
                    </p>
                </div>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
