# Marca UniRoute

Los logos están en `public/branding/` y se usan con su ruta pública:

```tsx
<img
    src="/branding/logo-horizontal-compacto-claro.svg"
    alt="UniRoute"
    width={200}
/>
```

El sufijo del nombre indica el **fondo** donde va el logo: `-claro` para fondos
claros y `-oscuro` para fondos oscuros.

## Qué logo usar

| Dónde                                    | Archivo                                                        |
| ---------------------------------------- | -------------------------------------------------------------- |
| Encabezado con subtítulo                 | `logo-horizontal-claro.svg` / `logo-horizontal-oscuro.svg`     |
| Barra de navegación, sidebar             | `logo-horizontal-compacto-claro.svg` / `-compacto-oscuro.svg`  |
| Login, registro y pantalla de bienvenida | `logo-vertical-claro.svg` / `logo-vertical-oscuro.svg`         |
| Solo el nombre                           | `logotipo-claro.svg` / `logotipo-oscuro.svg`                   |
| Sidebar colapsado, íconos grandes        | `isotipo-claro.svg` / `isotipo-oscuro.svg`                     |
| Una sola tinta                           | `logo-monocromatico-azul.svg` / `-blanco.svg` (y sus isotipos) |
| Ícono de aplicación                      | `app-icon-dark.svg` / `app-icon-light.svg`                     |

El favicon de la pestaña del navegador ya es el de UniRoute (`public/favicon.svg` y
`public/favicon.ico`).

## Paleta del logo

| Color              | Hex       |
| ------------------ | --------- |
| Azul institucional | `#17243A` |
| Azul nocturno      | `#020E24` |
| Dorado champán     | `#B69B72` |
| Verde señalización | `#84CC16` |
| Blanco             | `#FFFFFF` |

Ojo para S1-04 (tokens): el dorado y el verde del logo coinciden con el texto de
DESIGN.md (`#B69B72`, `#84CC16`), no con los tokens de los mockups (`#715b37`,
`#91db2a`). Conviene decidir cuál manda antes de pasar los colores a `app.css`.

## Detalles técnicos

- Los logos con texto usan la tipografía Inter. Dentro de un `<img>` el SVG no
  puede usar la fuente de la página: si la computadora no tiene Inter instalada, el
  texto sale con otra letra. Para que se vea idéntico, usar el isotipo y escribir
  "UniRoute" con HTML, o convertir el texto a contornos en el editor del logo.
- Faltan los PNG del kit (`apple-touch-icon.png`, `icon-192.png`, `icon-512.png`).
  Mientras tanto, `public/apple-touch-icon.png` sigue siendo el de Laravel.
- El componente `resources/js/components/app-logo-icon.tsx` todavía dibuja el logo
  de Laravel; se cambia por el de UniRoute en el layout (S1-05, Andrés).
