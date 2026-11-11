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

| Capa            | URL de los mosaicos                                                                             | Atribución obligatoria                 |
| --------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------- |
| Mapa (claro)    | `https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png`                                | `© OpenStreetMap contributors © CARTO` |
| Mapa (estándar) | `https://tile.openstreetmap.org/{z}/{x}/{y}.png`                                                | `© OpenStreetMap contributors`         |
| Satélite        | `https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}` | `Tiles © Esri`                         |

- **Recomendada:** la capa clara de CARTO, que se parece al mapa gris del mockup
  del Dashboard. Es gratuita para proyectos no comerciales.
- El selector **Mapa / Satélite** del mockup cambia entre la capa clara y la de Esri.
- La atribución se pasa en la propiedad `attribution` de `<TileLayer>` y **siempre
  debe verse**; Leaflet la muestra en la esquina inferior derecha.
- Son servidores gratuitos de uso justo: nada de descargar mosaicos en masa.
- No hay capa de tráfico gratuita, así que el interruptor **Tráfico** del mockup
  queda fuera del alcance.

## Marcadores con el diseño de DESIGN.md

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

## Variables de entorno

```dotenv
OSRM_URL=https://router.project-osrm.org
```

Ninguna otra: los mapas no usan llaves.
