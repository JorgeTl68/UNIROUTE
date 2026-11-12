import { Rotate3d } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Liga a Google Street View en unas coordenadas. Las ligas de Google Maps no
 * piden llave de API ni facturación; en celular abren la app de Google Maps
 * directo en la vista 360°.
 */
export function streetViewUrl(latitude: number, longitude: number): string {
    return `https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=${latitude},${longitude}`;
}

/** "Ver en 360°": abre la calle de la parada en Street View (pestaña nueva). */
export function StreetViewLink({
    name,
    latitude,
    longitude,
    className,
}: {
    name: string;
    latitude: number;
    longitude: number;
    className?: string;
}) {
    return (
        <a
            href={streetViewUrl(latitude, longitude)}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={`Ver ${name} en 360° en Google Maps`}
            className={cn(
                'inline-flex items-center gap-1 text-xs font-semibold text-gold hover:underline',
                className,
            )}
        >
            <Rotate3d className="size-3.5" />
            Ver en 360°
        </a>
    );
}
