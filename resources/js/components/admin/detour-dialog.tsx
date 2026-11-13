import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import type { Incident } from '@/types/transit';
export function DetourDialog({
    incident,
    stops,
}: {
    incident: Incident;
    stops: { id: number; name: string }[];
}) {
    const [open, setOpen] = useState(false);
    const form = useForm<{ skipped_stop_ids: number[] }>({
        skipped_stop_ids: [],
    });
    if (incident.detour?.active)
        return (
            <p className="rounded-lg border border-warning/40 bg-warning/10 px-3 py-2 text-xs text-warning">
                Desvío activo: {incident.detour.skipped_stop_ids.length} paradas
                omitidas. Al resolver esta incidencia se restaurará el recorrido
                original.
            </p>
        );
    if (incident.type !== 'detour' || incident.resolved_at || !incident.route)
        return null;
    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Desviar unidades
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Desvío temporal · Ruta {incident.route.number}
                    </DialogTitle>
                    <DialogDescription>
                        Selecciona las paradas que no pueden atenderse. Se
                        recalculará por calles el recorrido de esta ruta para
                        todas sus unidades; se restaurará al resolver la
                        incidencia. Deben quedar al menos dos paradas.
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(`/admin/incidents/${incident.id}/detour`, {
                            preserveScroll: true,
                            onSuccess: () => {
                                setOpen(false);
                                form.reset();
                            },
                        });
                    }}
                >
                    <fieldset
                        disabled={form.processing}
                        className="max-h-64 space-y-2 overflow-y-auto"
                    >
                        <legend className="mb-2 text-sm font-semibold">
                            Paradas afectadas
                        </legend>
                        {stops.map((stop, index) => (
                            <label
                                key={stop.id}
                                className="flex items-center gap-2 rounded-lg border border-border p-3 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    checked={form.data.skipped_stop_ids.includes(
                                        stop.id,
                                    )}
                                    onChange={(event) =>
                                        form.setData(
                                            'skipped_stop_ids',
                                            event.target.checked
                                                ? [
                                                      ...form.data
                                                          .skipped_stop_ids,
                                                      stop.id,
                                                  ]
                                                : form.data.skipped_stop_ids.filter(
                                                      (id) => id !== stop.id,
                                                  ),
                                        )
                                    }
                                />
                                {index + 1}. {stop.name}
                            </label>
                        ))}
                    </fieldset>
                    {Object.entries(form.errors).map(([key, message]) => (
                        <InputError key={key} message={message} />
                    ))}
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setOpen(false)}
                            disabled={form.processing}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                !form.data.skipped_stop_ids.length ||
                                stops.length -
                                    form.data.skipped_stop_ids.length <
                                    2
                            }
                        >
                            {form.processing
                                ? 'Calculando desvío…'
                                : 'Aplicar desvío temporal'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
