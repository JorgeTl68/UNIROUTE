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

export type IncidentSeverity = 'low' | 'medium' | 'high' | 'critical';

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
    /** Texto en español del tipo, por ejemplo "Tráfico". */
    type_label: string;
    severity: IncidentSeverity;
    severity_label: string;
    route_id: number | null;
    vehicle_id: number | null;
    reported_by: number;
    message: string;
    latitude: number | null;
    longitude: number | null;
    students_notified_at: IsoDateTime | null;
    resolved_at: IsoDateTime | null;
    route?: Pick<TransitRoute, 'id' | 'number' | 'name' | 'color'> | null;
    detour?: {
        active: boolean;
        skipped_stop_ids: number[];
        restored_at: string | null;
    } | null;
    vehicle?: Pick<Vehicle, 'id' | 'code'> | null;
    reporter?: Pick<User, 'id' | 'name'> & { role: UserRole };
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
/* Datos compuestos para pantallas (LiveNetwork en el servidor)        */
/* ------------------------------------------------------------------ */

export type PositionSnapshot = Pick<
    VehiclePosition,
    'latitude' | 'longitude' | 'speed_kmh' | 'heading' | 'recorded_at'
>;

/** Siguiente parada de una unidad en viaje. */
export type NextStop = {
    id: number;
    name: string;
    eta_minutes: number;
    arrives_at: IsoDateTime;
    distance_meters: number;
};

/** Unidad en viaje, tal como la dibujan el mapa y las tarjetas. */
export type LiveVehicle = {
    trip_id: number;
    route_id: number;
    vehicle: Pick<Vehicle, 'id' | 'code' | 'plate' | 'model' | 'capacity'> & {
        amenities: VehicleAmenity[];
    };
    driver: Pick<User, 'id' | 'name'> | null;
    occupancy: number;
    position: PositionSnapshot | null;
    next_stop: NextStop | null;
    /** Minutos de retraso sobre el horario; negativo si va adelantado. */
    delay_minutes: number;
    is_delayed: boolean;
    /** Fracción del recorrido ya hecha, de 0 a 1. */
    progress: number | null;
};

/** Parada de una ruta para el mapa. */
export type RoutePoint = Pick<
    Stop,
    'id' | 'name' | 'latitude' | 'longitude' | 'has_shelter'
> & {
    position: number;
    minutes_from_start: number;
};

/** Estado de una parada respecto a la unidad que llega antes. */
export type StopState = 'passed' | 'current' | 'next' | 'pending';

export type TimelineStop = RoutePoint & {
    state: StopState;
    eta_minutes: number | null;
    arrives_at: IsoDateTime | null;
};

type RouteBase = Pick<
    TransitRoute,
    'id' | 'number' | 'name' | 'description' | 'color' | 'encoded_polyline'
>;

/** Ruta en el Dashboard en Vivo. */
export type LiveRoute = RouteBase & {
    stops: RoutePoint[];
    is_favorite: boolean;
    /** Primera salida que queda hoy, "HH:MM". */
    next_departure: string | null;
    vehicles: LiveVehicle[];
};

/** Salida que queda hoy en el Detalle de ruta. */
export type UpcomingDeparture = {
    schedule_id: number;
    departs_at: string;
    status: TripStatus;
    vehicle_code: string | null;
    driver_name: string | null;
};

/** Props de la página Dashboard en Vivo. */
export type LiveDashboardProps = {
    campus: Pick<
        Campus,
        'id' | 'name' | 'slug' | 'latitude' | 'longitude'
    > | null;
    routes: LiveRoute[];
    incidents: Incident[];
};

/** Props de la página Detalle de Rutas y Paradas. */
export type RouteDetailProps = {
    route: RouteBase &
        Pick<
            TransitRoute,
            'frequency_minutes' | 'service_starts_at' | 'service_ends_at'
        > & {
            campus: Pick<Campus, 'id' | 'name'>;
            is_favorite: boolean;
        };
    stops: TimelineStop[];
    vehicles: LiveVehicle[];
    upcoming_departures: UpcomingDeparture[];
    routes: Pick<TransitRoute, 'id' | 'number' | 'name' | 'color'>[];
    alarms: Pick<
        ArrivalAlarm,
        'id' | 'stop_id' | 'minutes_before' | 'is_active'
    >[];
    incidents: Incident[];
    default_alarm_minutes: number;
};

/** Tipo de aviso de TransitNotification. */
export type NotificationType = 'arrival' | 'delay' | 'service' | 'incident';

/** Aviso guardado en la tabla notifications (canal database). */
export type AppNotification = {
    id: string;
    type: NotificationType;
    title: string;
    body: string;
    read_at: IsoDateTime | null;
    created_at: IsoDateTime;
};

/* ------------------------------------------------------------------ */
/* Tiempo real (Laravel Reverb)                                        */
/* ------------------------------------------------------------------ */

/** Canales públicos de transporte. */
export type TransitChannel = `campus.${number}` | `route.${number}`;

/** Evento VehiclePositionUpdated. */
export type VehiclePositionUpdatedEvent = {
    vehicle_id: number;
    trip_id: number;
    route_id: number;
    position: PositionSnapshot;
    occupancy: number;
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
