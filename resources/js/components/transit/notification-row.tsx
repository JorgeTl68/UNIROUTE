import { Link } from '@inertiajs/react';
import { BusFront, ClockAlert, Megaphone, TriangleAlert } from 'lucide-react';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/transit-format';
import type { AppNotification, NotificationType } from '@/types/transit';

/** Ícono y color de cada tipo de aviso (mockup de notificaciones). */
export const notificationStyles: Record<
    NotificationType,
    { icon: typeof BusFront; className: string }
> = {
    arrival: { icon: BusFront, className: 'bg-live/15 text-live' },
    delay: { icon: ClockAlert, className: 'bg-warning/15 text-warning' },
    incident: {
        icon: TriangleAlert,
        className: 'bg-destructive/15 text-destructive',
    },
    service: { icon: Megaphone, className: 'bg-primary/15 text-primary' },
};

/** Un aviso en la lista; al abrirlo se marca como leído. */
export function NotificationRow({
    notification,
    compact = false,
}: {
    notification: AppNotification;
    compact?: boolean;
}) {
    const { icon: Icon, className } = notificationStyles[notification.type];
    const unread = notification.read_at === null;

    return (
        <li>
            <Link
                href={`/notifications/${notification.id}`}
                className={cn(
                    'flex gap-3 hover:bg-secondary/50',
                    compact ? 'px-4 py-3' : 'gap-4 px-5 py-4',
                    unread && !compact && 'bg-primary/[0.04]',
                )}
            >
                <span
                    className={cn(
                        'flex shrink-0 items-center justify-center rounded-lg',
                        compact ? 'size-9' : 'size-10',
                        className,
                    )}
                >
                    <Icon className="size-4" />
                </span>
                <span className="min-w-0 flex-1">
                    <span
                        className={cn(
                            'flex items-center gap-2 text-sm font-semibold',
                            unread ? 'text-strong' : 'text-foreground',
                        )}
                    >
                        <span className={cn(compact && 'truncate')}>
                            {notification.title}
                        </span>
                        {unread && (
                            <span
                                className="size-2 shrink-0 rounded-full bg-primary"
                                aria-label="Sin leer"
                            />
                        )}
                    </span>
                    <span
                        className={cn(
                            'block text-xs text-muted-foreground',
                            compact ? 'truncate' : 'mt-0.5',
                        )}
                    >
                        {notification.body}
                    </span>
                    <span className="mt-0.5 block text-[11px] text-placeholder">
                        {timeAgo(notification.created_at)}
                    </span>
                </span>
            </Link>
        </li>
    );
}
