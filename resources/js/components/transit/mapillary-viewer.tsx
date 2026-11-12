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
}: {
    imageId: string;
    accessToken: string;
}) {
    const container = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!container.current) return;

        const viewer = new Viewer({
            accessToken,
            container: container.current,
            imageId,
            // Sin portada: la vista abre directo en la foto de la parada.
            component: { cover: false },
        });

        return () => viewer.remove();
    }, [imageId, accessToken]);

    return <div ref={container} className="size-full" />;
}
