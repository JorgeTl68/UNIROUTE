# Diseño de UniRoute

> El DESIGN.md original es el que exportó Stitch junto con los primeros
> mockups: está en [stitch/DESIGN.md](stitch/DESIGN.md), en inglés y en tema
> claro. Este documento lo adapta a lo que decidió el equipo (toda la app en
> oscuro y los nombres de `app.css`) y a los mockups de
> [docs/mockups](mockups/index.html).

## Mockups

Hay un mockup por pantalla. [docs/mockups/index.html](mockups/index.html) los
lista con la tarea y la persona de cada uno.

- **Estudiante:** Dashboard en Vivo, Detalle de ruta, Horarios, Notificaciones,
  Perfil y Reportar emergencia.
- **Inicio y acceso:** página de inicio, login, registro y recuperar contraseña
  (con verificar correo, confirmar contraseña y código de dos pasos).
- **Conductor:** pantalla para el celular.
- **Administración:** consola de flota, registrar unidad, conductores,
  mantenimiento, rutas y paradas, crear ruta, horarios de salida, incidencias y
  reportes de aforo.

Cómo están hechos:

- Todos en oscuro. La base compartida está en `mockups/assets/uniroute.js`:
  configura Tailwind con los nombres de `app.css`, así que las clases se copian
  tal cual, y dibuja el layout y un mapa ilustrado. Ese archivo no se copia a la
  app.
- Íconos de lucide con el mismo nombre que en `lucide-react` (`bus-front` →
  `BusFront`).
- Los datos son de ejemplo, tomados de los seeders. Cada archivo trae al inicio
  sus notas para implementarlo.
- Login y registro se hicieron antes y usan los nombres de Material 3 y
  Material Symbols; la equivalencia está en
  [Tokens en app.css](#tokens-en-appcss) e [Íconos](#íconos).
- Para verlos: desde la carpeta del proyecto, `python -m http.server 8765` y
  abrir `http://localhost:8765/docs/mockups/`.

## Idea general

- Es transporte universitario: se consulta rápido, de pie y desde el celular.
  Datos grandes (hora de llegada, minutos) y textos cortos.
- Azul nocturno como base, dorado champán como acento de marca y verde
  señalización para lo que está en vivo o ya pasó.
- La app va en blanco con detalles azules, dorados y verdes; la bienvenida, el
  login y el registro van en oscuro, y cada quien puede pasar toda la app a
  oscuro (ver [Temas claro y oscuro](#temas-claro-y-oscuro)).

## Color

Los nombres de esta sección siguen el esquema de Material 3 del DESIGN.md
original y de los mockups de login y registro. `on-X` es el color del texto que
va encima de `X`. Los mockups nuevos usan los nombres de `app.css` (ver
[Tokens en app.css](#tokens-en-appcss)).

### Azules (base)

| Token                  | Hex       | Uso                                                                         |
| ---------------------- | --------- | --------------------------------------------------------------------------- |
| `primary`              | `#020e24` | Azul nocturno. Fondo de login y registro; títulos y cifras en tema claro    |
| `primary-container`    | `#17243a` | Azul institucional. Paneles y campos en oscuro, botones y tarjeta destacada |
| `on-primary`           | `#ffffff` | Texto sobre los azules                                                      |
| `on-primary-container` | `#7e8ba6` | Placeholders, íconos de campos y texto de menor peso en oscuro              |
| `primary-fixed`        | `#d6e3ff` | Etiquetas de campos en oscuro                                               |
| `primary-fixed-dim`    | `#bac7e4` | Texto secundario en oscuro                                                  |
| `inverse-on-surface`   | `#ecf1f9` | Texto principal en oscuro, incluido lo que se escribe en los campos         |

### Dorados (acento)

| Token                       | Hex       | Uso                                                                         |
| --------------------------- | --------- | --------------------------------------------------------------------------- |
| `brand-gold` / `focus-ring` | `#b69b72` | Dorado champán del logo. Insignia de ruta, siguiente parada, anillo de foco |
| `secondary`                 | `#715b37` | Dorado para texto sobre fondo claro: minutos de llegada, "Siguiente parada" |
| `secondary-fixed`           | `#fddeb1` | Botón principal en oscuro, marcador de la unidad, íconos de acento          |
| `secondary-fixed-dim`       | `#e0c297` | Hover del botón principal, enlaces en oscuro, casilla marcada               |

### Verdes (en vivo)

| Token                   | Hex       | Uso                                                                              |
| ----------------------- | --------- | -------------------------------------------------------------------------------- |
| `brand-green`           | `#84cc16` | Verde señalización del logo. Línea de la ruta, paradas ya pasadas, punto en vivo |
| `tertiary-fixed-dim`    | `#91db2a` | El mismo verde sobre fondo oscuro: "Servicio en tiempo real", íconos             |
| `on-tertiary-container` | `#3f6e00` | Verde para texto sobre fondo claro ("Ya pasó") y halo de la línea de la ruta     |

### Superficies y texto (tema claro)

Vienen del DESIGN.md original y del demo del mapa. Son la base del tema claro
(ver [Temas claro y oscuro](#temas-claro-y-oscuro)).

| Token                      | Hex       | Uso                                                 |
| -------------------------- | --------- | --------------------------------------------------- |
| `surface`                  | `#f7f9ff` | Fondo de la página                                  |
| `surface-container-lowest` | `#ffffff` | Paneles, tarjetas flotantes del mapa, selects       |
| `surface-container-low`    | `#eff4fc` | Tarjetas dentro de un panel, chips                  |
| `surface-container`        | `#e9eef6` | Definido, sin uso todavía en los mockups            |
| `surface-container-high`   | `#e3e9f0` | Fondo de la barra de progreso, paradas pendientes   |
| `surface-variant`          | `#dde3eb` | Bordes y líneas divisorias                          |
| `on-surface`               | `#161c22` | Texto principal                                     |
| `on-surface-variant`       | `#44474d` | Texto secundario                                    |
| `outline`                  | `#75777e` | Etiquetas pequeñas en mayúsculas ("Próxima parada") |

### Errores

| Token             | Hex       | Uso                                                  |
| ----------------- | --------- | ---------------------------------------------------- |
| `error`           | `#ba1a1a` | Texto e íconos de error en tema claro                |
| `error-container` | `#ffdad6` | Fondo de los avisos de retraso (al 60 %)             |
| `error-soft`      | `#ffb4ab` | Errores sobre fondo oscuro: mensaje y borde de campo |

### Solo pantallas oscuras

| Token                 | Valor                 | Uso                                                              |
| --------------------- | --------------------- | ---------------------------------------------------------------- |
| `night-border`        | `#2f3e57`             | Bordes de campos y del botón secundario                          |
| `white/5`, `white/10` | Blanco al 5 y al 10 % | Fondos de íconos, botón secundario, separadores, bordes de panel |

`night-border` y `error-soft` se agregaron en los mockups de login y registro; el
DESIGN.md original no los traía.

### Contraste

Medido con la fórmula de WCAG; el mínimo para texto normal es 4.5:1.

- **Texto dorado sobre claro:** `secondary` (6.4:1), nunca `brand-gold` (2.6:1).
- **Texto verde sobre claro:** `on-tertiary-container` (6.1:1), nunca
  `brand-green` (2:1).
- **Texto sobre relleno dorado o verde:** `primary-container` (5.9:1 sobre
  dorado, 7.9:1 sobre verde), no blanco. El círculo de la siguiente parada del
  demo lleva el número en blanco sobre dorado (2.6:1); corregirlo al pasarlo a la
  app.
- **Etiquetas de 11 px en `outline`:** quedan en 4.5:1 sobre blanco y 4:1 sobre
  `surface-container-low`. Si van sobre tarjeta, usar `on-surface-variant`.
- En oscuro todo pasa: el placeholder (`on-primary-container` sobre
  `primary-container`) es el más justo, con 4.5:1.

## Tipografía

Inter en toda la app, con pesos 400, 500, 600, 700 y 800. Ya está cargada en
`vite.config.ts` y puesta en `app.css` (PR #7).

| Token         | Tamaño / interlínea | Peso | Espaciado | Uso                                           |
| ------------- | ------------------- | ---- | --------- | --------------------------------------------- |
| `headline-xl` | 40 / 48 px          | 700  | -0.02em   | Título del panel de bienvenida                |
| `headline-md` | 24 / 32 px          | 600  | —         | Título de la pantalla ("Inicia sesión")       |
| `headline-sm` | 20 / 28 px          | 600  | —         | "UniRoute" junto al ícono, títulos de sección |
| `body-lg`     | 16 / 24 px          | 400  | —         | Párrafo de introducción                       |
| `body-md`     | 14 / 20 px          | 400  | —         | Texto normal y campos                         |
| `label-md`    | 13 / 18 px          | 500  | 0.01em    | Etiquetas de campos y botones                 |
| `label-sm`    | 12 / 16 px          | 500  | 0.02em    | Mensajes de error, enlaces pequeños, chips    |
| `caption`     | 11 / 14 px          | 600  | 0.04em    | Pie de página y etiquetas en mayúsculas       |

- Títulos, etiquetas y botones suben el peso con `font-semibold` o `font-bold`
  encima del token.
- Horas, minutos y contadores llevan `tabular-nums` para que no brinquen al
  cambiar.
- Etiquetas cortas en mayúsculas: 11 px, `uppercase` y `tracking-wider`.
- El demo del mapa usa las medidas normales de Tailwind (`text-xs`, `text-sm`,
  `text-2xl`). Al pasarlo a la app se cambian por el token del mismo tamaño.

## Espaciado

| Token            | Valor | Uso                                               |
| ---------------- | ----- | ------------------------------------------------- |
| `space-xs`       | 4 px  | Entre ícono y texto dentro de un botón            |
| `space-sm`       | 8 px  | Entre ícono y texto, entre elementos de una lista |
| `space-md`       | 16 px | Entre campos de un formulario                     |
| `space-lg`       | 24 px | Entre bloques de un panel                         |
| `space-xl`       | 32 px | Entre secciones de una pantalla                   |
| `margin-desktop` | 32 px | Margen interior de los paneles en escritorio      |

En celular el margen lateral es de 16 px (`px-4`) y sube a 32 px desde `sm`
(`sm:px-8`). Como todos son múltiplos de 4 px, en la app basta la escala normal de
Tailwind (`1`, `2`, `4`, `6`, `8`); no hace falta crear tokens nuevos.

## Esquinas

| Clase          | Radio | Uso                                                  |
| -------------- | ----- | ---------------------------------------------------- |
| `rounded`      | 4 px  | Insignias de ruta y turno, casillas                  |
| `rounded-lg`   | 8 px  | Campos, botones, cuadros de ícono, tarjetas pequeñas |
| `rounded-xl`   | 12 px | Tarjetas, avisos, controles flotantes del mapa       |
| `rounded-full` | —     | Chips, puntos de estado, paradas, barra de progreso  |

La única excepción es la tarjeta flotante de "Próxima parada" sobre el mapa, con
16 px (`rounded-2xl`).

En `app.css` quedó `--radius: 0.75rem`, que da otra escala (ver
[Pendientes de decidir](#pendientes-de-decidir)).

## Sombras

| Dónde                       | Sombra                                              |
| --------------------------- | --------------------------------------------------- |
| Botón principal en oscuro   | `shadow-lg shadow-black/30`                         |
| Aviso (toast)               | `shadow-xl shadow-black/40`                         |
| Panel lateral en claro      | `shadow-[0_1px_8px_rgba(0,0,0,0.06)]`               |
| Control de capas del mapa   | `shadow-md`                                         |
| Tarjeta flotante del mapa   | `shadow-xl`, fondo blanco al 95 % y `backdrop-blur` |
| Paradas y unidad en el mapa | Azul nocturno: `0 4px 10px rgba(2, 14, 36, 0.35)`   |

Dentro de un panel claro las tarjetas no llevan sombra: se separan con el fondo
`surface-container-low`.

## Íconos

Los mockups nuevos ya usan lucide, con el nombre del ícono en kebab-case
(`bus-front` es `BusFront`). Login y registro usan Material Symbols Outlined:
18 px en campos y botones, 20 a 24 px en los cuadros de ícono y 16 px en los
mensajes de error. Estas son sus equivalencias en `lucide-react`, todas existen
en la versión instalada:

| Material Symbols                | `lucide-react`   | Dónde                                     |
| ------------------------------- | ---------------- | ----------------------------------------- |
| `directions_bus`                | `BusFront`       | Logo con texto, marcador de la unidad     |
| `fmd_good`                      | `MapPin`         | Beneficios del panel de bienvenida        |
| `notifications_active`          | `BellRing`       | Beneficios del panel de bienvenida        |
| `schedule`                      | `Clock`          | Horarios, aviso de retraso                |
| `mail`                          | `Mail`           | Campo de correo                           |
| `mark_email_read`               | `MailCheck`      | Aviso de correo de confirmación           |
| `lock`                          | `Lock`           | Campos de contraseña                      |
| `visibility` / `visibility_off` | `Eye` / `EyeOff` | Mostrar u ocultar la contraseña           |
| `login`                         | `LogIn`          | Botón "Iniciar sesión"                    |
| `passkey`                       | `KeyRound`       | Botón "Entrar con passkey"                |
| `person`                        | `User`           | Campo de nombre                           |
| `person_add`                    | `UserPlus`       | Botón "Crear cuenta"                      |
| `badge`                         | `IdCard`         | Campo de matrícula                        |
| `location_city`                 | `Building2`      | Campo de campus                           |
| `expand_more`                   | `ChevronDown`    | Flecha del select                         |
| `progress_activity`             | `LoaderCircle`   | Botón mientras envía (con `animate-spin`) |
| `error`                         | `CircleAlert`    | Mensajes de error                         |
| `check_circle`                  | `CircleCheck`    | Aviso de éxito                            |
| `pause` / `play_arrow`          | `Pause` / `Play` | Control de la simulación del demo         |

El autobús del marcador de la unidad va relleno; con lucide se logra con
`fill="currentColor"`.

## Componentes

Las clases de Botones, Campos y Tarjetas son las de login, registro y el demo
del mapa (nombres de Material 3). Los mockups nuevos usan su equivalente de
`app.css`; por ejemplo, el botón principal es
`bg-primary text-primary-foreground hover:bg-primary-hover`.

### Botones

| Variante             | Dónde                  | Clases                                                                                                                                           |
| -------------------- | ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| Principal en oscuro  | Login y registro       | `bg-secondary-fixed hover:bg-secondary-fixed-dim text-primary-container rounded-lg py-3 font-bold shadow-lg shadow-black/30 active:scale-[0.98]` |
| Secundario en oscuro | "Entrar con passkey"   | `border border-night-border bg-white/5 hover:bg-white/10 text-inverse-on-surface rounded-lg py-2.5 font-semibold`                                |
| De ícono en claro    | Pausa de la simulación | `bg-primary-container hover:bg-primary text-on-primary size-10 rounded-lg`                                                                       |
| Selector de opciones | Capas del mapa         | Contenedor `bg-surface-container-lowest/95 rounded-xl p-1`; opción activa `bg-primary text-on-primary`, inactiva `text-on-surface-variant`       |

- Texto del botón en `label-md` con ícono de 18 px a la izquierda.
- Mientras envía: el botón se desactiva, el texto cambia a "Entrando…" o
  "Creando cuenta…" y el ícono se vuelve un círculo que gira.
- Enlaces en oscuro: `text-secondary-fixed-dim hover:text-secondary-fixed
  hover:underline`, en negritas.

### Campos de texto (oscuro)

- Etiqueta arriba: `label-md` en `font-semibold` y `text-primary-fixed`, 6 px
  antes del campo. Si hay un enlace ("¿Olvidaste tu contraseña?"), va a la
  derecha de la etiqueta.
- Campo: `bg-primary-container border border-night-border rounded-lg py-2.5`,
  texto `body-md` en `text-inverse-on-surface` y placeholder en
  `text-on-primary-container`.
- Ícono a la izquierda de 18 px en `text-on-primary-container`, con `pl-10`.
- Foco: `focus:border-focus-ring focus:ring-2 focus:ring-focus-ring/50`.
- Error: el borde cambia a `border-error-soft` y debajo aparece el mensaje en
  `label-sm` y `text-error-soft` con ícono de 16 px. El campo lleva
  `aria-invalid` y `aria-describedby` apuntando al mensaje (componente
  `InputError`).
- Casilla ("Recordarme"): 16 px con `accent-[#e0c297]`.

Los formularios de administración usan estos mismos campos.

### Tarjetas e insignias (claro)

- **Tarjeta destacada de la ruta:** `bg-primary-container text-on-primary
  rounded-xl p-4`. Las cifras van en cuadros `bg-white/10 rounded-lg` con el
  número en `font-extrabold` y una etiqueta de 10 px en mayúsculas al 70 %.
- **Tarjeta normal:** `bg-surface-container-low rounded-xl p-3`.
- **Aviso de retraso:** `bg-error-container/60 rounded-xl p-3` con ícono en
  `text-error` y texto de 13 px en `font-semibold`.
- **Insignia de ruta:** `bg-brand-gold text-primary-container rounded px-2
  py-0.5`, 11 px, `font-extrabold`, mayúsculas.
- **Insignia sobre azul:** `bg-white/10 rounded px-2 py-0.5`, 11 px,
  `font-semibold`.
- **Chip en vivo:** `rounded-full` con un punto verde de 6 a 8 px que pulsa.
- **Barra de progreso:** `h-2 rounded-full bg-surface-container-high`, rellena
  con un degradado `from-brand-green to-brand-gold`.

### Aviso (toast)

`bg-primary-container border border-night-border text-on-primary rounded-xl px-4
py-3 shadow-xl shadow-black/40`, abajo al centro, con ícono verde de 18 px.
Lleva `role="status"` y `aria-live="polite"`.

### Estados y ocupación

Los chips de estado (StatusChip, S1-06) son
`rounded-full px-2 py-0.5 text-[11px] font-bold`, a veces con un punto de 6 px:

| Estado                          | Clases                               |
| ------------------------------- | ------------------------------------ |
| En vivo, A tiempo, En ruta      | `bg-live/15 text-live`               |
| Demorado, Sin asignar           | `bg-warning/15 text-warning`         |
| Mantenimiento, Crítica          | `bg-destructive/15 text-destructive` |
| Programado, En espera, Descanso | `bg-secondary text-muted-foreground` |

Sobre `bg-card` todos pasan de 6.5:1 de contraste.

La barra de ocupación (OccupancyBar) es `h-1.5 rounded-full bg-secondary` y su
relleno cambia de color: `bg-live` con menos de 50 %, `bg-gold` de 50 a 84 % y
`bg-destructive` desde 85 % (viaje saturado).

## Mapa

La capa es OpenStreetMap (ver [mapas.md](mapas.md)) con un filtro para que se vea
gris claro y resalten la ruta y los marcadores:

```css
.osm-claro {
    filter: grayscale(0.85) brightness(1.06) contrast(0.92);
}
```

El fondo mientras cargan los mosaicos es `#f2f5f8`.

| Elemento         | Diseño                                                                                                     |
| ---------------- | ---------------------------------------------------------------------------------------------------------- |
| Línea de la ruta | `#84cc16` de 6 px sobre un halo `#3f6e00` de 11 px al 35 %                                                 |
| Tramo recorrido  | `#17243a` de 6 px al 90 %                                                                                  |
| Parada pendiente | Círculo de 30 px `#17243a`, borde blanco de 3 px, número blanco de 13 px en `font-extrabold`               |
| Parada ya pasada | Igual, en `#84cc16` con el número en `#17243a`                                                             |
| Siguiente parada | Igual, en `#b69b72` con halo dorado de 6 px al 35 % y el número en `#17243a` (ver [Contraste](#contraste)) |
| Destino (UTC)    | Gota de 38 px en `#84cc16` con borde blanco y el texto "UTC" en `#17243a`                                  |
| Unidad           | Cuadro de 44 px con esquinas de 12 px, fondo `#fddeb1`, borde `#17243a` de 3 px y autobús relleno          |
| Rumbo            | Flecha `#17243a` arriba de la unidad que gira con `heading`                                                |
| Nombre de unidad | Etiqueta "UNIDAD 01" debajo: fondo `#17243a`, texto `#fddeb1` de 10 px, `rounded-full`                     |
| Unidad en vivo   | Anillo verde al 28 % que pulsa cada 1.8 s                                                                  |

La lista de paradas del panel usa los mismos tres estados: punto de 30 px con el
color de la parada, tarjeta en `surface-container-low` (la siguiente en
`secondary-fixed/50`) y una línea de estado: "Ya pasó" en
`on-tertiary-container` o "Siguiente parada" en `secondary`.

### En oscuro

Así lo dibujan los mockups nuevos (`UR.mapa` en `mockups/assets/uniroute.js`):

| Elemento         | Diseño                                                                         |
| ---------------- | ------------------------------------------------------------------------------ |
| Fondo y calles   | `#0a1830` con calles `#15253f` y avenidas `#1b2c4a` y `#22355a`                |
| Línea de la ruta | `route` (`#a3e635`) de 6 px sobre un halo `#3f6e00` de 13 px al 55 %           |
| Tramo recorrido  | La misma línea, oscurecida con `#020e24` al 55 %                               |
| Paradas          | Pendiente en `card` con borde claro; ya pasada en `route`; siguiente en `gold` |
| Unidad           | Cuadro con esquinas de 9 px en `primary`, borde `background` y anillo verde    |
| Otras rutas      | Su color de ruta al 45 %, más delgadas                                         |
| Usuario          | Punto `foreground` con halo                                                    |

Con Leaflet falta oscurecer la capa de OpenStreetMap (ver
[Pendientes de decidir](#pendientes-de-decidir)).

## Pantallas

### Layout de la app

- Sidebar fija de 288 px (`w-72`, `bg-card`) desde `lg`; en celular se abre
  como cajón con el botón del header. Arriba va el logo y el estado de la red;
  luego las 6 secciones, con la activa en `bg-primary` (Panel de Administración
  solo para admin); abajo, "Soporte en Ruta" con el botón de emergencia.
- Header de 64 px pegado arriba: campus, búsqueda (`Ctrl K`), unidades en
  servicio, campana con el centro de notificaciones (S3-08) y usuario.
- En celular (menos de 768 px), los estudiantes tienen una barra inferior con
  Mapa, Rutas, Horarios, Alertas y Perfil (S4-07).
- Las páginas de administración comparten una barra de pestañas: Unidades,
  Conductores, Mantenimiento, Rutas y paradas, Horarios de salida, Incidencias
  y Reportes de aforo.

### Login y registro

- Dos columnas desde `lg` (1024 px). A la izquierda, un panel
  `primary-container` (50 % del ancho, 55 % desde `xl`) con un mapa dibujado,
  el logo, el título, tres beneficios y el nombre de la universidad abajo. A la
  derecha, el formulario sobre `primary`, de 380 px de ancho como máximo.
- En celular desaparece el panel izquierdo y el logo pasa arriba del formulario.
- El mockup dibuja el logo con un ícono y texto; en la app va el logo vertical
  oscuro (ver [marca.md](marca.md)).
- En `main` ya está armada en `auth-transit-layout.tsx` (PR #7). Las notas para
  implementarla están en el comentario al inicio de
  [login.html](mockups/login.html).

### Dashboard en Vivo

- Escritorio: lista de rutas de 400 px a la izquierda y el mapa en el resto.
- Celular: el mapa arriba (55 % de la altura de la pantalla) y la lista debajo
  como hoja deslizable, con la llegada de tu unidad primero.
- Sobre el mapa: Mapa / Satélite y capas arriba a la derecha, zoom y "mi
  ubicación" abajo, y desde 1280 px la tarjeta de llegada abajo a la izquierda.

## Temas claro y oscuro

Desde el 2026-11-12 la app abre en **claro** (decisión 6 del README) y el
inicio —bienvenida, login, registro y las demás pantallas de cuenta— va siempre
en **oscuro**, con la paleta de login y registro. Cada quien puede elegir
**Oscuro**, **Claro** o **Como el sistema** en el menú de su cuenta (y en el de
Modo conductor).

- En `app.css`, `:root` lleva el tema claro y `.dark` el oscuro. La preferencia
  se guarda en la cookie `appearance` y `app.blade.php` pone la clase `.dark`
  desde el servidor, así que la página no parpadea al cargar.
- El claro es blanco con detalles azules, dorados y verdes. El botón
  principal y lo seleccionado van en `primary-container` (`#17243a`) con texto
  blanco. El dorado (`#8a6a2f`) y el verde (`#4d7c0f`) son versiones más
  oscuras de los del logo para que el texto se lea sobre blanco (5:1); la línea
  de la ruta y las paradas ya pasadas siguen en `brand-green` (`#84cc16`). El
  error va en `error` (`#ba1a1a`).
- Para títulos y cifras se usa `text-strong` (blanco en oscuro, azul nocturno en
  claro), nunca `text-white`.
- Sobre los colores de ruta, que son claros en los dos temas, el texto va en
  `text-ink` (`#020e24`). Sobre rellenos dorado, verde o rojo va en
  `text-on-accent`: azul nocturno en oscuro y blanco en claro.
- El mapa usa la capa de OpenStreetMap en gris en claro y oscurecida en oscuro
  (clase `osm-tiles`).
- La bienvenida (`welcome.tsx`) y los layouts de cuenta (`auth-transit-layout`
  y `auth-simple-layout`) llevan la clase `dark`, así que se quedan en oscuro
  en los dos temas.

## Movimiento

- Lo que está en vivo pulsa: el punto verde de los chips (`animate-ping` o
  `animate-pulse`) y el anillo de la unidad.
- Los botones usan `transition-colors` y el principal se encoge al 98 % al
  presionarlo.
- Los mockups no lo hacen, pero en la app las pulsaciones van con
  `motion-safe:` para respetar a quien desactiva las animaciones.

## Tokens en `app.css`

El PR #7 pasó la paleta oscura de los mockups a `app.css` con nombres en
español:

| Variable de `app.css` | Token del mockup        | Hex       |
| --------------------- | ----------------------- | --------- |
| `--fondo-principal`   | `primary`               | `#020e24` |
| `--fondo-paneles`     | `primary-container`     | `#17243a` |
| `--bordes`            | `night-border`          | `#2f3e57` |
| `--boton-principal`   | `secondary-fixed`       | `#fddeb1` |
| `--boton-hover`       | `secondary-fixed-dim`   | `#e0c297` |
| `--focus`             | `focus-ring`            | `#b69b72` |
| `--verde-tiempo-real` | `tertiary-fixed-dim`    | `#91db2a` |
| `--verde-rutas`       | Línea del mapa (Stitch) | `#a3e635` |
| `--texto-principal`   | `on-primary`            | `#ffffff` |
| `--texto-formularios` | `inverse-on-surface`    | `#ecf1f9` |
| `--texto-secundario`  | `primary-fixed-dim`     | `#bac7e4` |
| `--placeholder`       | `on-primary-container`  | `#7e8ba6` |
| `--error`             | `error-soft`            | `#ffb4ab` |

- En las variables de shadcn, `--primary` es el dorado del botón (`#fddeb1`),
  `--background` el azul nocturno y `--card` el azul institucional.
- Colores nuevos para clases: `primary-hover`, `live`, `route` y `placeholder`
  (por ejemplo `bg-primary-hover` o `text-live`).
- `--radius: 0.75rem`: `rounded-lg` da 12 px, `rounded-md` 10 px y `rounded-sm`
  8 px. En login y registro, campos y botones se fijan en 8 px.
- Ojo al copiar clases de login y registro: los nombres no son los mismos que
  en la app. `bg-primary` de esos mockups es `bg-background`,
  `bg-primary-container` es `bg-card` y el botón dorado
  (`bg-secondary-fixed`) es `bg-primary`. Los mockups nuevos ya usan los nombres
  de la app.
- **Propuesta:** agregar `--color-warning: #fbbf24` (Demorado, 9.3:1 sobre
  `card`) y `--color-gold: #b69b72` (insignias, siguiente parada y ocupación
  media). Los mockups nuevos ya los usan como `warning` y `gold`.

## Pendientes de decidir

1. **Capa del mapa en oscuro.** Los mockups muestran cómo se ve el mapa en
   oscuro (ver [En oscuro](#en-oscuro)), pero la capa de [mapas.md](mapas.md)
   está filtrada para verse clara. Falta elegir el filtro o la capa oscura en
   Leaflet (Sosa).
2. **Dorado y verde.** El texto del DESIGN.md original usaba los colores del
   logo (`#B69B72`, `#84CC16`) y los tokens de los mockups usan otros
   (`#715b37`, `#91db2a`). Aquí se conservan todos, cada uno con su uso: el del
   logo para rellenos y bordes, y el más oscuro o más claro para texto, según el
   fondo, por contraste. Además, la línea del mapa de los mockups de Stitch usa
   `#a3e635`, que `app.css` tomó como `--verde-rutas`, y el demo usa
   `#84cc16`. Falta que el equipo lo confirme.
3. **Esquinas.** Login y registro usan 4, 8 y 12 px (decisión 5 del README) y
   `app.css` quedó en 8, 10 y 12 px, que es lo que usan los mockups nuevos. El
   DESIGN.md original trae otras dos escalas: 4, 8, 12, 16 y 24 px en los
   tokens y 8, 12 y 16 px en el texto.
4. **Íconos.** Los mockups nuevos ya usan lucide; falta confirmarlo en la
   decisión 3 del README.
5. **Tokens nuevos.** Agregar `warning` y `gold` a `app.css` (ver
   [Tokens en app.css](#tokens-en-appcss)).
