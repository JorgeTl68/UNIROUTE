# Google Maps

UniRoute usa Google Maps Platform para el mapa (Maps JavaScript API) y para
trazar las rutas por las calles (Routes API). Esta guía la sigue Jorge una vez
(S0-04); el resto del equipo solo necesita la sección "Configurar tu `.env`".

## Lista de S0-04

- [ ] Proyecto `uniroute` creado en Google Cloud.
- [ ] Cuenta de facturación vinculada.
- [ ] Maps JavaScript API y Routes API habilitadas.
- [ ] Llave del navegador creada y restringida.
- [ ] Llave del servidor creada y restringida.
- [ ] Map ID creado, con el estilo de UniRoute.
- [ ] Presupuesto con alertas configurado.
- [ ] Cuotas diarias configuradas.
- [ ] Llaves compartidas por mensaje privado con el equipo.
- [ ] Prueba de la llave del servidor (ver "Verificar").

## 1. Proyecto y facturación

1. Entra a [Google Cloud Console](https://console.cloud.google.com/) con la
   cuenta que administrará el proyecto.
2. Crea un proyecto llamado `uniroute`.
3. En **Facturación** (Billing), vincula una cuenta de facturación. Google pide
   tarjeta aunque haya un uso gratuito mensual; sin facturación, el mapa se ve
   oscurecido con la marca "For development purposes only".

## 2. Habilitar las APIs

En **APIs y servicios → Biblioteca** (APIs & Services → Library), habilita:

| API                   | Para qué                                                             |
| --------------------- | -------------------------------------------------------------------- |
| Maps JavaScript API   | Mostrar el mapa en Dashboard en Vivo, Detalle de ruta y Crear ruta   |
| Routes API            | Trazar la ruta por calles al crearla; se calcula una vez y se guarda |
| Places API (opcional) | Autocompletar direcciones al dar de alta paradas                     |

## 3. Llaves

En **APIs y servicios → Credenciales → Crear credenciales → Clave de API**.

### Llave del navegador → `VITE_GOOGLE_MAPS_API_KEY`

Todo lo que empieza con `VITE_` termina en el JavaScript que descarga el
navegador, así que esta llave es visible. Por eso se restringe:

- **Restricción de aplicación:** Sitios web (HTTP referrers):
    - `http://localhost:8000/*`
    - `http://127.0.0.1:8000/*`
    - `http://uniroute.test/*`
    - `https://uniroute.test/*`
    - El dominio de la demo, cuando exista.
- **Restricción de API:** solo Maps JavaScript API (y Places API si se usa).

### Llave del servidor → `GOOGLE_MAPS_SERVER_KEY`

Solo la usa Laravel para llamar a Routes API; nunca llega al navegador.

- **Restricción de API:** solo Routes API.
- **Restricción de aplicación:** ninguna mientras se trabaja en local (la IP de
  cada casa cambia). Cuando haya servidor de demo, restringirla a su IP.

## 4. Map ID y estilo

1. En **Google Maps Platform → Administración de mapas** (Map management), crea
   un ID de mapa de tipo **JavaScript**, renderizado **vectorial**.
2. En **Estilos de mapa** (Map styles), crea un estilo con los colores del
   mockup del Dashboard y asócialo al Map ID:

    | Elemento          | Color     |
    | ----------------- | --------- |
    | Fondo del mapa    | `#f2f5f8` |
    | Calles            | `#ffffff` |
    | Bordes de calles  | `#e2e8f0` |
    | Agua              | `#d5e3f0` |
    | Texto             | `#44474d` |
    | Puntos de interés | ocultos   |

3. El Map ID va en `VITE_GOOGLE_MAPS_MAP_ID`. Lo exigen los Advanced Markers que
   usamos para paradas y unidades.

## 5. Control de costos

- **Presupuesto:** en **Facturación → Presupuestos y alertas**, crea un
  presupuesto bajo (por ejemplo 5 USD) con alertas al 50 %, 90 % y 100 %. Las
  alertas avisan por correo, pero no detienen los cobros.
- **Cuotas:** lo que sí pone un tope. En **APIs y servicios**, abre cada API →
  **Cuotas** y baja el límite por día. Punto de partida sugerido:
    - Maps JavaScript API: 1,000 cargas de mapa por día.
    - Routes API: 200 solicitudes por día.
- En el código: el mapa se carga solo en las pantallas que lo usan, y la ruta
  calculada con Routes API se guarda en `routes.encoded_polyline` para no volver
  a pedirla.

## 6. Configurar tu `.env`

Jorge comparte las llaves por mensaje privado. Nunca en el grupo, en un issue ni
en el repositorio. Cada quien las pega en su `.env`:

```dotenv
GOOGLE_MAPS_SERVER_KEY=llave-del-servidor
VITE_GOOGLE_MAPS_API_KEY=llave-del-navegador
VITE_GOOGLE_MAPS_MAP_ID=id-del-mapa
```

Después de cambiar una variable `VITE_`, detén y vuelve a iniciar `npm run dev`.

En el código:

- Frontend: `import.meta.env.VITE_GOOGLE_MAPS_API_KEY` y
  `import.meta.env.VITE_GOOGLE_MAPS_MAP_ID` (tipadas en
  `resources/js/types/vite-env.d.ts`).
- Backend: `config('services.google_maps.server_key')`.

## 7. Verificar

### Llave del servidor

Desde Git Bash, en la carpeta del proyecto (calcula la ruta del centro de
Saltillo a Ramos Arizpe):

```bash
KEY=$(grep '^GOOGLE_MAPS_SERVER_KEY=' .env | cut -d= -f2)
curl -s -X POST 'https://routes.googleapis.com/directions/v2:computeRoutes' \
  -H 'Content-Type: application/json' \
  -H "X-Goog-Api-Key: $KEY" \
  -H 'X-Goog-FieldMask: routes.distanceMeters,routes.duration' \
  -d '{"origin":{"location":{"latLng":{"latitude":25.4232,"longitude":-101.0053}}},"destination":{"location":{"latLng":{"latitude":25.5394,"longitude":-100.9472}}},"travelMode":"DRIVE"}'
```

Si responde con `distanceMeters` y `duration`, la llave funciona.

### Llave del navegador

La prueba Sosa en S0-08 al mostrar el primer mapa. Si no carga, la consola del
navegador indica la causa:

| Error en la consola         | Qué falta                                        |
| --------------------------- | ------------------------------------------------ |
| `ApiNotActivatedMapError`   | Habilitar Maps JavaScript API en el proyecto     |
| `BillingNotEnabledMapError` | Vincular la cuenta de facturación                |
| `RefererNotAllowedMapError` | Agregar el sitio a las restricciones de la llave |
| `InvalidKeyMapError`        | Revisar que la llave esté bien copiada en `.env` |

## Si una llave se filtra

1. Avisar a Jorge de inmediato.
2. En **Credenciales**, regenerar la llave (o borrarla y crear otra).
3. Compartir la nueva por mensaje privado y actualizar el `.env` de cada quien.
