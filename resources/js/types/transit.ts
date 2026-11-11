import type { User } from './auth';

/*
 * Tipos compartidos del dominio de transporte de UniRoute.
 *
 * Reflejan cómo Laravel serializa los modelos (snake_case). Si cambias una
 * migración o un API Resource, actualiza el tipo en el mismo PR.
 * Ver docs/modelo-de-datos.md.
 */

/** Fecha y hora ISO 8601, por ejemplo "2026-11-10T22:45:00.000000Z". */
export type IsoDateTime = string;

/** Fecha "AAAA-MM-DD". */
export type IsoDate = string;

/** Hora local de Saltillo "HH:MM:SS". */
export type TimeOfDay = string;

/** Coordenada en el formato {lat, lng} que acepta Leaflet. */
export type LatLng = {
    lat: number;
    lng: number;
};

/* ------------------------------------------------------------------ */
/* Valores de estado                                                   */
/* ------------------------------------------------------------------ */

export type UserRole = 'student' | 'driver' | 'admin';

export type VehicleStatus = 'in_service' | 'standby' | 'maintenance';

export type VehicleAmenity = 'air_conditioning' | 'wifi' | 'accessible_ramp';

export type TripStatus =
    | 'scheduled'
    | 'in_progress'
    | 'completed'
    | 'cancelled';

export type IncidentType = 'traffic' | 'detour' | 'breakdown' | 'emergency';

export type IncidentSeverity = 'low' | 'medium' | 'high';

/** 1 = lunes … 7 = domingo (ISO 8601). */
export type Weekday = 1 | 2 | 3 | 4 | 5 | 6 | 7;

/* ------------------------------------------------------------------ */
/* Modelos (una tabla cada uno)                                        */
/* ------------------------------------------------------------------ */

type Timestamps = {
    created_at: IsoDateTime;
    updated_at: IsoDateTime;
};

export type Campus = Timestamps & {
    id: number;
    name: string;
    slug: string;
    latitude: number;
    longitude: number;
};

/** Ruta de transporte. Se llama así para no confundirla con las rutas web. */
export type TransitRoute = Timestamps & {
    id: number;
    campus_id: number;
    number: number;
    name: string;
    description: string | null;
    /** Color hexadecimal de la línea en el mapa, por ejemplo "#91db2a". */
    color: string;
    /** Polilínea codificada (precisión 5) que devuelve OSRM. */
    encoded_polyline: string | null;
    frequency_minutes: number;
    service_starts_at: TimeOfDay;
    service_ends_at: TimeOfDay;
    is_active: boolean;
};

export type Stop = Timestamps & {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    has_shelter: boolean;
    notes: string | null;
};

/** Parada dentro de una ruta, con su orden (tabla pivote route_stop). */
export type RouteStop = Stop & {
    pivot: {
        route_id: number;
        stop_id: number;
        position: number;
        minutes_from_start: number;
    };
};

export type Vehicle = Timestamps & {
    id: number;
    /** Identificador visible, por ejemplo "BUS-UTC-01". */
    code: string;
    plate: string;
    model: string;
    capacity: number;
    status: VehicleStatus;
    amenities: VehicleAmenity[] | null;
};

export type DriverProfile = Timestamps & {
    id: number;
    user_id: number;
    license_number: string;
    hired_at: IsoDate;
    rating: number | null;
};

/** Usuario con rol de conductor y su perfil. */
export type Driver = Pick<User, 'id' | 'name' | 'avatar'> & {
    driver_profile: DriverProfile | null;
};

export type Schedule = Timestamps & {
    id: number;
    route_id: number;
    weekday: Weekday;
    departs_at: TimeOfDay;
};

export type Trip = Timestamps & {
    id: number;
    route_id: number;
    schedule_id: number | null;
    vehicle_id: number | null;
    driver_id: number | null;
    scheduled_at: IsoDateTime;
    started_at: IsoDateTime | null;
    ended_at: IsoDateTime | null;
    status: TripStatus;
    occupancy: number;
};

export type VehiclePosition = {
    id: number;
    vehicle_id: number;
    trip_id: number | null;
    latitude: number;
    longitude: number;
    speed_kmh: number | null;
    /** Rumbo en grados, de 0 a 359. */
    heading: number | null;
    recorded_at: IsoDateTime;
};

export type Incident = Timestamps & {
    id: number;
    type: IncidentType;
    severity: IncidentSeverity;
    route_id: number | null;
    vehicle_id: number | null;
    reported_by: number;
    message: string;
    resolved_at: IsoDateTime | null;
};

export type ArrivalAlarm = Timestamps & {
    id: number;
    user_id: number;
    route_id: number;
    stop_id: number;
    minutes_before: number;
    is_active: boolean;
};

/* ------------------------------------------------------------------ */
/* Datos compuestos para pantallas                                     */
/* Propuesta inicial; se ajusta en S0-09 con quienes hacen cada página. */
/* ------------------------------------------------------------------ */

export type PositionSnapshot = Pick<
    VehiclePosition,
    'latitude' | 'longitude' | 'speed_kmh' | 'heading' | 'recorded_at'
>;

/** Unidad en circulación, tal como la dibuja el mapa. */
export type LiveVehicle = {
    vehicle: Pick<Vehicle, 'id' | 'code' | 'model' | 'capacity' | 'status'>;
    trip: Pick<
        Trip,
        | 'id'
        | 'route_id'
        | 'status'
        | 'occupancy'
        | 'scheduled_at'
        | 'started_at'
    > | null;
    driver: Pick<User, 'id' | 'name'> | null;
    position: PositionSnapshot | null;
};

/** Llegada estimada de un viaje a una parada. */
export type StopEta = {
    stop_id: number;
    trip_id: number;
    eta_minutes: number;
    arrives_at: IsoDateTime;
    /** Minutos de retraso sobre el horario; negativo si va adelantado. */
    delay_minutes: number;
};

/** Tarjeta de ruta en el Dashboard en Vivo. */
export type RouteSummary = Pick<
    TransitRoute,
    'id' | 'number' | 'name' | 'description' | 'color'
> & {
    stops_count: number;
    is_delayed: boolean;
    next_arrival: (StopEta & { stop_name: string }) | null;
    live_vehicle: LiveVehicle | null;
};

/** Salida programada en la tabla "Próximas Salidas". */
export type UpcomingDeparture = Pick<Trip, 'id' | 'scheduled_at' | 'status'> & {
    vehicle_code: string | null;
    driver_name: string | null;
};

/** Props de la página Dashboard en Vivo. */
export type LiveDashboardProps = {
    campus: Campus;
    routes: RouteSummary[];
    vehicles: LiveVehicle[];
};

/** Props de la página Detalle de Rutas y Paradas. */
export type RouteDetailProps = {
    route: TransitRoute & { campus: Campus; stops: RouteStop[] };
    vehicles: LiveVehicle[];
    etas: StopEta[];
    upcoming_departures: UpcomingDeparture[];
};

/* ------------------------------------------------------------------ */
/* Tiempo real (Laravel Reverb)                                        */
/* ------------------------------------------------------------------ */

/** Canales públicos de transporte. */
export type TransitChannel = `campus.${number}` | `route.${number}`;

/** Evento VehiclePositionUpdated. */
export type VehiclePositionUpdatedEvent = {
    vehicle_id: number;
    trip_id: number | null;
    route_id: number | null;
    position: PositionSnapshot;
    occupancy: number | null;
};

/** Evento TripStatusChanged. */
export type TripStatusChangedEvent = {
    trip_id: number;
    route_id: number;
    vehicle_id: number | null;
    status: TripStatus;
    changed_at: IsoDateTime;
};

/** Evento IncidentReported. */
export type IncidentReportedEvent = {
    incident: Incident;
};
