import { router, useForm, usePage } from '@inertiajs/react';
import {
    CarFront,
    CircleHelp,
    HeartPulse,
    LoaderCircle,
    LocateFixed,
    Phone,
    Send,
    ShieldAlert,
    Siren,
    Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

const categoryIcons: Record<string, { icon: typeof Siren; className: string }> =
    {
        accident: { icon: CarFront, className: 'text-destructive' },
        breakdown: { icon: Wrench, className: 'text-warning' },
        safety: { icon: ShieldAlert, className: 'text-destructive' },
        health: { icon: HeartPulse, className: 'text-destructive' },
        other: { icon: CircleHelp, className: 'text-muted-foreground' },
    };

const fieldClass =
    'w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50';

type EmergencyForm = {
    category: string;
    route_id: number | '';
    message: string;
    latitude: number | null;
    longitude: number | null;
};

/**
 * Modal "Reportar emergencia" (S4-06). Se abre desde Soporte en Ruta, el
 * detalle de una ruta y la pantalla del conductor. Las rutas y categorías se
 * piden al abrirlo (prop compartida opcional "emergency").
 */
export function EmergencyDialog({
    routeId,
    children,
}: {
    routeId?: number;
    children: ReactNode;
}) {
    const { emergency } = usePage().props;
    const [open, setOpen] = useState(false);
    const [locating, setLocating] = useState(false);
    const [locationError, setLocationError] = useState<string | null>(null);
    const form = useForm<EmergencyForm>({
        category: 'accident',
        route_id: routeId ?? '',
        message: '',
        latitude: null,
        longitude: null,
    });

    const openDialog = (value: boolean) => {
        setOpen(value);

        if (!value) return;

        form.reset();
        form.clearErrors();
        setLocationError(null);
        router.reload({
            only: ['emergency'],
            onSuccess: (page) => {
                const data = page.props.emergency;

                form.setData(
                    'route_id',
                    routeId ?? data?.driver_route_id ?? '',
                );
            },
        });
    };

    const shareLocation = () => {
        if (!navigator.geolocation) {
            setLocationError('Tu navegador no permite compartir la ubicación.');

            return;
        }

        setLocating(true);
        setLocationError(null);
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                form.setData((data) => ({
                    ...data,
                    latitude: coords.latitude,
                    longitude: coords.longitude,
                }));
                setLocating(false);
            },
            () => {
                setLocationError(
                    'No pudimos obtener tu ubicación. Describe dónde estás en el mensaje.',
                );
                setLocating(false);
            },
            { enableHighAccuracy: true, timeout: 10_000 },
        );
    };

    return (
        <Dialog open={open} onOpenChange={openDialog}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto bg-card sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Siren className="size-5 text-destructive" />
                        Reportar emergencia
                    </DialogTitle>
                    <DialogDescription>
                        El área de transporte recibe tu reporte al momento.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-3 rounded-xl border border-destructive/40 bg-destructive/10 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-foreground">
                        <span className="font-bold text-white">
                            Si hay riesgo para alguien, llama al 911.
                        </span>{' '}
                        Después repórtalo aquí.
                    </p>
                    <a
                        href="tel:911"
                        className="flex shrink-0 items-center justify-center gap-2 rounded-md bg-destructive px-4 py-2 text-sm font-bold text-destructive-foreground"
                    >
                        <Phone className="size-4" />
                        Llamar al 911
                    </a>
                </div>

                {emergency === undefined ? (
                    <div className="flex justify-center py-8">
                        <LoaderCircle className="size-6 animate-spin text-muted-foreground" />
                    </div>
                ) : (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/emergency', {
                                preserveScroll: true,
                                onSuccess: () => setOpen(false),
                            });
                        }}
                        className="space-y-4"
                        noValidate
                    >
                        <fieldset>
                            <legend className="text-sm font-semibold text-white">
                                ¿Qué pasó?
                            </legend>
                            <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                {emergency.categories.map((category) => {
                                    const { icon: Icon, className } =
                                        categoryIcons[category.value] ??
                                        categoryIcons.other;

                                    return (
                                        <label
                                            key={category.value}
                                            className="cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                name="category"
                                                value={category.value}
                                                checked={
                                                    form.data.category ===
                                                    category.value
                                                }
                                                onChange={() =>
                                                    form.setData(
                                                        'category',
                                                        category.value,
                                                    )
                                                }
                                                className="peer sr-only"
                                            />
                                            <span className="flex h-full items-center gap-2 rounded-xl border border-border bg-background/50 p-3 text-sm font-semibold text-foreground peer-checked:border-primary peer-checked:bg-primary/10 peer-focus-visible:ring-2 peer-focus-visible:ring-ring">
                                                <Icon
                                                    className={cn(
                                                        'size-4 shrink-0',
                                                        className,
                                                    )}
                                                />
                                                {category.label}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            <InputError
                                className="mt-2"
                                message={form.errors.category}
                            />
                        </fieldset>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <label className="block space-y-1.5">
                                <span className="text-sm font-semibold text-foreground">
                                    Ruta
                                </span>
                                <select
                                    value={form.data.route_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'route_id',
                                            event.target.value === ''
                                                ? ''
                                                : Number(event.target.value),
                                        )
                                    }
                                    className={fieldClass}
                                >
                                    <option value="">
                                        No estoy en una ruta
                                    </option>
                                    {emergency.routes.map((route) => (
                                        <option key={route.id} value={route.id}>
                                            Ruta {route.number} · {route.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.route_id} />
                            </label>
                            <div className="space-y-1.5">
                                <span className="text-sm font-semibold text-foreground">
                                    Ubicación
                                </span>
                                <button
                                    type="button"
                                    onClick={shareLocation}
                                    disabled={locating}
                                    className="flex w-full items-center justify-center gap-2 rounded-md border border-border bg-secondary py-2.5 text-sm font-semibold text-foreground hover:bg-secondary/70"
                                >
                                    {locating ? (
                                        <LoaderCircle className="size-4 animate-spin" />
                                    ) : (
                                        <LocateFixed className="size-4" />
                                    )}
                                    {form.data.latitude !== null
                                        ? 'Ubicación agregada'
                                        : 'Compartir mi ubicación'}
                                </button>
                                {locationError && (
                                    <p className="text-xs text-warning">
                                        {locationError}
                                    </p>
                                )}
                            </div>
                        </div>

                        <label className="block space-y-1.5">
                            <span className="text-sm font-semibold text-foreground">
                                Cuéntanos qué está pasando
                            </span>
                            <textarea
                                value={form.data.message}
                                onChange={(event) =>
                                    form.setData('message', event.target.value)
                                }
                                rows={3}
                                maxLength={1000}
                                placeholder="Por ejemplo: la unidad se detuvo en Blvd. Fundadores y sale humo del motor."
                                className={fieldClass}
                                aria-invalid={Boolean(form.errors.message)}
                            />
                            <InputError message={form.errors.message} />
                        </label>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="flex w-full items-center justify-center gap-2 rounded-md bg-destructive py-3 text-sm font-bold text-destructive-foreground shadow-lg shadow-black/30 active:scale-[0.98] disabled:opacity-60"
                        >
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : (
                                <Send className="size-4" />
                            )}
                            {form.processing ? 'Enviando…' : 'Enviar reporte'}
                        </button>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
