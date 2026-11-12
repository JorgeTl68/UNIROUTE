import { Link, usePage } from '@inertiajs/react';
import { Siren } from 'lucide-react';
import { useEffect } from 'react';
import AppLogo from '@/components/app-logo';
import { EmergencyDialog } from '@/components/emergency-dialog';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { isActiveSection, navigationFor } from '@/lib/navigation';

/**
 * Sidebar de la app (DESIGN.md · Layout de la app): logo, estado de la red,
 * las secciones según el rol y "Soporte en Ruta" con el botón de emergencia.
 */
export function AppSidebar() {
    const { auth, liveUnits, notifications } = usePage().props;
    const { url } = usePage();
    const { setOpenMobile } = useSidebar();

    // En celular el menú es un cajón; el layout no se vuelve a montar al
    // navegar con Inertia, así que se cierra cada vez que cambia la página.
    useEffect(() => {
        setOpenMobile(false);
    }, [url, setOpenMobile]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <div className="mx-2 flex items-center justify-between rounded-md bg-background/60 px-3 py-2 text-xs group-data-[collapsible=icon]:hidden">
                    <span className="flex items-center gap-2 text-muted-foreground">
                        <span className="size-1.5 rounded-full bg-live" />
                        Red operativa
                    </span>
                    <span className="font-semibold text-foreground">
                        {liveUnits} {liveUnits === 1 ? 'activa' : 'activas'}
                    </span>
                </div>

                <nav aria-label="Menú principal" className="px-2">
                    <SidebarMenu className="gap-1">
                        {navigationFor(auth.user.role).map((item) => {
                            const active = isActiveSection(item, url);
                            const unread =
                                item.badge === 'notifications'
                                    ? (notifications?.unread ?? 0)
                                    : 0;

                            return (
                                <SidebarMenuItem key={item.href}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={active}
                                        tooltip={item.label}
                                        className="h-10 data-[active=true]:bg-primary data-[active=true]:font-semibold data-[active=true]:text-primary-foreground"
                                    >
                                        <Link
                                            href={item.href}
                                            prefetch
                                            aria-current={
                                                active ? 'page' : undefined
                                            }
                                        >
                                            <item.icon className="size-[18px]" />
                                            <span>{item.label}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                    {unread > 0 && (
                                        <SidebarMenuBadge className="top-2.5 rounded-full bg-secondary px-2 text-[11px] font-bold text-foreground peer-data-[active=true]/menu-button:bg-primary-foreground/15 peer-data-[active=true]/menu-button:text-primary-foreground">
                                            {unread}
                                        </SidebarMenuBadge>
                                    )}
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </nav>
            </SidebarContent>

            <SidebarFooter className="group-data-[collapsible=icon]:hidden">
                <div className="rounded-xl border border-border bg-background/50 p-4">
                    <p className="flex items-center gap-2 text-sm font-semibold text-foreground">
                        <Siren className="size-4 text-destructive" />
                        Soporte en Ruta
                    </p>
                    <p className="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                        Incidencias en tiempo real y ayuda directa del área de
                        transporte.
                    </p>
                    <EmergencyDialog>
                        <button
                            type="button"
                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-md border border-destructive/40 bg-destructive/10 py-2 text-xs font-semibold text-destructive hover:bg-destructive/20"
                        >
                            <Siren className="size-4" />
                            Reportar emergencia
                        </button>
                    </EmergencyDialog>
                </div>
                <p className="px-1 text-[11px] text-placeholder">
                    UniRoute v1.0
                </p>
            </SidebarFooter>
        </Sidebar>
    );
}
