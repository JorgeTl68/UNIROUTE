# S5-02 — Revisión de calidad visual de UniRoute

## Objetivo

Verificar la adaptación visual, navegación y legibilidad de
UniRoute en escritorio, tableta y dispositivos móviles.

## Entorno de pruebas

- Aplicación: UniRoute
- Entorno: desarrollo local
- Navegador: Google Chrome
- Herramienta: Chrome DevTools, modo Responsive
- Base de datos: MySQL con datos de demostración
- Resoluciones: 375 × 812 px y 768 × 812 px
- Escritorio: revisión realizada; ancho exacto pendiente

## Pruebas realizadas

| ID    | Elemento                             | Resolución | Resultado |
| ----- | ------------------------------------ | ---------- | --------- |
| QA-01 | Logotipo y colores institucionales   | Escritorio | Correcto  |
| QA-02 | Navegación lateral                   | Escritorio | Correcto  |
| QA-03 | Mapa y tarjetas de rutas             | Escritorio | Correcto  |
| QA-04 | Mapa y paradas                       | 375 px     | Correcto  |
| QA-05 | Barra inferior de navegación         | 375 px     | Correcto  |
| QA-06 | Desplazamiento horizontal de filtros | 375 px     | Correcto  |
| QA-07 | Acceso al detalle de rutas           | 375 px     | Correcto  |
| QA-08 | Sidebar contraído                    | 768 px     | Correcto  |
| QA-09 | Selector de campus                   | 768 px     | Correcto  |
| QA-10 | Desplegable de notificaciones        | 768 px     | Correcto  |

## Observaciones

1. En la versión móvil, los filtros se desplazan horizontalmente.
   Se comprobó que es posible acceder al filtro Favoritas.

2. La barra inferior permanece visible durante la navegación móvil.

3. En 768 px, el sidebar expandido reduce el espacio disponible
   para el mapa. Al contraerlo, el contenido gana espacio.

4. No se observaron recortes en los desplegables de campus y
   notificaciones durante las pruebas realizadas.

5. El centro de notificaciones mostró el estado vacío.
   No se verificó la visualización de notificaciones reales.

## Limitaciones

- No se realizó una auditoría automatizada de accesibilidad.
- No se verificaron todos los formularios del sistema.
- No se comprobó el funcionamiento con datos de transporte en vivo.
- No se verificaron todas las pantallas a las tres resoluciones.

## Conclusión

Las pantallas y componentes revisados presentan un comportamiento
responsive funcional en las resoluciones probadas.

No se identificaron defectos visuales bloqueantes en las
interacciones comprobadas.

La revisión debe complementarse con pruebas de accesibilidad,
formularios y estados de datos reales antes de considerarse
una validación completa de toda la aplicación.
