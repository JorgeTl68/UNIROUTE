import { Head, Link, router } from '@inertiajs/react';
import { Bell, BellRing, CheckCheck, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { NotificationRow } from '@/components/transit/notification-row';
import { cn } from '@/lib/utils';
import type {
    AppNotification,
    NotificationType,
    TransitRoute,
} from '@/types/transit';

type RouteTag = Pick<TransitRoute, 'id' | 'number' | 'name' | 'color'>;

type Props = {
    items: AppNotification[];
    alarms: {
        id: number;
        minutes_before: number;
        route: RouteTag;
        stop: { id: number; name: string };
    }[];
    favorites: RouteTag[];
};

type Tab = 'all' | 'unread' | 'arrivals' | 'delays' | 'service';

const tabTypes: Partial<Record<Tab, NotificationType[]>> = {
    arrivals: ['arrival'],
    delays: ['delay', 'incident'],
    service: ['service'],
};

function isToday(iso: string): boolean {
    return new Date(iso).toDateString() === new Date().toDateString();
}

function RouteBadge({ route }: { route: RouteTag }) {
    return (
        <span
            className="flex size-8 shrink-0 items-center justify-center rounded-md text-xs font-extrabold text-ink"
            style={{ backgroundColor: route.color }}
        >
            {route.number}
        </span>
    );
}

export default function Notifications({
    items: notifications,
    alarms,
    favorites,
}: Props) {
    const [tab, setTab] = useState<Tab>('all');
    const unread = notifications.filter((item) => item.read_at === null);
    const visible = notifications.filter((item) =>
        tab === 'all'
            ? true
            : tab === 'unread'
              ? item.read_at === null
              : tabTypes[tab]?.includes(item.type),
    );
    const groups = [
        {
            label: 'Hoy',
            items: visible.filter((item) => isToday(item.created_at)),
        },
        {
            label: 'Anteriores',
            items: visible.filter((item) => !isToday(item.created_at)),
        },
    ].filter((group) => group.items.length > 0);

    const tabs: { id: Tab; label: string; count?: number }[] = [
        { id: 'all', label: 'Todas', count: notifications.length },
        { id: 'unread', label: 'Sin leer', count: unread.length },
        { id: 'arrivals', label: 'Llegadas' },
        { id: 'delays', label: 'Retrasos e incidencias' },
        { id: 'service', label: 'Avisos de servicio' },
    ];

    return (
        <main className="flex-1 space-y-6 px-4 py-6 pb-24 sm:px-6 md:pb-6 lg:px-8">
            <Head title="Mis Notificaciones y Alertas" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                        Mis Notificaciones y Alertas
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Llegadas, retrasos y avisos de las rutas que usas.
                    </p>
                </div>
                {unread.length > 0 && (
                    <button
                        type="button"
                        onClick={() =>
                            router.post(
                                '/notifications/read-all',
                                {},
                                { preserveScroll: true },
                            )
                        }
                        className="flex items-center gap-2 rounded-md border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground hover:bg-secondary"
                    >
                        <CheckCheck className="size-4" />
                        Marcar todas como leídas
                    </button>
                )}
            </div>

            <div className="grid gap-6 xl:grid-cols-12">
                <section className="min-w-0 space-y-4 xl:col-span-8">
                    <div className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                        <div
                            className="flex w-max gap-1 rounded-xl border border-border bg-card p-1"
                            role="tablist"
                            aria-label="Filtrar notificaciones"
                        >
                            {tabs.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    role="tab"
                                    aria-selected={tab === item.id}
                                    onClick={() => setTab(item.id)}
                                    className={cn(
                                        'flex items-center gap-2 rounded-md px-3 py-2 text-sm',
                                        tab === item.id
                                            ? 'bg-primary font-semibold text-primary-foreground'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {item.label}
                                    {item.count !== undefined && (
                                        <span
                                            className={cn(
                                                'rounded-full px-1.5 text-[11px]',
                                                tab === item.id
                                                    ? 'bg-primary-foreground/15'
                                                    : 'bg-secondary text-foreground',
                                            )}
                                        >
                                            {item.count}
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>

                    {groups.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 rounded-2xl border border-border bg-card px-6 py-12 text-center">
                            <Bell className="size-8 text-muted-foreground" />
                            <p className="text-sm font-semibold text-foreground">
                                No tienes notificaciones
                            </p>
                            <p className="max-w-sm text-xs text-muted-foreground">
                                Aquí verás cuándo llega tu unidad, los retrasos
                                y los avisos de tus rutas favoritas.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-2xl border border-border bg-card">
                            {groups.map((group) => (
                                <div key={group.label}>
                                    <p className="border-b border-border px-5 py-3 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                        {group.label}
                                    </p>
                                    <ul className="divide-y divide-border">
                                        {group.items.map((item) => (
                                            <NotificationRow
                                                key={item.id}
                                                notification={item}
                                            />
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <aside className="min-w-0 space-y-4 xl:col-span-4">
                    <div className="rounded-2xl border border-border bg-card p-5">
                        <h2 className="text-lg font-bold">Mis alarmas</h2>
                        {alarms.length === 0 ? (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Usa “Avisarme al llegar” en una ruta para crear
                                una alarma.
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-2">
                                {alarms.map((alarm) => (
                                    <li
                                        key={alarm.id}
                                        className="flex items-center gap-3 rounded-lg bg-background/50 p-3"
                                    >
                                        <RouteBadge route={alarm.route} />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold text-foreground">
                                                {alarm.stop.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                <BellRing className="mr-1 inline size-3" />
                                                {alarm.minutes_before} min antes
                                                · Ruta {alarm.route.number}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.delete(
                                                    `/arrival-alarms/${alarm.id}`,
                                                    { preserveScroll: true },
                                                )
                                            }
                                            className="text-muted-foreground hover:text-destructive"
                                            aria-label={`Quitar alarma de ${alarm.stop.name}`}
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="rounded-2xl border border-border bg-card p-5">
                        <h2 className="text-lg font-bold">Rutas favoritas</h2>
                        {favorites.length === 0 ? (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Marca una ruta con la estrella para recibir sus
                                retrasos.
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-2">
                                {favorites.map((route) => (
                                    <li key={route.id}>
                                        <Link
                                            href={`/routes/${route.id}`}
                                            className="flex items-center gap-3 rounded-lg bg-background/50 p-3 hover:bg-secondary/60"
                                        >
                                            <RouteBadge route={route} />
                                            <span className="min-w-0 flex-1 truncate text-sm font-semibold text-foreground">
                                                {route.name}
                                            </span>
                                            <Star className="size-4 fill-gold text-gold" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </aside>
            </div>
        </main>
    );
}
