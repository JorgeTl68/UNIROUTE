import polyline from '@mapbox/polyline';
import 'leaflet/dist/leaflet.css';
import { LocateFixed, Minus, Plus } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useMemo, useState } from 'react';
import {
    MapContainer,
    Marker,
    Polyline,
    Popup,
    TileLayer,
    Tooltip,
    useMap,
} from 'react-leaflet';
import {
    minorStopIcon,
    stopIcon,
    unitIcon,
    userIcon,
} from '@/components/transit/map-icons';
import { StreetViewLink } from '@/components/transit/street-view-link';
import { cn } from '@/lib/utils';
import type {
    PositionSnapshot,
    RoutePoint,
    StopState,
    TransitRoute,
} from '@/types/transit';

type Layer = 'map' | 'satellite';
type LatLngTuple = [number, number];

export type MapRoute = Pick<
    TransitRoute,
    'id' | 'color' | 'encoded_polyline'
> & {
    stops: RoutePoint[];
};

export type MapVehicle = {
    trip_id: number;
    route_id: number;
    code: string;
    position: PositionSnapshot | null;
};

const layers: Record<Layer, { url: string; attribution: string }> = {
    map: {
        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '© OpenStreetMap contributors',
    },
    satellite: {
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        attribution: 'Tiles © Esri',
    },
};

const controlButton =
    'flex size-10 items-center justify-center text-foreground hover:bg-secondary';
const controlBox =
    'flex flex-col overflow-hidden rounded-xl border border-border bg-card/95 shadow-lg backdrop-blur';

/** Trazado guardado de la ruta o, si no hay, la unión de sus paradas. */
function routePath(route: MapRoute): LatLngTuple[] {
    if (route.encoded_polyline) {
        return polyline.decode(route.encoded_polyline) as LatLngTuple[];
    }

    return route.stops.map((stop) => [stop.latitude, stop.longitude]);
}

function FitToPath({ path }: { path: LatLngTuple[] }) {
    const map = useMap();

    useEffect(() => {
        if (path.length > 1) {
            map.fitBounds(path, { padding: [48, 48] });
        }
    }, [map, path]);

    return null;
}

function MapControls({
    onLocate,
    className,
}: {
    onLocate: (position: LatLngTuple) => void;
    className?: string;
}) {
    const map = useMap();
    const [locating, setLocating] = useState(false);

    const locate = () => {
        if (!navigator.geolocation) return;

        setLocating(true);
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                const position: LatLngTuple = [
                    coords.latitude,
                    coords.longitude,
                ];
                onLocate(position);
                map.flyTo(position, 15);
                setLocating(false);
            },
            () => setLocating(false),
            { enableHighAccuracy: true, timeout: 8000 },
        );
    };

    return (
        <div
            className={cn(
                'absolute right-3 z-[1000] flex flex-col gap-2',
                className,
            )}
        >
            <button
                type="button"
                onClick={locate}
                disabled={locating}
                className={cn(
                    controlBox,
                    controlButton,
                    locating && 'text-primary',
                )}
                aria-label="Mi ubicación"
            >
                <LocateFixed
                    className={cn(
                        'size-[18px]',
                        locating && 'motion-safe:animate-pulse',
                    )}
                />
            </button>
            <div className={controlBox}>
                <button
                    type="button"
                    onClick={() => map.zoomIn()}
                    className={controlButton}
                    aria-label="Acercar"
                >
                    <Plus className="size-[18px]" />
                </button>
                <button
                    type="button"
                    onClick={() => map.zoomOut()}
                    className={cn(controlButton, 'border-t border-border')}
                    aria-label="Alejar"
                >
                    <Minus className="size-[18px]" />
                </button>
            </div>
        </div>
    );
}

/**
 * Mapa de UniRoute (S1-07): la ruta elegida con halo y paradas numeradas, las
 * demás rutas tenues, las unidades en viaje con su rumbo, el selector
 * Mapa / Satélite (S4-08) y "mi ubicación".
 */
export function TransitMap({
    center,
    routes,
    selectedRouteId,
    stopStates = {},
    vehicles,
    highlightedTripId,
    onSelectRoute,
    controlsClassName,
    className,
    children,
}: {
    center: LatLngTuple;
    routes: MapRoute[];
    selectedRouteId: number | null;
    stopStates?: Record<number, StopState>;
    vehicles: MapVehicle[];
    highlightedTripId?: number | null;
    onSelectRoute?: (routeId: number) => void;
    controlsClassName?: string;
    className?: string;
    children?: ReactNode;
}) {
    const [layer, setLayer] = useState<Layer>('map');
    const [userPosition, setUserPosition] = useState<LatLngTuple | null>(null);
    const paths = useMemo(
        () =>
            new Map(
                routes.map((route) => [route.id, routePath(route)] as const),
            ),
        [routes],
    );
    const selected = routes.find((route) => route.id === selectedRouteId);
    const selectedStopIds = new Set(
        selected?.stops.map((stop) => stop.id) ?? [],
    );
    const selectedPath = useMemo(
        () => (selected ? (paths.get(selected.id) ?? []) : []),
        [paths, selected],
    );

    return (
        <div className={cn('relative isolate overflow-hidden', className)}>
            <MapContainer
                center={center}
                zoom={13}
                zoomControl={false}
                scrollWheelZoom
                className="transit-map size-full"
            >
                <TileLayer
                    key={layer}
                    url={layers[layer].url}
                    attribution={layers[layer].attribution}
                    className={layer === 'map' ? 'osm-tiles' : undefined}
                />

                {routes
                    .filter((route) => route.id !== selectedRouteId)
                    .map((route) => (
                        <Polyline
                            key={route.id}
                            positions={paths.get(route.id) ?? []}
                            pathOptions={{
                                color: route.color,
                                weight: 4,
                                opacity: 0.45,
                            }}
                            eventHandlers={{
                                click: () => onSelectRoute?.(route.id),
                            }}
                        />
                    ))}

                {routes
                    .filter((route) => route.id !== selectedRouteId)
                    .flatMap((route) =>
                        route.stops
                            .filter((stop) => !selectedStopIds.has(stop.id))
                            .map((stop) => (
                                <Marker
                                    key={`${route.id}-${stop.id}`}
                                    position={[stop.latitude, stop.longitude]}
                                    icon={minorStopIcon(route.color)}
                                >
                                    <Tooltip direction="top">
                                        {stop.name}
                                    </Tooltip>
                                </Marker>
                            )),
                    )}

                {selected && (
                    <>
                        <Polyline
                            positions={selectedPath}
                            pathOptions={{
                                color: '#3f6e00',
                                weight: 13,
                                opacity: 0.55,
                            }}
                        />
                        <Polyline
                            positions={selectedPath}
                            pathOptions={{ color: selected.color, weight: 6 }}
                        />
                        {selected.stops.map((stop) => (
                            <Marker
                                key={stop.id}
                                position={[stop.latitude, stop.longitude]}
                                icon={stopIcon(
                                    stopStates[stop.id] ?? 'pending',
                                    stop.position,
                                )}
                            >
                                <Tooltip direction="top" offset={[0, -16]}>
                                    {stop.name}
                                </Tooltip>
                                <Popup
                                    className="transit-popup"
                                    offset={[0, -12]}
                                    closeButton={false}
                                >
                                    <p className="text-sm font-semibold">
                                        {stop.name}
                                    </p>
                                    <StreetViewLink
                                        name={stop.name}
                                        latitude={stop.latitude}
                                        longitude={stop.longitude}
                                        className="mt-1"
                                    />
                                </Popup>
                            </Marker>
                        ))}
                        <FitToPath path={selectedPath} />
                    </>
                )}

                {vehicles.map(
                    (vehicle) =>
                        vehicle.position && (
                            <Marker
                                key={vehicle.trip_id}
                                position={[
                                    vehicle.position.latitude,
                                    vehicle.position.longitude,
                                ]}
                                icon={unitIcon(
                                    vehicle.code,
                                    vehicle.position.heading,
                                    vehicle.trip_id === highlightedTripId,
                                )}
                                zIndexOffset={1000}
                                eventHandlers={{
                                    click: () =>
                                        onSelectRoute?.(vehicle.route_id),
                                }}
                            />
                        ),
                )}

                {userPosition && (
                    <Marker position={userPosition} icon={userIcon}>
                        <Tooltip direction="top">Tu ubicación</Tooltip>
                    </Marker>
                )}

                <MapControls
                    onLocate={setUserPosition}
                    className={controlsClassName ?? 'bottom-10'}
                />
            </MapContainer>

            <div
                className="absolute top-3 right-3 z-[1000] flex rounded-xl border border-border bg-card/95 p-1 shadow-lg backdrop-blur"
                role="group"
                aria-label="Tipo de mapa"
            >
                {(
                    [
                        ['map', 'Mapa'],
                        ['satellite', 'Satélite'],
                    ] as const
                ).map(([value, label]) => (
                    <button
                        key={value}
                        type="button"
                        onClick={() => setLayer(value)}
                        aria-pressed={layer === value}
                        className={cn(
                            'rounded-md px-3 py-1.5 text-xs',
                            layer === value
                                ? 'bg-primary font-bold text-primary-foreground'
                                : 'font-semibold text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {children}
        </div>
    );
}
