# UNIROUTE

Proyecto escolar UNIROUTE solucionando problemas de transporte

## Base del proyecto

Laravel 13 con el starter oficial de React: React 19, Inertia 3, TypeScript,
Tailwind CSS 4, shadcn/ui y rutas tipadas con Wayfinder. Incluye registro,
inicio de sesión, recuperación de contraseña y configuración de cuenta.

Se conservan los ajustes locales de IDAP para MySQL, sesiones en archivos,
caché en archivos y colas en base de datos. UNIROUTE utiliza su propia base
`uniroute`, clave de aplicación y configuración de entorno.

## Equipo

| Equipo | Área                       | Integrantes                                                                                  |
| ------ | -------------------------- | -------------------------------------------------------------------------------------------- |
| 1      | Administración             | Adaia (flota y conductores), Enrique (rutas y operación)                                     |
| 2      | Experiencia del estudiante | Andrés (diseño y layout), Sosa (mapa y Dashboard en Vivo), Kiroz (rutas, paradas y horarios) |
| 3      | Plataforma                 | Jorge (líder técnico y backend núcleo), Santana (tiempo real y rastreo)                      |

Cada equipo es dueño de sus carpetas; ver [CONTRIBUTING.md](CONTRIBUTING.md).

## Decisiones técnicas

Las propuestas se confirman en la reunión de arranque (S0). Cuando una cambie,
se actualiza esta tabla con la fecha.

| #   | Tema              | Decisión                                                                        | Estado                | Alternativa o nota                                                                             |
| --- | ----------------- | ------------------------------------------------------------------------------- | --------------------- | ---------------------------------------------------------------------------------------------- |
| 1   | Mapa              | Leaflet con `react-leaflet` y mapas de OpenStreetMap; rutas por calles con OSRM | Decidida (2026-11-11) | Google Maps se descartó porque exige un prepago de 600 MXN; ver [docs/mapas.md](docs/mapas.md) |
| 2   | Tiempo real       | Laravel Reverb con `@laravel/echo-react`                                        | Propuesta             | Plan B: `usePoll` de Inertia cada 5 s                                                          |
| 3   | Íconos            | `lucide-react` (ya instalado) con tabla de equivalencias del mockup             | Propuesta             | Material Symbols iguala el mockup, pero carga la fuente completa                               |
| 4   | Origen del GPS    | Simulador (`php artisan uniroute:simular`) y pantalla de conductor              | Propuesta             | El GPS del celular solo funciona con HTTPS                                                     |
| 5   | Radios de esquina | 4, 8 y 12 px, como en los mockups HTML                                          | Propuesta             | `app.css` quedó en 8, 10 y 12 px; ver [docs/DESIGN.md](docs/DESIGN.md#esquinas)                |
| 6   | Tema              | Oscuro por omisión, con la paleta de login y registro; claro a elección         | Decidida (2026-11-12) | Cada quien lo cambia en su menú; ver [docs/DESIGN.md](docs/DESIGN.md#temas-claro-y-oscuro)     |
| 7   | Gráficas          | Recharts                                                                        | Propuesta             | Es la base de los charts de shadcn/ui                                                          |
| 8   | Idioma del código | Tablas, columnas y código en inglés; textos de la interfaz en español           | Propuesta             | Ver [docs/modelo-de-datos.md](docs/modelo-de-datos.md)                                         |

## Instalación local

Requisitos: PHP 8.4 o superior, Composer, MySQL y Node.js 22.18+ de la rama 22
o 24.11+ de la rama 24. Se incluye `.nvmrc` con Node 22.22.0.

1. Crear una base MySQL vacía llamada `uniroute`.
2. Ejecutar `composer install` y `npm install`.
3. Copiar `.env.example` a `.env` y ajustar las credenciales de MySQL.
4. Ejecutar `php artisan key:generate` y `php artisan migrate --seed`.
5. Ejecutar `npm run build`.

Para reiniciar la base con los datos de prueba: `php artisan migrate:fresh --seed`
(borra todo lo que haya en la base local). Los seeders se pueden correr varias
veces con `php artisan db:seed` sin duplicar datos.

### Usuarios de prueba

Todos usan la contraseña definida en `DemoUserSeeder::DEMO_PASSWORD` y ya tienen
el correo verificado.

| Rol           | Correo                                                        |
| ------------- | ------------------------------------------------------------- |
| Administrador | `admin@uniroute.test`                                         |
| Estudiante    | `estudiante@uniroute.test`                                    |
| Conductor     | `conductor@uniroute.test` (y otros tres en `FleetSeeder.php`) |

Las rutas, paradas, unidades y conductores son **provisionales**: los nombres
vienen de los mockups y las coordenadas son aproximadas. Se reemplazan con los
datos reales de S0-05 (Enrique) y S0-06 (Adaia) en `database/seeders/`.

En PowerShell, usar `npm.cmd` si la política de ejecución bloquea `npm.ps1`.
Si el Node predeterminado es 25, usar el Node compatible de Laragon solo
en la terminal actual:

```powershell
$env:Path = 'C:\laragon\bin\nodejs\node-v22;' + $env:Path
node --version
```

## Desarrollo

Ejecutar en terminales separadas:

```sh
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

La aplicación estará en `http://localhost:8000`. Con Laragon, el sitio debe
apuntar a `public/` y `APP_URL` debe coincidir con su dominio local.
El correo se escribe en el registro local hasta configurar un proveedor SMTP.

### Viajes y rastreo

Los viajes del día se generan a partir de los horarios, y eso lo hace el
programador de tareas de Laravel. En local se deja corriendo en otra terminal:

```sh
php artisan schedule:work
```

Corre `uniroute:generar-viajes` a las 00:10 y `uniroute:limpiar-posiciones` a
las 03:00, en hora de Saltillo (`America/Monterrey`). Para no esperar:

```sh
php artisan uniroute:generar-viajes
php artisan uniroute:simular --iniciar --intervalo=5
```

Con `--iniciar`, el simulador arranca un viaje en cada ruta que no tenga uno,
con una unidad y un conductor libres, y mueve solo esas unidades. Cada posición
se transmite al mapa con Reverb (`php artisan reverb:start`); si Reverb no está
corriendo, el mapa se pone al día solo cada 15 segundos. Se detiene con Ctrl+C.

## Verificación

```sh
composer validate --strict
composer test
npm run check
npm run types:check
npm run build
```

Las pruebas usan SQLite en memoria y no modifican la base MySQL local.
El starter actual utiliza Vite+ para formato y lint. Las rutas TypeScript
se generan con `php artisan wayfinder:generate --with-form` o al compilar.

## Documentación del proyecto

- [CONTRIBUTING.md](CONTRIBUTING.md): ramas, PRs, revisión y definición de terminado.
- [docs/modelo-de-datos.md](docs/modelo-de-datos.md): diagrama entidad-relación y
  valores de cada estado.
- [docs/mapas.md](docs/mapas.md): Leaflet, capas de OpenStreetMap y cómo trazar
  rutas por las calles con OSRM.
- [docs/DESIGN.md](docs/DESIGN.md): colores, tipografía, componentes y
  marcadores del mapa, adaptados al tema oscuro. El original de Stitch está en
  [docs/stitch/DESIGN.md](docs/stitch/DESIGN.md).
- [docs/mockups/](docs/mockups/index.html): un mockup por pantalla, con la tarea
  y la persona de cada uno.
- [docs/marca.md](docs/marca.md): logos de UniRoute, cuándo usar cada uno y su
  paleta.
- `resources/js/types/transit.ts`: tipos compartidos del dominio de transporte.

Documentación oficial del starter: https://laravel.com/docs/13.x/starter-kits
