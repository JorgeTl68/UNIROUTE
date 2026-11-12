import { useForm, usePage } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const fields = [
    {
        key: 'name',
        label: 'Nombre completo',
        type: 'text',
        placeholder: 'Nombre y apellidos',
    },
    {
        key: 'email',
        label: 'Correo electrónico',
        type: 'email',
        placeholder: 'conductor@ejemplo.com',
    },
    {
        key: 'license_number',
        label: 'Número de licencia',
        type: 'text',
        placeholder: 'Licencia de conducir',
    },
    {
        key: 'hired_at',
        label: 'Fecha de contratación',
        type: 'date',
        placeholder: '',
    },
] as const;

/** Alta de conductor (S3-05) en un modal desde la lista de conductores. */
export function DriverFormDialog({ children }: { children: ReactNode }) {
    const { campuses } = usePage().props;
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: '',
        email: '',
        license_number: '',
        hired_at: '',
        campus_id: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => {
                setOpen(value);
                if (value) {
                    form.reset();
                    form.clearErrors();
                }
            }}
        >
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto bg-card sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Registrar conductor</DialogTitle>
                    <DialogDescription>
                        Crea la cuenta de acceso y su perfil de conducción.
                    </DialogDescription>
                </DialogHeader>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/admin/fleet/drivers', {
                            preserveScroll: true,
                            onSuccess: () => setOpen(false),
                            onError: () =>
                                form.reset('password', 'password_confirmation'),
                        });
                    }}
                >
                    <fieldset
                        disabled={form.processing}
                        className="grid gap-5 sm:grid-cols-2"
                    >
                        <legend className="sr-only">Cuenta y licencia</legend>
                        {fields.map(({ key, label, type, placeholder }) => (
                            <div key={key} className="grid gap-2">
                                <Label htmlFor={key}>{label} *</Label>
                                <Input
                                    id={key}
                                    name={key}
                                    type={type}
                                    required
                                    autoFocus={key === 'name'}
                                    maxLength={
                                        type === 'date' ? undefined : 255
                                    }
                                    value={form.data[key]}
                                    onChange={(event) =>
                                        form.setData(key, event.target.value)
                                    }
                                    placeholder={placeholder}
                                    autoComplete={
                                        key === 'name'
                                            ? 'name'
                                            : key === 'email'
                                              ? 'email'
                                              : 'off'
                                    }
                                    aria-invalid={Boolean(form.errors[key])}
                                    aria-describedby={`${key}-error`}
                                />
                                <InputError
                                    id={`${key}-error`}
                                    message={form.errors[key]}
                                />
                            </div>
                        ))}
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="campus_id">Campus (opcional)</Label>
                            <select
                                id="campus_id"
                                value={form.data.campus_id}
                                onChange={(event) =>
                                    form.setData(
                                        'campus_id',
                                        event.target.value,
                                    )
                                }
                                className="h-10 rounded-lg border border-input bg-background px-3 text-sm focus:ring-2 focus:ring-ring"
                            >
                                <option value="">Sin campus asignado</option>
                                {campuses.map((campus) => (
                                    <option key={campus.id} value={campus.id}>
                                        {campus.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.campus_id} />
                        </div>
                        {(['password', 'password_confirmation'] as const).map(
                            (key) => (
                                <div key={key} className="grid gap-2">
                                    <Label htmlFor={key}>
                                        {key === 'password'
                                            ? 'Contraseña inicial'
                                            : 'Confirmar contraseña'}{' '}
                                        *
                                    </Label>
                                    <PasswordInput
                                        id={key}
                                        name={key}
                                        required
                                        minLength={8}
                                        autoComplete="new-password"
                                        value={form.data[key]}
                                        onChange={(event) =>
                                            form.setData(
                                                key,
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Mínimo 8 caracteres"
                                        aria-invalid={Boolean(form.errors[key])}
                                    />
                                    <InputError message={form.errors[key]} />
                                </div>
                            ),
                        )}
                        <p className="text-xs leading-relaxed text-muted-foreground sm:col-span-2">
                            La cuenta tendrá rol de conductor. Recibirá un
                            correo de verificación y deberá confirmar su
                            dirección antes de ingresar.
                        </p>
                    </fieldset>
                    <DialogFooter className="mt-6">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing}>
                            <Save />
                            {form.processing
                                ? 'Guardando…'
                                : 'Guardar conductor'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
