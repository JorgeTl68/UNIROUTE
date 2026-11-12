/** Hora "HH:MM" en hora de Saltillo a partir de una fecha ISO. */
export function clockTime(iso: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'America/Monterrey',
    }).format(new Date(iso));
}

/** "450 m" o "1.2 km". */
export function distanceLabel(meters: number): string {
    return meters < 1000
        ? `${Math.round(meters / 10) * 10} m`
        : `${(meters / 1000).toFixed(1)} km`;
}

/** Distancia en metros entre dos coordenadas (fórmula de haversine). */
export function distanceMeters(
    [latA, lngA]: [number, number],
    [latB, lngB]: [number, number],
): number {
    const radians = (degrees: number) => (degrees * Math.PI) / 180;
    const deltaLat = radians(latB - latA);
    const deltaLng = radians(lngB - lngA);
    const a =
        Math.sin(deltaLat / 2) ** 2 +
        Math.cos(radians(latA)) *
            Math.cos(radians(latB)) *
            Math.sin(deltaLng / 2) ** 2;

    return 2 * 6_371_000 * Math.asin(Math.min(1, Math.sqrt(a)));
}

/** "Hace 5 min", "Hace 2 h", "Ayer" o la fecha. */
export function timeAgo(iso: string): string {
    const minutes = Math.round((Date.now() - Date.parse(iso)) / 60_000);

    if (minutes < 1) return 'Ahora';
    if (minutes < 60) return `Hace ${minutes} min`;
    if (minutes < 24 * 60) return `Hace ${Math.round(minutes / 60)} h`;
    if (minutes < 48 * 60) return 'Ayer';

    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
    }).format(new Date(iso));
}
