import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type TransportStatus = 'active' | 'waiting' | 'incident' | 'inactive';

type StatusBadgeProps = {
    status: TransportStatus;
    className?: string;
};

const statusConfig: Record<
    TransportStatus,
    { label: string; className: string }
> = {
    active: {
        label: 'En servicio',
        className: 'border-live/40 bg-live/10 text-live',
    },
    waiting: {
        label: 'En espera',
        className: 'border-primary/40 bg-primary/10 text-primary',
    },
    incident: {
        label: 'Incidencia',
        className: 'border-destructive/40 bg-destructive/10 text-destructive',
    },
    inactive: {
        label: 'Fuera de servicio',
        className: 'border-muted-foreground/40 bg-muted text-muted-foreground',
    },
};

export function StatusBadge({ status, className }: StatusBadgeProps) {
    const config = statusConfig[status];

    return (
        <Badge variant="outline" className={cn(config.className, className)}>
            <span
                aria-hidden="true"
                className="size-1.5 rounded-full bg-current"
            />
            {config.label}
        </Badge>
    );
}
