import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { isActiveSection, navigationFor } from '@/lib/navigation';

/**
 * Barra de navegación inferior en celular (S4-07): Mapa, Rutas, Horarios,
 * Alertas y Perfil. No aparece para administración.
 */
export function MobileBottomNav() {
    const { auth, notifications } = usePage().props;
    const { url } = usePage();

    if (auth.user.role === 'admin') {
        return null;
    }

    return (
        <nav
            className="fixed inset-x-0 bottom-0 z-40 flex h-16 border-t border-border bg-card/95 backdrop-blur md:hidden"
            aria-label="Navegación inferior"
        >
            {navigationFor(auth.user.role)
                .filter((item) => item.short)
                .map((item) => {
                    const active = isActiveSection(item, url);

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                'relative flex flex-1 flex-col items-center justify-center gap-1 text-[11px] font-semibold',
                                active
                                    ? 'text-primary'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {active && (
                                <span className="absolute inset-x-5 top-0 h-0.5 rounded-full bg-primary" />
                            )}
                            <item.icon className="size-5" />
                            {item.short}
                            {item.badge === 'notifications' &&
                                (notifications?.unread ?? 0) > 0 && (
                                    <span className="absolute top-2 right-[22%] size-2 rounded-full bg-primary" />
                                )}
                        </Link>
                    );
                })}
        </nav>
    );
}
