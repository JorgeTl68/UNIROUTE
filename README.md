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

| Equipo | Área                       | Integrantes                                                                                         |
| ------ | -------------------------- | --------------------------------------------------------------------------------------------------- |
| 1      | Administración             | Adaia (flota y conductores), Enrique (rutas y operación)                                            |
| 2      | Experiencia del estudiante | Andrés (diseño y layout), Sosa (Google Maps y Dashboard en Vivo), Kiroz (rutas, paradas y horarios) |
| 3      | Plataforma                 | Jorge (líder técnico y backend núcleo), Santana (tiempo real y rastreo)                             |

Cada equipo es dueño de sus carpetas; ver [CONTRIBUTING.md](CONTRIBUTING.md).

## Decisiones técnicas

Las propuestas se confirman en la reunión de arranque (S0). Cuando una cambie,
se actualiza esta tabla con la fecha.

| #   | Tema              | Decisión                                                                                | Estado                | Alternativa o nota                                                          |
| --- | ----------------- | --------------------------------------------------------------------------------------- | --------------------- | --------------------------------------------------------------------------- |
| 1   | Mapa              | Google Maps Platform con `@vis.gl/react-google-maps` (Maps JavaScript API y Routes API) | Decidida (2026-11-10) | Requiere facturación activa; ver [docs/google-maps.md](docs/google-maps.md) |
| 2   | Tiempo real       | Laravel Reverb con `@laravel/echo-react`                                                | Propuesta             | Plan B: `usePoll` de Inertia cada 5 s                                       |
| 3   | Íconos            | `lucide-react` (ya instalado) con tabla de equivalencias del mockup                     | Propuesta             | Material Symbols iguala el mockup, pero carga la fuente completa            |
| 4   | Origen del GPS    | Simulador (`php artisan uniroute:simular`) y pantalla de conductor                      | Propuesta             | El GPS del celular solo funciona con HTTPS                                  |
| 5   | Radios de esquina | 4, 8 y 12 px, como en los mockups HTML                                                  | Propuesta             | DESIGN.md trae otras dos escalas distintas                                  |
| 6   | Tema              | Solo modo claro en la v1                                                                | Propuesta             | DESIGN.md no define colores oscuros                                         |
| 7   | Gráficas          | Recharts                                                                                | Propuesta             | Es la base de los charts de shadcn/ui                                       |
| 8   | Idioma del código | Tablas, columnas y código en inglés; textos de la interfaz en español                   | Propuesta             | Ver [docs/modelo-de-datos.md](docs/modelo-de-datos.md)                      |

## Instalación local

Requisitos: PHP 8.4 o superior, Composer, MySQL y Node.js 22.18+ de la rama 22
o 24.11+ de la rama 24. Se incluye `.nvmrc` con Node 22.22.0.

1. Crear una base MySQL vacía llamada `uniroute`.
2. Ejecutar `composer install` y `npm install`.
3. Copiar `.env.example` a `.env`, ajustar las credenciales de MySQL y
   agregar las llaves de Google Maps (ver [docs/google-maps.md](docs/google-maps.md)).
4. Ejecutar `php artisan key:generate` y `php artisan migrate`.
5. Ejecutar `npm run build`.

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
- [docs/google-maps.md](docs/google-maps.md): configuración de Google Cloud,
  llaves y control de costos.
- `resources/js/types/transit.ts`: tipos compartidos del dominio de transporte.

Documentación oficial del starter: https://laravel.com/docs/13.x/starter-kits
