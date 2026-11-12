import { useForm } from '@inertiajs/react';
import { BellPlus, LoaderCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { RoutePoint } from '@/types/transit';

const fieldClass =
    'h-10 w-full rounded-md border border-border bg-background px-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50';

/**
 * Diálogo "Avisarme al llegar" (S4-02): parada de la ruta y minutos antes.
 */
export function ArrivalAlarmDialog({
    routeId,
    stops,
    defaultStopId,
    defaultMinutes,
    children,
}: {
    routeId: number;
    stops: Pick<RoutePoint, 'id' | 'name'>[];
    defaultStopId?: number;
    defaultMinutes: number;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        route_id: routeId,
        stop_id: defaultStopId ?? stops[0]?.id ?? 0,
        minutes_before: defaultMinutes,
    });

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                setOpen(value);
                if (value) {
                    form.setData({
                        route_id: routeId,
                        stop_id: defaultStopId ?? stops[0]?.id ?? 0,
                        minutes_before: defaultMinutes,
                    });
                    form.clearErrors();
                }
            }}
        >
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="bg-card sm:max-w-md">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/arrival-alarms', {
                            preserveScroll: true,
                            onSuccess: () => setOpen(false),
                        });
                    }}
                    className="space-y-5"
                >
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <BellPlus className="size-5 text-primary" />
                            Avisarme al llegar
                        </DialogTitle>
                        <DialogDescription>
                            Te mandamos una notificación cuando la unidad esté a
                            esos minutos de tu parada.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor="alarm-stop">Parada</Label>
                        <select
                            id="alarm-stop"
                            value={form.data.stop_id}
                            onChange={(event) =>
                                form.setData(
                                    'stop_id',
                                    Number(event.target.value),
                                )
                            }
                            className={fieldClass}
                        >
                            {stops.map((stop) => (
                                <option key={stop.id} value={stop.id}>
                                    {stop.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.stop_id} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="alarm-minutes">Minutos antes</Label>
                        <select
                            id="alarm-minutes"
                            value={form.data.minutes_before}
                            onChange={(event) =>
                                form.setData(
                                    'minutes_before',
                                    Number(event.target.value),
                                )
                            }
                            className={fieldClass}
                        >
                            {[...new Set([2, 3, 5, 10, 15, defaultMinutes])]
                                .sort((a, b) => a - b)
                                .map((minutes) => (
                                    <option key={minutes} value={minutes}>
                                        {minutes} minutos
                                    </option>
                                ))}
                        </select>
                        <InputError message={form.errors.minutes_before} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : (
                                <BellPlus className="size-4" />
                            )}
                            {form.processing ? 'Guardando…' : 'Guardar alarma'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
