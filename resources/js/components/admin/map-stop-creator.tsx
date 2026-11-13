import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useMapEvents } from 'react-leaflet';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

export function MapStopCreator({
    onCreated,
}: {
    onCreated: (id: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: '',
        latitude: 0,
        longitude: 0,
        has_shelter: false,
    });
    useMapEvents({
        click(event) {
            if (open || form.processing) return;
            form.clearErrors();
            form.setData({
                name: '',
                latitude: Number(event.latlng.lat.toFixed(7)),
                longitude: Number(event.latlng.lng.toFixed(7)),
                has_shelter: false,
            });
            setOpen(true);
        },
    });
    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                if (!form.processing) setOpen(value);
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Nueva parada en el mapa</DialogTitle>
                    <DialogDescription>
                        Se guardará en el catálogo y se agregará al recorrido
                        actual. Revisa los minutos acumulados antes de calcular
                        el trazado.
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        form.post('/admin/stops', {
                            preserveScroll: true,
                            preserveState: true,
                            onSuccess(page) {
                                const id = page.props.createdStop;
                                if (typeof id === 'number') onCreated(id);
                                setOpen(false);
                                form.reset();
                            },
                        });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="new-stop-name">
                            Nombre de la parada
                        </Label>
                        <Input
                            id="new-stop-name"
                            autoFocus
                            required
                            maxLength={255}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Coordenadas: {form.data.latitude}, {form.data.longitude}
                    </p>
                    <InputError
                        message={form.errors.latitude ?? form.errors.longitude}
                    />
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.has_shelter}
                            onChange={(event) =>
                                form.setData(
                                    'has_shelter',
                                    event.target.checked,
                                )
                            }
                        />
                        Tiene refugio o techo
                    </label>
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.processing}
                            onClick={() => setOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing
                                ? 'Guardando…'
                                : 'Guardar y agregar parada'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
