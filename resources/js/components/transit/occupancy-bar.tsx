import { cn } from '@/lib/utils';

/** Desde este porcentaje el viaje se considera saturado. */
export const SATURATED_PERCENT = 85;

export function occupancyPercent(occupancy: number, capacity: number): number {
    return capacity > 0
        ? Math.min(100, Math.round((occupancy / capacity) * 100))
        : 0;
}

/** Color del relleno: verde con menos de 50 %, dorado hasta 84 % y rojo. */
export function occupancyTone(percent: number): string {
    if (percent >= SATURATED_PERCENT) return 'bg-destructive';
    if (percent >= 50) return 'bg-gold';

    return 'bg-live';
}

/** Barra de ocupación de la unidad (DESIGN.md · Estados y ocupación). */
export function OccupancyBar({
    percent,
    className,
}: {
    percent: number;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'h-1.5 overflow-hidden rounded-full bg-secondary',
                className,
            )}
            role="progressbar"
            aria-valuenow={percent}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label="Ocupación"
        >
            <div
                className={cn(
                    'h-full rounded-full transition-[width]',
                    occupancyTone(percent),
                )}
                style={{ width: `${percent}%` }}
            />
        </div>
    );
}
