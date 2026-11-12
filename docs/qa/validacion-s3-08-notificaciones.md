# S3-08 — Validación del centro de notificaciones

## Objetivo

Verificar el funcionamiento del centro de notificaciones y
alertas de transporte de UniRoute, incluyendo su adaptación
a dispositivos móviles.

## Entorno de pruebas

- Aplicación: UniRoute.
- Entorno: desarrollo local.
- Framework: Laravel, React e Inertia.
- Navegador: Google Chrome.
- Resolución móvil: 375 × 812 px.
- Pruebas automatizadas: Laravel Feature Tests.

## Revisión visual

| Elemento                           | Resultado |
| ---------------------------------- | --------- |
| Campana de notificaciones          | Correcto  |
| Menú desplegable de notificaciones | Correcto  |
| Acceso mediante "Ver todas"        | Correcto  |
| Centro de notificaciones           | Correcto  |
| Estado sin notificaciones          | Correcto  |
| Sección "Mis alarmas"              | Visible   |
| Sección "Rutas favoritas"          | Visible   |
| Navegación inferior "Alertas"      | Correcto  |
| Adaptación a 375 px                | Correcto  |

## Pruebas automatizadas

Se ejecutaron cinco archivos de pruebas:

| Archivo                        | Pruebas aprobadas |
| ------------------------------ | ----------------: |
| NotificationCenterTest.php     |                 4 |
| NotificationDispatcherTest.php |                 4 |
| NotificationsTableTest.php     |                 2 |
| TransitNotificationTest.php    |                 4 |
| TripAlertsTest.php             |                 3 |
| **Total**                      |            **17** |

Resultado: **17 pruebas aprobadas y 56 aserciones**.

## Funcionalidades verificadas

- Consulta de notificaciones correspondientes al usuario.
- Protección contra acceso a notificaciones ajenas.
- Marcado individual y general de mensajes como leídos.
- Envío de notificaciones a usuarios y roles.
- Almacenamiento de notificaciones en la base de datos.
- Uso de canales privados para notificaciones.
- Serialización de información enviada al frontend.
- Generación de alertas de llegada y retrasos.
- Prevención de alertas duplicadas en los casos probados.

## Observaciones

El centro de notificaciones presentó correctamente el estado
vacío cuando el usuario no tenía mensajes.

La interfaz móvil mostró las secciones de notificaciones,
alarmas y rutas favoritas sin recortes evidentes.

## Limitaciones

- No se comprobó la recepción de notificaciones reales en el
  navegador.
- No se probaron visualmente mensajes de todos los tipos.
- No se realizó una auditoría completa de accesibilidad.
- No se modificó código funcional ni la base de datos.

## Conclusión

El centro de notificaciones de UniRoute superó las 17 pruebas
automatizadas ejecutadas y presentó una adaptación visual
adecuada en la resolución móvil revisada.

No se identificaron fallos en las verificaciones realizadas.
