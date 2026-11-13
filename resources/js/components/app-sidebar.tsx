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
    const page = usePage();
    const auth = page.props.auth;
    const currentUrl = page.url;

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

    const studentNavItems = [
        { label: 'Detalle de Rutas y Paradas', href: '/schedules', Icon: Route },
        { label: 'Horarios e Itinerarios', href: '/schedules', Icon: Clock3 },
        { label: 'Mis Notificaciones y Alertas', href: '/schedules', Icon: BellRing },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            tooltip="UniRoute — Inicio"
                        >
                            <Link href={dashboard()} prefetch>
                                <span className="flex min-w-0 items-center group-data-[collapsible=icon]:hidden">
                                    <AppLogo />
                                </span>
                                <img
                                    src="/branding/isotipo-oscuro.svg"
                                    alt="UniRoute"
                                    className="hidden size-6 shrink-0 object-contain group-data-[collapsible=icon]:block"
                                />
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

                {auth.user.role === 'student' && (
                    <div className="space-y-1 px-3 group-data-[collapsible=icon]:hidden">
                        {studentNavItems.map(({ label, href, Icon }) => (
                            <Link
                                key={label}
                                href={href}
                                className={`flex items-center gap-3 rounded-lg px-3 py-3 text-xs transition-colors ${
                                    currentUrl.startsWith(href)
                                        ? 'bg-secondary text-foreground font-medium'
                                        : 'text-placeholder hover:bg-secondary hover:text-foreground'
                                }`}
                            >
                                <Icon className="size-4 shrink-0" />
                                <span>{label}</span>
                            </Link>
                        ))}

                        <Link
                            href="/settings/profile"
                            className="flex items-center gap-3 rounded-lg px-3 py-3 text-xs hover:bg-secondary"
                        >
                            <Settings className="size-4" />
                            Perfil y Ajustes
                        </Link>
                    </div>
                )}
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
