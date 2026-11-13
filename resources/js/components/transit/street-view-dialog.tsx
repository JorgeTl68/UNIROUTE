import 'leaflet/dist/leaflet.css';
import { LoaderCircle, Rotate3d } from 'lucide-react';
import { lazy, Suspense, useCallback, useEffect, useState } from 'react';
import { CircleMarker, MapContainer, TileLayer } from 'react-leaflet';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { streetView } from '@/routes/stops';
import type { RoutePoint } from '@/types/transit';

const MapillaryViewer = lazy(
    () => import('@/components/transit/mapillary-viewer'),
);

type StopPoint = Pick<RoutePoint, 'id' | 'name' | 'latitude' | 'longitude'>;

/** Foto de Mapillary más cercana a la parada (StreetView en el servidor). */
type StreetImage = {
    id: string;
    is_pano: boolean;
    captured_at: string | null;
    distance_meters: number;
};

type Lookup =
    | { status: 'loading' }
    | { status: 'ready'; image: StreetImage; accessToken: string }
    | { status: 'none' }
    /** Había foto, pero Mapillary no la entregó al visor. */
    | { status: 'failed' };

/** Más lejos que esto, se avisa a qué distancia de la parada se tomó la foto. */
const NEAR_METERS = 25;

const monthYear = new Intl.DateTimeFormat('es-MX', {
    month: 'long',
    year: 'numeric',
});

function describe(lookup: Lookup): string {
    if (lookup.status === 'loading') return 'Buscando la vista de calle…';

    if (lookup.status === 'none') {
        return 'Esta parada todavía no tiene vista de calle. Así se ve desde el satélite.';
    }

    if (lookup.status === 'failed') {
        return 'No se pudo cargar la foto de Mapillary. Así se ve desde el satélite.';
    }

    const { captured_at, distance_meters } = lookup.image;

    return [
        'Foto de Mapillary',
        captured_at && `tomada en ${monthYear.format(new Date(captured_at))}`,
        distance_meters > NEAR_METERS && `a ${distance_meters} m de la parada`,
    ]
        .filter(Boolean)
        .join(' · ');
}

function Spinner() {
    return (
        <div className="flex size-full items-center justify-center text-muted-foreground">
            <LoaderCircle className="size-6 animate-spin" />
        </div>
    );
}

/** Vista satelital de la parada cuando no hay foto de calle que mostrar. */
function SatelliteView({ stop }: { stop: StopPoint }) {
    return (
        <MapContainer
            center={[stop.latitude, stop.longitude]}
            zoom={18}
            className="size-full"
        >
            <TileLayer
                url="https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}"
                attribution="Tiles © Esri"
                maxZoom={19}
            />
            <CircleMarker
                center={[stop.latitude, stop.longitude]}
                radius={10}
                pathOptions={{
                    color: '#020e24',
                    weight: 3,
                    fillColor: '#fddeb1',
                    fillOpacity: 1,
                }}
            />
        </MapContainer>
    );
}

function StreetViewContent({ stop }: { stop: StopPoint }) {
    const [lookup, setLookup] = useState<Lookup>({ status: 'loading' });
    const showFailure = useCallback(() => setLookup({ status: 'failed' }), []);

    useEffect(() => {
        const controller = new AbortController();

        fetch(streetView.url(stop.id), {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((response) => (response.ok ? response.json() : null))
            .then(
                (
                    data: {
                        image: StreetImage | null;
                        access_token: string | null;
                    } | null,
                ) =>
                    setLookup(
                        data?.image && data.access_token
                            ? {
                                  status: 'ready',
                                  image: data.image,
                                  accessToken: data.access_token,
                              }
                            : { status: 'none' },
                    ),
            )
            .catch((error: unknown) => {
                if (
                    !(error instanceof DOMException) ||
                    error.name !== 'AbortError'
                ) {
                    setLookup({ status: 'none' });
                }
            });

        return () => controller.abort();
    }, [stop.id]);

    return (
        <>
            <DialogHeader>
                <DialogTitle>{stop.name}</DialogTitle>
                <DialogDescription>{describe(lookup)}</DialogDescription>
            </DialogHeader>
            <div className="relative isolate h-[60vh] max-h-[520px] overflow-hidden rounded-lg border border-border bg-muted">
                {lookup.status === 'loading' && <Spinner />}
                {lookup.status === 'ready' && (
                    <Suspense fallback={<Spinner />}>
                        <MapillaryViewer
                            imageId={lookup.image.id}
                            accessToken={lookup.accessToken}
                            onError={showFailure}
                        />
                    </Suspense>
                )}
                {(lookup.status === 'none' || lookup.status === 'failed') && (
                    <SatelliteView stop={stop} />
                )}
            </div>
        </>
    );
}

/**
 * "Ver en 360°": abre un modal con la vista de calle de la parada (Mapillary)
 * sin salir de UniRoute. Si no hay fotos cerca, muestra la vista satelital.
 */
export function StreetViewDialog({
    stop,
    className,
}: {
    stop: StopPoint;
    className?: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <button
                    type="button"
                    className={cn(
                        'inline-flex items-center gap-1 text-xs font-semibold text-gold hover:underline',
                        className,
                    )}
                >
                    <Rotate3d className="size-3.5" />
                    Ver en 360°
                </button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-3xl">
                <StreetViewContent stop={stop} />
            </DialogContent>
        </Dialog>
    );
}
