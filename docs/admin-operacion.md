# Operación administrativa de UniRoute

Los datos actuales siguen siendo de prueba. Este flujo no los sustituye por inventario real.

## Indicadores de Flota

- Unidades: vehículos en servicio respecto del total registrado.
- Viajes de hoy: programados, en curso y completados; excluye cancelados.
- Puntualidad: porcentaje de viajes iniciados hoy cuya salida ocurre dentro de ±2 minutos de su hora programada. Sin salidas, muestra “—”.
- Ocupación promedio: suma de la ocupación registrada dividida entre la capacidad de los vehículos de los viajes iniciados hoy. Es una medición de ocupación, no un conteo de estudiantes únicos.
- El banner muestra la incidencia abierta más grave; a igual gravedad, la más reciente. Indicadores y banner se actualizan cada 15 segundos mientras la pantalla está visible.

## Paradas en el mapa

En Administración → Rutas → Crear ruta, pulsa una ubicación del mapa. Indica el nombre de la nueva parada y confirma. Se agrega al catálogo y al recorrido. Revisa los minutos acumulados, calcula el trazado por calles y guarda la ruta. Cancelar el diálogo no guarda una parada.

## Desvíos temporales

1. Registra una incidencia de tipo “Desvío” para la ruta afectada.
2. Pulsa “Desviar unidades” y selecciona las paradas que se omitirán. Deben conservarse al menos dos.
3. El sistema recalcula el recorrido entre las paradas restantes con OSRM. Solo aplica los cambios si el cálculo es válido.
4. El recorrido temporal es compartido por las unidades de esa ruta y se utiliza para mapa y ETA. No cambia sus conductores ni asignaciones.
5. Al marcar la incidencia como resuelta, restaura el trazado, orden y tiempos originales. El desvío queda en el historial de la incidencia.

Solo puede haber un desvío activo por ruta. Para cambiarlo, resuelve el anterior y crea otra incidencia. El servicio calcula un recorrido por las paradas restantes: no consulta tráfico ni garantiza evitar una calle cerrada concreta; el operador debe revisar el recorrido propuesto.

## Actualización local y prueba

Después de integrar los cambios:

```powershell
php artisan migrate
npm run build
composer test
npm run check
npm run types:check
```

La migración `create_route_detours_table` conserva el recorrido original y los responsables de aplicar y restaurar cada desvío.

Los historiales de mantenimiento y relevos se envían al navegador con zona horaria explícita. No se interpreta una hora local como si fuera UTC.
