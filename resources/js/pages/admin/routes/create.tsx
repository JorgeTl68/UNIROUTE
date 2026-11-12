import { Head, Link, useForm } from '@inertiajs/react';
import polyline from '@mapbox/polyline';
import { useEffect, useMemo, useState } from 'react';
import {
    CircleMarker,
    MapContainer,
    Polyline,
    TileLayer,
    Tooltip,
    useMap,
} from 'react-leaflet';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    MapPinPlus,
    Save,
    Sparkles,
    Trash2,
} from 'lucide-react';
import 'leaflet/dist/leaflet.css';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Campus = {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
};

type Stop = {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
};

type RouteStop = {
    id: number;
    minutes_from_start: string;
};

type Preview = {
    token: string;
    encoded_polyline: string;
    stops: { id: number; minutes_from_start: number }[];
    distance_meters: number;
    duration_seconds: number;
};

type RouteForm = {
    campus_id: string;
    number: string;
    name: string;
    description: string;
    color: string;
    frequency_minutes: string;
    service_starts_at: string;
    service_ends_at: string;
    stops: RouteStop[];
    preview_token: string;
};

const routeColors = ['#91db2a', '#e0c297', '#7e8ba6', '#715b37', '#93c5fd'];

function signature(stops: Array<{ id: number }>) {
    return stops.map((stop) => String(stop.id)).join('|');
}

const defaultCenter: [number, number] = [25.4262, -100.9925];

/**
 * Encuadra el mapa solo cuando cambian las paradas o el campus, no en cada
 * render: así no se pierde el zoom mientras se llenan los demás campos.
 */
function FitMap({
    positions,
    fallback,
}: {
    positions: [number, number][];
    fallback: [number, number];
}) {
    const map = useMap();

    useEffect(() => {
        if (positions.length === 0) {
            map.setView(fallback, 13);
        } else if (positions.length === 1) {
            map.setView(positions[0], 14);
        } else {
            map.fitBounds(positions, { padding: [36, 36] });
        }
    }, [map, positions, fallback]);

    return null;
}

export default function CreateRoute({
    campuses,
    stops,
    preview,
}: {
    campuses: Campus[];
    stops: Stop[];
    preview: Preview | null;
}) {
    const form = useForm<RouteForm>({
        campus_id: campuses[0] ? String(campuses[0].id) : '',
        number: '',
        name: '',
        description: '',
        color: routeColors[0],
        frequency_minutes: '15',
        service_starts_at: '06:00',
        service_ends_at: '22:00',
        stops: [],
        preview_token: '',
    });
    const [stopToAdd, setStopToAdd] = useState('');
    const [tracing, setTracing] = useState(false);
    const selectedStops = form.data.stops
        .map((routeStop) => ({
            routeStop,
            stop: stops.find((stop) => stop.id === routeStop.id),
        }))
        .filter(
            (item): item is { routeStop: RouteStop; stop: Stop } =>
                item.stop !== undefined,
        );
    const previewIsCurrent = Boolean(
        preview && signature(form.data.stops) === signature(preview.stops),
    );
    // Depende de los IDs en orden, no del arreglo de paradas: editar los
    // minutos o cualquier otro campo no debe volver a encuadrar el mapa.
    const stopIds = signature(form.data.stops);
    const directPositions = useMemo<[number, number][]>(
        () =>
            stopIds
                .split('|')
                .map((id) => stops.find((stop) => String(stop.id) === id))
                .filter((stop): stop is Stop => stop !== undefined)
                .map((stop) => [stop.latitude, stop.longitude]),
        [stopIds, stops],
    );
    const selectedCampus = campuses.find(
        (campus) => String(campus.id) === form.data.campus_id,
    );
    const campusCenter = useMemo<[number, number]>(
        () =>
            selectedCampus
                ? [selectedCampus.latitude, selectedCampus.longitude]
                : defaultCenter,
        [selectedCampus],
    );
    const tracedPositions = useMemo<[number, number][]>(() => {
        if (!preview || !previewIsCurrent) return [];

        try {
            return polyline.decode(preview.encoded_polyline) as [
                number,
                number,
            ][];
        } catch {
            return [];
        }
    }, [preview, previewIsCurrent]);
    const mapPositions = tracedPositions.length
        ? tracedPositions
        : directPositions;
    const mapCenter: [number, number] = directPositions[0] ?? campusCenter;
    const errors = form.errors as Record<string, string | undefined>;

    useEffect(() => {
        if (
            previewIsCurrent &&
            preview &&
            form.data.preview_token !== preview.token
        ) {
            form.setData('preview_token', preview.token);
        }
    }, [form, preview, previewIsCurrent]);

    const replaceStops = (nextStops: RouteStop[]) => {
        form.setData({
            ...form.data,
            stops: nextStops.map((stop, index) =>
                index === 0 ? { ...stop, minutes_from_start: '0' } : stop,
            ),
            preview_token: '',
        });
    };

    const addStop = () => {
        const id = Number(stopToAdd);
        if (!id || form.data.stops.some((stop) => stop.id === id)) return;

        const previous = form.data.stops.at(-1);
        const nextMinutes = previous
            ? String(Number(previous.minutes_from_start || 0) + 5)
            : '0';
        replaceStops([
            ...form.data.stops,
            { id, minutes_from_start: nextMinutes },
        ]);
        setStopToAdd('');
    };

    const moveStop = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= form.data.stops.length) return;

        const nextStops = [...form.data.stops];
        [nextStops[index], nextStops[target]] = [
            nextStops[target],
            nextStops[index],
        ];
        replaceStops(nextStops);
    };

    const calculateTrace = () => {
        form.clearErrors();
        setTracing(true);
        form.post('/admin/routes/trace', {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => setTracing(false),
        });
    };

    return (
        <>
            <Head title="Nueva ruta" />
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/admin/routes', { preserveScroll: true });
                }}
                className="space-y-6 px-4 py-6 sm:px-6 lg:px-8"
            >
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <Link
                            href="/admin/routes"
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary"
                        >
                            <ArrowLeft className="size-4" />
                            Volver a rutas y paradas
                        </Link>
                        <h1 className="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
                            Nueva ruta
                        </h1>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href="/admin/routes">Cancelar</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing || !previewIsCurrent}
                        >
                            <Save className="size-4" />
                            {form.processing && !tracing
                                ? 'Guardando…'
                                : 'Guardar ruta'}
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-12">
                    <div className="min-w-0 space-y-5 xl:col-span-5">
                        <Card>
                            <CardHeader>
                                <h2 className="font-bold">Datos de la ruta</h2>
                                <p className="text-xs text-muted-foreground">
                                    Todos los campos son obligatorios salvo la
                                    descripción.
                                </p>
                            </CardHeader>
                            <CardContent className="grid gap-5">
                                <div className="grid gap-4 sm:grid-cols-[7rem_1fr]">
                                    <div className="grid gap-2">
                                        <Label htmlFor="number">Número</Label>
                                        <Input
                                            id="number"
                                            type="number"
                                            min="1"
                                            max="65535"
                                            required
                                            value={form.data.number}
                                            onChange={(event) =>
                                                form.setData(
                                                    'number',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.number,
                                            )}
                                        />
                                        <InputError
                                            message={form.errors.number}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nombre</Label>
                                        <Input
                                            id="name"
                                            maxLength={255}
                                            required
                                            value={form.data.name}
                                            onChange={(event) =>
                                                form.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.name,
                                            )}
                                        />
                                        <InputError
                                            message={form.errors.name}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="campus_id">Campus</Label>
                                    <select
                                        id="campus_id"
                                        required
                                        value={form.data.campus_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'campus_id',
                                                event.target.value,
                                            )
                                        }
                                        aria-invalid={Boolean(
                                            form.errors.campus_id,
                                        )}
                                        className="h-10 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                                    >
                                        {campuses.map((campus) => (
                                            <option
                                                key={campus.id}
                                                value={campus.id}
                                            >
                                                {campus.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={form.errors.campus_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        Descripción
                                    </Label>
                                    <textarea
                                        id="description"
                                        rows={3}
                                        maxLength={65535}
                                        value={form.data.description}
                                        onChange={(event) =>
                                            form.setData(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                        aria-invalid={Boolean(
                                            form.errors.description,
                                        )}
                                        className="rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                                    />
                                    <InputError
                                        message={form.errors.description}
                                    />
                                </div>

                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium">
                                        Color en el mapa
                                    </legend>
                                    <div className="flex flex-wrap gap-3">
                                        {routeColors.map((color) => (
                                            <label
                                                key={color}
                                                className="cursor-pointer"
                                            >
                                                <input
                                                    type="radio"
                                                    name="color"
                                                    value={color}
                                                    checked={
                                                        form.data.color ===
                                                        color
                                                    }
                                                    onChange={() =>
                                                        form.setData(
                                                            'color',
                                                            color,
                                                        )
                                                    }
                                                    className="peer sr-only"
                                                />
                                                <span
                                                    className="block size-9 rounded-full ring-offset-2 ring-offset-card peer-checked:ring-2 peer-checked:ring-foreground"
                                                    style={{
                                                        backgroundColor: color,
                                                    }}
                                                    title={color}
                                                />
                                            </label>
                                        ))}
                                    </div>
                                    <InputError
                                        className="mt-2"
                                        message={form.errors.color}
                                    />
                                </fieldset>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="frequency_minutes">
                                            Frecuencia (opcional)
                                        </Label>
                                        <Input
                                            id="frequency_minutes"
                                            type="number"
                                            min="1"
                                            max="65535"
                                            placeholder="Por turno"
                                            value={form.data.frequency_minutes}
                                            onChange={(event) =>
                                                form.setData(
                                                    'frequency_minutes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                form.errors.frequency_minutes
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="service_starts_at">
                                            Desde
                                        </Label>
                                        <Input
                                            id="service_starts_at"
                                            type="time"
                                            required
                                            value={form.data.service_starts_at}
                                            onChange={(event) =>
                                                form.setData(
                                                    'service_starts_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                form.errors.service_starts_at
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="service_ends_at">
                                            Hasta
                                        </Label>
                                        <Input
                                            id="service_ends_at"
                                            type="time"
                                            required
                                            value={form.data.service_ends_at}
                                            onChange={(event) =>
                                                form.setData(
                                                    'service_ends_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                form.errors.service_ends_at
                                            }
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <h2 className="font-bold">Paradas en orden</h2>
                                <span className="text-xs text-muted-foreground">
                                    {form.data.stops.length} paradas
                                </span>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex gap-2">
                                    <select
                                        value={stopToAdd}
                                        onChange={(event) =>
                                            setStopToAdd(event.target.value)
                                        }
                                        className="h-9 min-w-0 flex-1 rounded-md border border-input bg-background px-3 text-sm"
                                        aria-label="Parada existente"
                                    >
                                        <option value="">
                                            Selecciona una parada
                                        </option>
                                        {stops
                                            .filter(
                                                (stop) =>
                                                    !form.data.stops.some(
                                                        (selected) =>
                                                            selected.id ===
                                                            stop.id,
                                                    ),
                                            )
                                            .map((stop) => (
                                                <option
                                                    key={stop.id}
                                                    value={stop.id}
                                                >
                                                    {stop.name}
                                                </option>
                                            ))}
                                    </select>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={addStop}
                                        disabled={!stopToAdd}
                                    >
                                        <MapPinPlus className="size-4" />
                                        Agregar
                                    </Button>
                                </div>

                                <ol className="space-y-2">
                                    {selectedStops.map(
                                        ({ routeStop, stop }, index) => (
                                            <li
                                                key={stop.id}
                                                className="flex flex-wrap items-center gap-2 rounded-lg bg-background/50 p-2.5"
                                            >
                                                <span className="grid size-7 place-items-center rounded-full bg-primary text-xs font-extrabold text-primary-foreground">
                                                    {index + 1}
                                                </span>
                                                <span
                                                    className="min-w-32 flex-1 truncate text-sm"
                                                    title={stop.name}
                                                >
                                                    {stop.name}
                                                </span>
                                                <div className="w-24">
                                                    <Input
                                                        type="number"
                                                        min={
                                                            index === 0 ? 0 : 1
                                                        }
                                                        max="65535"
                                                        value={
                                                            routeStop.minutes_from_start
                                                        }
                                                        readOnly={index === 0}
                                                        aria-label={
                                                            'Minutos acumulados en ' +
                                                            stop.name
                                                        }
                                                        onChange={(event) => {
                                                            const nextStops = [
                                                                ...form.data
                                                                    .stops,
                                                            ];
                                                            nextStops[index] = {
                                                                ...nextStops[
                                                                    index
                                                                ],
                                                                minutes_from_start:
                                                                    event.target
                                                                        .value,
                                                            };
                                                            form.setData(
                                                                'stops',
                                                                nextStops,
                                                            );
                                                        }}
                                                        className="h-8"
                                                    />
                                                    <InputError
                                                        className="mt-1 text-xs"
                                                        message={
                                                            errors[
                                                                'stops.' +
                                                                    String(
                                                                        index,
                                                                    ) +
                                                                    '.minutes_from_start'
                                                            ]
                                                        }
                                                    />
                                                </div>
                                                <div className="ml-auto flex">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        disabled={index === 0}
                                                        onClick={() =>
                                                            moveStop(index, -1)
                                                        }
                                                        aria-label={
                                                            'Subir ' + stop.name
                                                        }
                                                    >
                                                        <ArrowUp className="size-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        disabled={
                                                            index ===
                                                            selectedStops.length -
                                                                1
                                                        }
                                                        onClick={() =>
                                                            moveStop(index, 1)
                                                        }
                                                        aria-label={
                                                            'Bajar ' + stop.name
                                                        }
                                                    >
                                                        <ArrowDown className="size-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            replaceStops(
                                                                form.data.stops.filter(
                                                                    (
                                                                        _,
                                                                        stopIndex,
                                                                    ) =>
                                                                        stopIndex !==
                                                                        index,
                                                                ),
                                                            )
                                                        }
                                                        aria-label={
                                                            'Quitar ' +
                                                            stop.name
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
                                            </li>
                                        ),
                                    )}
                                </ol>
                                {!form.data.stops.length && (
                                    <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                        Agrega al menos dos paradas existentes.
                                    </p>
                                )}
                                <InputError message={form.errors.stops} />
                                <InputError
                                    message={form.errors.preview_token}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <section
                        className="min-w-0 xl:col-span-7"
                        aria-label="Mapa de la ruta"
                    >
                        <Card className="overflow-hidden p-0 xl:sticky xl:top-6">
                            <div className="flex flex-wrap items-center justify-between gap-3 border-b p-4">
                                <div className="text-sm text-muted-foreground">
                                    {previewIsCurrent && preview
                                        ? 'Trazado calculado · ' +
                                          (
                                              preview.distance_meters / 1000
                                          ).toFixed(1) +
                                          ' km · ' +
                                          String(
                                              Math.round(
                                                  preview.duration_seconds / 60,
                                              ),
                                          ) +
                                          ' min'
                                        : 'La línea recta es una referencia. Calcula el trazado por calles antes de guardar.'}
                                </div>
                                <Button
                                    type="button"
                                    onClick={calculateTrace}
                                    disabled={
                                        form.processing ||
                                        form.data.stops.length < 2
                                    }
                                >
                                    <Sparkles className="size-4" />
                                    {tracing
                                        ? 'Calculando…'
                                        : 'Calcular trazado'}
                                </Button>
                            </div>
                            <InputError
                                className="px-4 pt-3"
                                message={errors.trace}
                            />
                            <MapContainer
                                center={mapCenter}
                                zoom={13}
                                className="h-[420px] w-full xl:h-[600px]"
                                scrollWheelZoom
                            >
                                <TileLayer
                                    attribution="© OpenStreetMap contributors"
                                    url="https://tile.openstreetmap.org/{z}/{x}/{y}.png"
                                />
                                {mapPositions.length > 1 && (
                                    <Polyline
                                        positions={mapPositions}
                                        pathOptions={{
                                            color: form.data.color,
                                            weight: 6,
                                            dashArray: previewIsCurrent
                                                ? undefined
                                                : '8 10',
                                        }}
                                    />
                                )}
                                {selectedStops.map(({ stop }, index) => (
                                    <CircleMarker
                                        key={stop.id}
                                        center={[stop.latitude, stop.longitude]}
                                        radius={12}
                                        pathOptions={{
                                            color: '#ffffff',
                                            weight: 3,
                                            fillColor: form.data.color,
                                            fillOpacity: 1,
                                        }}
                                    >
                                        <Tooltip
                                            permanent
                                            direction="center"
                                            className="font-bold"
                                        >
                                            {index + 1}
                                        </Tooltip>
                                    </CircleMarker>
                                ))}
                                <FitMap
                                    positions={directPositions}
                                    fallback={campusCenter}
                                />
                            </MapContainer>
                        </Card>
                    </section>
                </div>
            </form>
        </>
    );
}
