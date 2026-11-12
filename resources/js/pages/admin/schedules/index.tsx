import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    Pencil,
    Plus,
    Repeat2,
    Save,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type TransitRoute = {
    id: number;
    number: number;
    name: string;
    color: string;
    campus: string;
    frequency_minutes: number | null;
    service_starts_at: string;
    service_ends_at: string;
};

type Schedule = {
    id: number;
    weekday: number;
    departs_at: string;
};

const days = [
    { value: 1, name: 'Lunes', short: 'L' },
    { value: 2, name: 'Martes', short: 'M' },
    { value: 3, name: 'Miércoles', short: 'M' },
    { value: 4, name: 'Jueves', short: 'J' },
    { value: 5, name: 'Viernes', short: 'V' },
    { value: 6, name: 'Sábado', short: 'S' },
    { value: 7, name: 'Domingo', short: 'D' },
];

function minutesFromTime(value: string): number | null {
    if (!/^\d{2}:\d{2}$/.test(value)) return null;
    const [hour, minute] = value.split(':').map(Number);
    if (hour > 23 || minute > 59) return null;

    return hour * 60 + minute;
}

function timeFromMinutes(minutes: number) {
    return (
        String(Math.floor(minutes / 60)).padStart(2, '0') +
        ':' +
        String(minutes % 60).padStart(2, '0')
    );
}

export default function ScheduleIndex({
    route,
    schedules,
}: {
    route: TransitRoute;
    schedules: Schedule[];
}) {
    const addForm = useForm({ weekday: '1', departs_at: '06:00' });
    const editForm = useForm({ weekday: '1', departs_at: '06:00' });
    const generateForm = useForm({
        weekdays: [1, 2, 3, 4, 5],
        starts_at: route.service_starts_at,
        ends_at: route.service_ends_at,
        interval_minutes: String(route.frequency_minutes ?? ''),
    });
    const [editingId, setEditingId] = useState<number | null>(null);
    const preview = useMemo(() => {
        const start = minutesFromTime(generateForm.data.starts_at);
        const end = minutesFromTime(generateForm.data.ends_at);
        const interval = Number(generateForm.data.interval_minutes);

        if (
            start === null ||
            end === null ||
            end < start ||
            !Number.isInteger(interval) ||
            interval < 1
        ) {
            return { total: 0, fresh: 0, existing: 0 };
        }

        const existingKeys = new Set(
            schedules.map(
                (schedule) =>
                    String(schedule.weekday) + '|' + schedule.departs_at,
            ),
        );
        const keys: string[] = [];
        for (const weekday of generateForm.data.weekdays) {
            for (let minutes = start; minutes <= end; minutes += interval) {
                keys.push(String(weekday) + '|' + timeFromMinutes(minutes));
            }
        }
        const existing = keys.filter((key) => existingKeys.has(key)).length;

        return {
            total: keys.length,
            fresh: keys.length - existing,
            existing,
        };
    }, [generateForm.data, schedules]);

    const beginEdit = (schedule: Schedule) => {
        setEditingId(schedule.id);
        editForm.setData({
            weekday: String(schedule.weekday),
            departs_at: schedule.departs_at,
        });
        editForm.clearErrors();
    };

    const toggleDay = (weekday: number) => {
        generateForm.setData(
            'weekdays',
            generateForm.data.weekdays.includes(weekday)
                ? generateForm.data.weekdays.filter((day) => day !== weekday)
                : [...generateForm.data.weekdays, weekday].sort(
                      (left, right) => left - right,
                  ),
        );
    };

    return (
        <div className="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <Head title="Horarios de salida" />
            <header>
                <Link
                    href="/admin/routes"
                    className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Volver a rutas y paradas
                </Link>
                <div className="mt-3 flex items-start gap-3">
                    <span
                        className="grid size-11 shrink-0 place-items-center rounded-lg text-base font-extrabold text-ink"
                        style={{ backgroundColor: route.color }}
                    >
                        {route.number}
                    </span>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                            Horarios de salida
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {route.name} · {route.campus}
                        </p>
                    </div>
                </div>
            </header>

            <div className="grid gap-6 xl:grid-cols-12">
                <Card className="min-w-0 xl:col-span-8">
                    <CardHeader className="flex flex-row items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">Salidas semanales</h2>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {schedules.length} horarios registrados
                            </p>
                        </div>
                        <CalendarClock className="size-6 text-primary" />
                    </CardHeader>
                    <CardContent>
                        {schedules.length === 0 ? (
                            <p className="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground">
                                Sin horarios registrados
                            </p>
                        ) : (
                            <div className="space-y-6">
                                {days.map((day) => {
                                    const daySchedules = schedules.filter(
                                        (schedule) =>
                                            schedule.weekday === day.value,
                                    );
                                    if (!daySchedules.length) return null;

                                    return (
                                        <section key={day.value}>
                                            <h3 className="mb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                {day.name}
                                            </h3>
                                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                {daySchedules.map((schedule) =>
                                                    editingId ===
                                                    schedule.id ? (
                                                        <form
                                                            key={schedule.id}
                                                            onSubmit={(
                                                                event,
                                                            ) => {
                                                                event.preventDefault();
                                                                editForm.patch(
                                                                    '/admin/routes/' +
                                                                        String(
                                                                            route.id,
                                                                        ) +
                                                                        '/schedules/' +
                                                                        String(
                                                                            schedule.id,
                                                                        ),
                                                                    {
                                                                        preserveScroll: true,
                                                                        onSuccess:
                                                                            () =>
                                                                                setEditingId(
                                                                                    null,
                                                                                ),
                                                                    },
                                                                );
                                                            }}
                                                            className="col-span-full grid gap-3 rounded-lg border border-primary/40 bg-primary/5 p-3 sm:grid-cols-[1fr_1fr_auto]"
                                                        >
                                                            <select
                                                                aria-label="Día de la salida"
                                                                value={
                                                                    editForm
                                                                        .data
                                                                        .weekday
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    editForm.setData(
                                                                        'weekday',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                                            >
                                                                {days.map(
                                                                    (
                                                                        option,
                                                                    ) => (
                                                                        <option
                                                                            key={
                                                                                option.value
                                                                            }
                                                                            value={
                                                                                option.value
                                                                            }
                                                                        >
                                                                            {
                                                                                option.name
                                                                            }
                                                                        </option>
                                                                    ),
                                                                )}
                                                            </select>
                                                            <Input
                                                                type="time"
                                                                aria-label="Hora de salida"
                                                                value={
                                                                    editForm
                                                                        .data
                                                                        .departs_at
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    editForm.setData(
                                                                        'departs_at',
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                            />
                                                            <div className="flex gap-1">
                                                                <Button
                                                                    type="submit"
                                                                    size="icon"
                                                                    disabled={
                                                                        editForm.processing
                                                                    }
                                                                    aria-label="Guardar horario"
                                                                >
                                                                    <Save className="size-4" />
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    size="icon"
                                                                    variant="ghost"
                                                                    onClick={() =>
                                                                        setEditingId(
                                                                            null,
                                                                        )
                                                                    }
                                                                    aria-label="Cancelar edición"
                                                                >
                                                                    <X className="size-4" />
                                                                </Button>
                                                            </div>
                                                            <InputError
                                                                className="sm:col-span-full"
                                                                message={
                                                                    editForm
                                                                        .errors
                                                                        .weekday ??
                                                                    editForm
                                                                        .errors
                                                                        .departs_at
                                                                }
                                                            />
                                                        </form>
                                                    ) : (
                                                        <div
                                                            key={schedule.id}
                                                            className="flex items-center justify-between gap-3 rounded-lg border bg-background/50 px-3 py-2"
                                                        >
                                                            <time
                                                                dateTime={
                                                                    schedule.departs_at
                                                                }
                                                                className="font-semibold tabular-nums"
                                                            >
                                                                {
                                                                    schedule.departs_at
                                                                }
                                                            </time>
                                                            <Button
                                                                type="button"
                                                                size="icon"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    beginEdit(
                                                                        schedule,
                                                                    )
                                                                }
                                                                aria-label={
                                                                    'Editar salida de ' +
                                                                    day.name +
                                                                    ' a las ' +
                                                                    schedule.departs_at
                                                                }
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </section>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <aside className="min-w-0 space-y-5 xl:col-span-4">
                    <Card>
                        <CardHeader>
                            <h2 className="font-bold">Generar salidas</h2>
                            <p className="text-xs text-muted-foreground">
                                Crea horarios recurrentes para varios días.
                            </p>
                        </CardHeader>
                        <CardContent>
                            <form
                                className="space-y-5"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    generateForm.post(
                                        '/admin/routes/' +
                                            String(route.id) +
                                            '/schedules/generate',
                                        { preserveScroll: true },
                                    );
                                }}
                            >
                                <div className="grid grid-cols-3 gap-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="interval_minutes">
                                            Intervalo (min)
                                        </Label>
                                        <Input
                                            id="interval_minutes"
                                            type="number"
                                            min="1"
                                            step="1"
                                            required
                                            value={
                                                generateForm.data
                                                    .interval_minutes
                                            }
                                            onChange={(event) =>
                                                generateForm.setData(
                                                    'interval_minutes',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="starts_at">Desde</Label>
                                        <Input
                                            id="starts_at"
                                            type="time"
                                            required
                                            value={generateForm.data.starts_at}
                                            onChange={(event) =>
                                                generateForm.setData(
                                                    'starts_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="ends_at">Hasta</Label>
                                        <Input
                                            id="ends_at"
                                            type="time"
                                            required
                                            value={generateForm.data.ends_at}
                                            onChange={(event) =>
                                                generateForm.setData(
                                                    'ends_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <InputError
                                    message={
                                        generateForm.errors.interval_minutes ??
                                        generateForm.errors.starts_at ??
                                        generateForm.errors.ends_at
                                    }
                                />

                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium">
                                        Días
                                    </legend>
                                    <div className="grid grid-cols-7 gap-1">
                                        {days.map((day) => (
                                            <label
                                                key={day.value}
                                                title={day.name}
                                                className="cursor-pointer"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={generateForm.data.weekdays.includes(
                                                        day.value,
                                                    )}
                                                    onChange={() =>
                                                        toggleDay(day.value)
                                                    }
                                                    className="peer sr-only"
                                                />
                                                <span className="block rounded-md border py-2 text-center text-xs font-semibold text-muted-foreground peer-checked:border-primary peer-checked:bg-primary/15 peer-checked:text-primary">
                                                    {day.short}
                                                    <span className="sr-only">
                                                        {day.name}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError
                                        className="mt-2"
                                        message={generateForm.errors.weekdays}
                                    />
                                </fieldset>

                                <p className="rounded-lg bg-background/50 p-3 text-sm text-muted-foreground">
                                    Se crearían{' '}
                                    <strong className="text-foreground">
                                        {preview.fresh} salidas nuevas
                                    </strong>
                                    . {preview.existing} ya existen de{' '}
                                    {preview.total} calculadas.
                                </p>
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={
                                        generateForm.processing ||
                                        preview.total === 0
                                    }
                                >
                                    <Repeat2 className="size-4" />
                                    {generateForm.processing
                                        ? 'Generando…'
                                        : 'Generar salidas'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <h2 className="font-bold">Agregar una salida</h2>
                        </CardHeader>
                        <CardContent>
                            <form
                                className="space-y-4"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    addForm.post(
                                        '/admin/routes/' +
                                            String(route.id) +
                                            '/schedules',
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => addForm.reset(),
                                        },
                                    );
                                }}
                            >
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="weekday">Día</Label>
                                        <select
                                            id="weekday"
                                            value={addForm.data.weekday}
                                            onChange={(event) =>
                                                addForm.setData(
                                                    'weekday',
                                                    event.target.value,
                                                )
                                            }
                                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            {days.map((day) => (
                                                <option
                                                    key={day.value}
                                                    value={day.value}
                                                >
                                                    {day.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="departs_at">Hora</Label>
                                        <Input
                                            id="departs_at"
                                            type="time"
                                            required
                                            value={addForm.data.departs_at}
                                            onChange={(event) =>
                                                addForm.setData(
                                                    'departs_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <InputError
                                    message={
                                        addForm.errors.weekday ??
                                        addForm.errors.departs_at
                                    }
                                />
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    className="w-full"
                                    disabled={addForm.processing}
                                >
                                    <Plus className="size-4" />
                                    {addForm.processing
                                        ? 'Agregando…'
                                        : 'Agregar salida'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    );
}
