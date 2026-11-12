import { cn } from '@/lib/utils';

export type ChipTone = 'live' | 'warning' | 'danger' | 'neutral';

const tones: Record<ChipTone, string> = {
    live: 'bg-live/15 text-live',
    warning: 'bg-warning/15 text-warning',
    danger: 'bg-destructive/15 text-destructive',
    neutral: 'bg-secondary text-muted-foreground',
};

const dots: Record<ChipTone, string> = {
    live: 'bg-live motion-safe:animate-pulse',
    warning: 'bg-warning',
    danger: 'bg-destructive',
    neutral: 'bg-muted-foreground',
};

/**
 * Chip de estado (DESIGN.md · Estados y ocupación): En vivo, Demorado,
 * Crítica, Programado…
 */
export function StatusChip({
    tone,
    label,
    dot = false,
    className,
}: {
    tone: ChipTone;
    label: string;
    dot?: boolean;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-bold whitespace-nowrap',
                tones[tone],
                className,
            )}
        >
            {dot && (
                <span className={cn('size-1.5 rounded-full', dots[tone])} />
            )}
            {label}
        </span>
    );
}
