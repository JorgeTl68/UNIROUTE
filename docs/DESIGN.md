# Diseño de UniRoute

> Este archivo se reconstruyó el 2026-11-11 a partir de los mockups, porque el
> DESIGN.md original que salió con ellos nunca se subió al repositorio. Los
> valores salen de la configuración de Tailwind de cada mockup. Lo que el
> original decía distinto y se sabe está en
> [Pendientes de decidir](#pendientes-de-decidir).

| Mockup                                                     | Pantalla        | Tema   | Para                            |
| ---------------------------------------------------------- | --------------- | ------ | ------------------------------- |
| [docs/mockups/login.html](mockups/login.html)              | Iniciar sesión  | Oscuro | S2-08 (Andrés)                  |
| [docs/mockups/registro.html](mockups/registro.html)        | Crear cuenta    | Oscuro | S2-08 (Andrés) y S2-11          |
| Demo de la Ruta 1 Santa Elena (todavía no está en el repo) | Ruta en el mapa | Claro  | Mapa y Dashboard en Vivo (Sosa) |

Los tokens del tema claro y la sección [Mapa](#mapa) salen de ese demo, que se
subirá aparte.

Los mockups cargan Tailwind por CDN solo para poder abrirse solos en el
navegador. En la app los colores salen de `resources/css/app.css` (S1-04); no se
copia su configuración.

## Idea general

- Es transporte universitario: se consulta rápido, de pie y desde el celular.
  Datos grandes (hora de llegada, minutos) y textos cortos.
- Azul nocturno como base, dorado champán como acento de marca y verde
  señalización para lo que está en vivo o ya pasó.
- En los mockups, login y registro van con fondo oscuro y el resto de la app es
  clara. En `main` toda la app quedó en oscuro (ver [Tema oscuro](#tema-oscuro)).

## Color

Los nombres siguen el esquema de Material 3 que traen los mockups. `on-X` es el
color del texto que va encima de `X`.

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

Los mockups usan Material Symbols Outlined: 18 px en campos y botones, 20 a
24 px en los cuadros de ícono y 16 px en los mensajes de error. La propuesta del
README (decisión 3) es `lucide-react`; estas son las equivalencias, todas
existen en la versión instalada:

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

Todavía no hay mockup de formularios en tema claro (pantallas de
administración).

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

## Pantallas

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

### Mapa de la ruta (estudiante)

- Escritorio: panel blanco de 390 px a la izquierda y el mapa en el resto.
- Celular: el mapa arriba (58 % de la altura de la pantalla) y el panel debajo.
- Sobre el mapa: el selector de capas arriba a la derecha y la tarjeta de
  "Próxima parada" abajo, con el nombre de la parada, los minutos que faltan y
  la barra de avance de la ruta.

## Tema oscuro

En los mockups solo login y registro van en oscuro (llevan
`<meta name="color-scheme" content="dark">`); el resto de la app es clara, como
dice la decisión 6 del README.

En `main`, el PR #7 dejó toda la app en oscuro: `:root` y `.dark` tienen la misma
paleta de login y `color-scheme: dark`. Falta decidir cuál se queda (ver
[Pendientes de decidir](#pendientes-de-decidir)).

## Movimiento

- Lo que está en vivo pulsa: el punto verde de los chips (`animate-ping` o
  `animate-pulse`) y el anillo de la unidad.
- Los botones usan `transition-colors` y el principal se encoge al 98 % al
  presionarlo.
- Los mockups no lo hacen, pero en la app las pulsaciones van con
  `motion-safe:` para respetar a quien desactiva las animaciones.

## Tokens en `app.css`

### Lo que ya está en `main`

El PR #7 pasó la paleta oscura de los mockups a `app.css` con nombres en
español:

| Variable de `app.css` | Token del mockup       | Hex       |
| --------------------- | ---------------------- | --------- |
| `--fondo-principal`   | `primary`              | `#020e24` |
| `--fondo-paneles`     | `primary-container`    | `#17243a` |
| `--bordes`            | `night-border`         | `#2f3e57` |
| `--boton-principal`   | `secondary-fixed`      | `#fddeb1` |
| `--boton-hover`       | `secondary-fixed-dim`  | `#e0c297` |
| `--focus`             | `focus-ring`           | `#b69b72` |
| `--verde-tiempo-real` | `tertiary-fixed-dim`   | `#91db2a` |
| `--verde-rutas`       | Ninguno (nuevo)        | `#a3e635` |
| `--texto-principal`   | `on-primary`           | `#ffffff` |
| `--texto-formularios` | `inverse-on-surface`   | `#ecf1f9` |
| `--texto-secundario`  | `primary-fixed-dim`    | `#bac7e4` |
| `--placeholder`       | `on-primary-container` | `#7e8ba6` |
| `--error`             | `error-soft`           | `#ffb4ab` |

- En las variables de shadcn, `--primary` es el dorado del botón (`#fddeb1`),
  `--background` el azul nocturno y `--card` el azul institucional.
- Colores nuevos para clases: `primary-hover`, `live`, `route` y `placeholder`
  (por ejemplo `bg-primary-hover` o `text-live`).
- `--radius: 0.75rem`: `rounded-lg` da 12 px, `rounded-md` 10 px y `rounded-sm`
  8 px. En login y registro, campos y botones se fijan en 8 px.

### Si se mantiene el tema claro (decisión 6)

**Propuesta.** Equivalencias con las variables de shadcn para las pantallas
claras:

| Variable de shadcn                          | Token                      | Hex       |
| ------------------------------------------- | -------------------------- | --------- |
| `--background`                              | `surface`                  | `#f7f9ff` |
| `--foreground`                              | `on-surface`               | `#161c22` |
| `--card`, `--popover`                       | `surface-container-lowest` | `#ffffff` |
| `--card-foreground`, `--popover-foreground` | `on-surface`               | `#161c22` |
| `--primary`                                 | `primary-container`        | `#17243a` |
| `--primary-foreground`                      | `on-primary`               | `#ffffff` |
| `--secondary`                               | `secondary-fixed`          | `#fddeb1` |
| `--secondary-foreground`                    | `primary-container`        | `#17243a` |
| `--muted`                                   | `surface-container-low`    | `#eff4fc` |
| `--muted-foreground`                        | `on-surface-variant`       | `#44474d` |
| `--accent`                                  | `surface-container`        | `#e9eef6` |
| `--accent-foreground`                       | `primary-container`        | `#17243a` |
| `--destructive`                             | `error`                    | `#ba1a1a` |
| `--border`, `--input`                       | `surface-variant`          | `#dde3eb` |
| `--ring`                                    | `focus-ring`               | `#b69b72` |

- Ojo al copiar clases de los mockups: `primary` y `secondary` no son los mismos
  colores que en la app. En los mockups `primary` es el azul nocturno
  (`#020e24`) y `secondary` el dorado oscuro para texto (`#715b37`); en la app
  `primary` es el color del botón principal.
- Los tokens del tema claro sin equivalente en shadcn (`brand-gold`,
  `brand-green`, `on-tertiary-container`, `surface-container-high`, `outline`,
  `error-container` y el dorado oscuro `#715b37`) se registran en `@theme`.

## Pendientes de decidir

1. **Tema.** Los mockups y la decisión 6 del README dejan en oscuro solo login y
   registro; en `main` (PR #7) toda la app quedó en oscuro. Si se queda así, hay
   que actualizar la decisión 6 y pasar a oscuro las pantallas que en el demo
   son claras (mapa y lista de paradas).
2. **Dorado y verde.** El texto del DESIGN.md original usaba los colores del
   logo (`#B69B72`, `#84CC16`) y los tokens de los mockups usan otros
   (`#715b37`, `#91db2a`). Aquí se conservan todos, cada uno con su uso: el del
   logo para rellenos y bordes, y el más oscuro o más claro para texto, según el
   fondo, por contraste. Además, `app.css` agregó un verde para las rutas
   (`#a3e635`) que no viene en los mockups ni en el logo; la línea de la ruta
   del demo usa `#84cc16`. Falta que el equipo lo confirme.
3. **Esquinas.** Los mockups usan 4, 8 y 12 px (decisión 5 del README) y
   `app.css` quedó en 8, 10 y 12 px. El DESIGN.md original traía otras dos
   escalas que no se conservaron.
4. **Íconos.** Material Symbols o `lucide-react` (decisión 3 del README).
5. **Pantallas sin mockup.** Panel de administración, sidebar, tablas,
   formularios en claro y gráficas. Se diseñan con estos mismos tokens y, cuando
   haya mockup, se agregan aquí.
