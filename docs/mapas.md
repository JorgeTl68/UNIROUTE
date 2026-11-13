# Mapas

UniRoute usa **Leaflet** con mapas de **OpenStreetMap** y calcula las rutas por
las calles con **OSRM**. Todo es gratis y no necesita llaves ni tarjeta.

Se decidió el 2026-11-11: Google Maps exige un prepago de 600 MXN para habilitar
sus APIs, aunque el uso sea gratuito.

## Librerías

Las instala Sosa en S0-08:

```bash
npm install leaflet react-leaflet @mapbox/polyline
npm install -D @types/leaflet @types/mapbox__polyline
```

- `react-leaflet` 5 es compatible con React 19.
- Importar el CSS una sola vez, en el componente del mapa:
  `import 'leaflet/dist/leaflet.css';`

## Capas del mapa

| Capa     | URL de los mosaicos                                                                             | Atribución obligatoria         |
| -------- | ----------------------------------------------------------------------------------------------- | ------------------------------ |
| Mapa     | `https://tile.openstreetmap.org/{z}/{x}/{y}.png`                                                | `© OpenStreetMap contributors` |
| Satélite | `https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}` | `Tiles © Esri`                 |

- **Mapa claro como el del mockup:** se usa la capa de OpenStreetMap con un filtro
  de CSS. En `<TileLayer>` se pasa `className="osm-claro"` y en el CSS:

    ```css
    .osm-claro {
        filter: grayscale(0.85) brightness(1.06) contrast(0.92);
    }
    ```

- **No usar CARTO:** su capa clara (`basemaps.cartocdn.com`) ahora pide llave y
  muestra "API KEY REQUIRED" en lugar del mapa.
- El selector **Mapa / Satélite** del mockup cambia entre la capa de OpenStreetMap y
  la de Esri.
- Los mapas necesitan que la página se abra desde un sitio (`uniroute.test` o
  `localhost:8000`), no como archivo suelto, porque OpenStreetMap pide saber desde
  dónde se carga.
- La atribución se pasa en la propiedad `attribution` de `<TileLayer>` y **siempre
  debe verse**; Leaflet la muestra en la esquina inferior derecha.
- Son servidores gratuitos de uso justo: nada de descargar mosaicos en masa.
- No hay capa de tráfico gratuita, así que el interruptor **Tráfico** del mockup
  queda fuera del alcance.

## Marcadores

Los colores y tamaños de paradas, unidades y línea de la ruta están en
[DESIGN.md](DESIGN.md#mapa).

Para paradas y unidades se usan marcadores de HTML con `L.divIcon`, así se pueden
pintar con las mismas clases de Tailwind del resto de la app:

```tsx
const busIcon = L.divIcon({
    className: '',
    html: `<div class="size-8 rounded-full bg-secondary-fixed ring-2 ring-primary"
                style="transform: rotate(${heading}deg)">…</div>`,
    iconSize: [32, 32],
});
```

El rumbo (`heading`) llega en las posiciones de la unidad (ver `transit.ts`).

## Trazar una ruta por las calles (OSRM)

Lo hace el servidor una sola vez, al crear o editar la ruta (Enrique, S1-13 y
S2-05), y el resultado se guarda en `routes.encoded_polyline`.

```php
use Illuminate\Support\Facades\Http;

// Ojo: OSRM recibe "longitud,latitud", al revés de como se escriben normalmente.
$coordinates = $stops->map(fn ($stop) => "{$stop->longitude},{$stop->latitude}")->implode(';');

$response = Http::get(config('services.osrm.url')."/route/v1/driving/{$coordinates}", [
    'overview' => 'full',
    'geometries' => 'polyline',
]);

$route->update(['encoded_polyline' => $response->json('routes.0.geometry')]);
```

En el frontend se decodifica y se dibuja:

```tsx
import polyline from '@mapbox/polyline';

const positions = polyline.decode(route.encoded_polyline); // [[lat, lng], ...]

<Polyline
    positions={positions}
    pathOptions={{ color: route.color, weight: 6 }}
/>;
```

Para probar desde la terminal (del centro de Saltillo a la puerta del Campus Central):

```bash
curl "https://router.project-osrm.org/route/v1/driving/-101.0005,25.4232;-100.9925,25.4262?overview=full&geometries=polyline"
```

- El servidor público de OSRM pide como máximo una consulta por segundo. Como la
  ruta se calcula solo al guardarla, no hay problema.
- Si algún día falla, la URL se cambia en `OSRM_URL` del `.env` sin tocar código.

## Vista de calle (Mapillary)

"Ver en 360°" (detalle de ruta y globo de la parada en el mapa) abre un modal
con la foto de calle más cercana a la parada, sin salir de UniRoute. Las fotos
son de **Mapillary**: abiertas, gratis y sin tarjeta. Leaflet por sí solo no
tiene vista de calle, y Google Street View dentro de la app pediría llave con
facturación.

- `App\Services\Transit\StreetView` lee las fotos de los **mosaicos de
  cobertura** de Mapillary (zoom 14, capa `image`; los decodifica
  `App\Services\Transit\VectorTile`), toma la más cercana a menos de 100 m de
  la parada y la guarda en caché. Si no hay ninguna, también se recuerda; si
  Mapillary no responde, se vuelve a intentar la siguiente vez. La búsqueda por
  zona de la API (`/images?bbox=`) regresa vacío, por eso se usan los mosaicos.
- `php artisan uniroute:vista-calle` renueva todas las paradas; corre cada
  lunes a las 03:30. Cada mosaico pesa ~2 MB, así que la primera búsqueda de
  una parada tarda unos segundos; con el comando ya quedan listas.
- `GET /stops/{stop}/street-view` regresa la foto y el token, porque el visor
  (`mapillary-js`) lo necesita en el navegador. Es el token de cliente,
  pensado para ser público.
- El visor se carga aparte (`React.lazy`) solo al abrir el modal.
- Sin fotos cerca, sin token, o si Mapillary no entrega la foto, el modal
  muestra la parada en la vista satelital de Esri.
- Cobertura revisada el 2026-11-12: 15 de las 22 paradas tienen foto a menos de
  6 m. Sin foto: OXXO Santa Elena, UTC, María Mazzarello, Arco de las Lomas,
  Blvd. del Valle, OXXO Gas 4 de Octubre y Valdez Sánchez y Minería.

Para sacar el token: crear una cuenta en mapillary.com, ir a **Dashboard →
Developers → Register application**, marcar el permiso **Read** y copiar el
**Client Token** (empieza con `MLY|`) en `MAPILLARY_TOKEN`. Sin el permiso
Read, los mosaicos funcionan pero el visor no recibe las fotos.

## Variables de entorno

```dotenv
OSRM_URL=https://router.project-osrm.org
MAPILLARY_TOKEN=MLY|...
```

Los mapas y el trazado no usan llaves; el token de Mapillary solo es para la
vista de calle y, sin él, la app funciona igual (muestra la vista satelital).
