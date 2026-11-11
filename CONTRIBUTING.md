# Cómo trabajamos en UniRoute

Guía para los 3 equipos. Si algo aquí ya no se cumple, se corrige en un PR.

## Preparar tu máquina

1. Sigue la sección "Instalación local" del [README](README.md).
2. Usa Node 22 (`.nvmrc`). Con Node 25, `npm` falla.
3. Pide a Jorge las llaves de Google Maps y ponlas solo en tu `.env`
   (ver [docs/google-maps.md](docs/google-maps.md)).

## Ramas

- `main` está protegida: solo entra código por PR con 1 aprobación y el CI en verde.
- Crea tu rama desde `main` actualizada:
    - `feat/<modulo>-<descripcion>` para funciones nuevas, por ejemplo
      `feat/admin-registrar-unidad`.
    - `fix/<descripcion>` para correcciones, por ejemplo `fix/eta-negativo`.
    - `docs/<descripcion>` para documentación.
- Antes de abrir el PR, trae los cambios de `main` a tu rama
  (`git pull origin main`) y resuelve conflictos en tu máquina.

## Commits

- En español, en presente y describiendo el cambio: `Agrega tabla de unidades al panel`.
- Un commit por cambio lógico. Nada de "cambios" o "avances".

## Pull requests

- Pequeños: 400 líneas o menos. Si crece, divídelo.
- Al menos uno por persona cada 2 días, aunque sea parcial (márcalo como borrador).
- Llena la plantilla: actividad del plan (por ejemplo `S1-07`), cómo probarlo y capturas.
- Una vez por sprint, revisa un PR de otro equipo para conocer todo el código.

## Dueños por carpeta

Para que los PRs no choquen, cada quien cambia sus carpetas. Si necesitas tocar
algo de otra persona, avísale antes.

| Carpeta o archivo                                                  | Responsable            |
| ------------------------------------------------------------------ | ---------------------- |
| `database/`, `app/Models/`, `app/Policies/`                        | Jorge                  |
| `app/Services/Tracking/`, `app/Events/`, `routes/channels.php`     | Santana                |
| `resources/css/app.css`, `resources/js/components/`, `layouts/`    | Andrés                 |
| `resources/js/components/transit/map/`, `pages/live/`              | Sosa                   |
| `pages/routes/`, `pages/schedules/`, `routes/transit.php`, `lang/` | Kiroz                  |
| `pages/admin/fleet/`, `app/Http/Controllers/Admin/`                | Adaia                  |
| `pages/admin/routes/`, `routes/admin.php`                          | Enrique                |
| `resources/js/types/transit.ts`                                    | Jorge (cambios por PR) |

## Migraciones

- Jorge crea las migraciones del núcleo en S1.
- Después, cada quien crea las de sus tablas y avisa en el grupo antes de subirlas.
- Nunca edites una migración que ya está en `main`: crea una nueva.

## Secretos

- `.env` nunca se sube. Las llaves de Google van solo ahí.
- Si subes una llave por error, avisa a Jorge de inmediato para rotarla.

## Definición de terminado

Una tarea está terminada cuando:

- Usa los tokens y componentes del sistema de diseño, sin colores sueltos.
- Sus datos vienen de la base de datos, no del mockup.
- Tiene pruebas de feature, incluidos los permisos por rol.
- `composer test`, `npm run check` y `npm run types:check` pasan y el CI está en verde.
- Otra persona la revisó en el PR.
- Se ve bien a 375, 768 y 1280 px.
- Todos los textos de la interfaz están en español.

## Rutina

- Reunión diaria de 10 minutos por equipo: qué hice, qué haré y qué me bloquea.
- Reunión general al cierre de cada sprint con la demo de los tres equipos.
- Tablero en GitHub Projects: Por hacer, En progreso, En revisión y Hecho.
