import { usePage } from '@inertiajs/react';
import { Building2, UserRound } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const isFleet = usePage().url.startsWith('/admin/fleet');
    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                {isFleet ? (
                    <span className="flex items-center gap-2 rounded-lg bg-secondary/60 px-3 py-2 text-xs">
                        <Building2 className="size-4 text-primary" />
                        Operación de campus
                    </span>
                ) : (
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                )}
            </div>
            {isFleet && (
                <div className="ml-auto flex items-center gap-4">
                    <span className="hidden items-center gap-2 rounded-full bg-secondary/50 px-3 py-1.5 text-[11px] text-muted-foreground sm:flex">
                        <span className="size-1.5 rounded-full bg-live" />
                        Consola de flota
                    </span>
                    <div className="hidden text-right sm:block">
                        <p className="text-xs font-semibold">
                            {auth.user.name}
                        </p>
                        <p className="text-[10px] tracking-wide text-primary">
                            Administración de transporte
                        </p>
                    </div>
                    <UserRound className="size-8 rounded-full bg-secondary p-2 text-primary" />
                </div>
            )}
        </header>
    );
}
