import 'mapillary-js/dist/mapillary.css';
import { Viewer } from 'mapillary-js';
import { useEffect, useRef } from 'react';

/**
 * Visor de Mapillary. Se carga aparte (React.lazy) solo cuando alguien abre
 * "Ver en 360°", para no hacer más pesada la carga de la app.
 */
export default function MapillaryViewer({
    imageId,
    accessToken,
    onError,
}: {
    imageId: string;
    accessToken: string;
    /** Mapillary no entregó la foto (token sin permiso, foto borrada…). */
    onError: () => void;
}) {
    const container = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!container.current) return;

        const viewer = new Viewer({
            accessToken,
            container: container.current,
            // Sin portada: la vista abre directo en la foto de la parada.
            component: { cover: false },
        });
        let active = true;

        viewer.moveTo(imageId).catch(() => {
            if (active) onError();
        });

        return () => {
            active = false;
            viewer.remove();
        };
    }, [imageId, accessToken, onError]);

    return <div ref={container} className="size-full" />;
}
