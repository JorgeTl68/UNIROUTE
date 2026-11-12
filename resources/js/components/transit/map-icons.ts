import L from 'leaflet';
import type { StopState } from '@/types/transit';

/*
 * Marcadores del mapa con L.divIcon, con los colores de DESIGN.md (Mapa). Se
 * arman como HTML porque Leaflet los dibuja fuera de React; los que dependen
 * del tema usan las variables de app.css.
 */

const colors = {
    ink: '#020e24',
    card: 'var(--card)',
    border: 'var(--muted-foreground)',
    route: '#a3e635',
    gold: '#b69b72',
    unit: '#fddeb1',
    label: 'var(--primary)',
    live: '#91db2a',
    foreground: 'var(--foreground)',
};

/** Autobús relleno de lucide (bus-front) para el marcador de la unidad. */
const busSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="${colors.ink}" stroke="${colors.ink}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6 2 7"/><path d="M10 6h4"/><path d="m22 7-2-1"/><rect width="16" height="16" x="4" y="3" rx="2"/><path d="M4 11h16" stroke="${colors.unit}"/><path d="M8 15h.01" stroke="${colors.unit}"/><path d="M16 15h.01" stroke="${colors.unit}"/><path d="M6 19v2"/><path d="M18 21v-2"/></svg>`;

const stopStyles: Record<
    StopState,
    { fill: string; border: string; text: string; halo: boolean }
> = {
    pending: {
        fill: colors.card,
        border: colors.border,
        text: colors.foreground,
        halo: false,
    },
    passed: {
        fill: colors.route,
        border: colors.ink,
        text: colors.ink,
        halo: false,
    },
    next: {
        fill: colors.gold,
        border: colors.ink,
        text: colors.ink,
        halo: true,
    },
    current: {
        fill: colors.gold,
        border: colors.ink,
        text: colors.ink,
        halo: true,
    },
};

/** Parada numerada de 30 px; la siguiente lleva halo dorado. */
export function stopIcon(state: StopState, number: number): L.DivIcon {
    const style = stopStyles[state];
    const halo = style.halo
        ? `box-shadow:0 0 0 6px ${colors.gold}59;`
        : 'box-shadow:0 2px 6px #0008;';

    return L.divIcon({
        className: 'transit-marker',
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        html: `<div style="display:flex;width:30px;height:30px;align-items:center;justify-content:center;border-radius:9999px;background:${style.fill};border:3px solid ${style.border};color:${style.text};font-size:13px;font-weight:800;${halo}">${number}</div>`,
    });
}

/** Punto pequeño para las paradas de las rutas no elegidas. */
export function minorStopIcon(color: string): L.DivIcon {
    return L.divIcon({
        className: 'transit-marker',
        iconSize: [12, 12],
        iconAnchor: [6, 6],
        html: `<div style="width:12px;height:12px;border-radius:9999px;background:${colors.card};border:3px solid ${color};"></div>`,
    });
}

/**
 * Unidad: cuadro dorado de 44 px con el autobús, flecha de rumbo, anillo verde
 * que pulsa y su código debajo.
 */
export function unitIcon(
    code: string,
    heading: number | null,
    highlighted: boolean,
): L.DivIcon {
    const arrow =
        heading === null
            ? ''
            : `<div style="position:absolute;left:50%;top:-14px;width:0;height:0;margin-left:-7px;border-left:7px solid transparent;border-right:7px solid transparent;border-bottom:10px solid ${colors.unit};transform:rotate(${heading}deg);transform-origin:7px 36px;"></div>`;
    const ring = `<span class="transit-pulse" style="position:absolute;inset:-6px;border-radius:14px;border:3px solid ${colors.live}48;"></span>`;
    const outline = highlighted ? colors.live : colors.ink;

    return L.divIcon({
        className: 'transit-marker transit-unit',
        iconSize: [44, 64],
        iconAnchor: [22, 22],
        html: `<div style="position:relative;width:44px;height:44px;">${ring}${arrow}<div style="position:relative;display:flex;width:44px;height:44px;align-items:center;justify-content:center;border-radius:12px;background:${colors.unit};border:3px solid ${outline};box-shadow:0 6px 16px #000a;">${busSvg}</div><div style="position:absolute;left:50%;top:48px;transform:translateX(-50%);white-space:nowrap;border-radius:9999px;background:${colors.card};color:${colors.label};font-size:10px;font-weight:700;padding:2px 8px;box-shadow:0 2px 6px #0008;">${code}</div></div>`,
    });
}

/** Ubicación del usuario. */
export const userIcon = L.divIcon({
    className: 'transit-marker',
    iconSize: [18, 18],
    iconAnchor: [9, 9],
    html: `<div style="width:18px;height:18px;border-radius:9999px;background:${colors.foreground};border:3px solid ${colors.ink};box-shadow:0 0 0 8px color-mix(in srgb, ${colors.foreground} 20%, transparent);"></div>`,
});
