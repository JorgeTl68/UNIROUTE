import { Head, Link, useForm } from '@inertiajs/react';
import { Save, UserRoundPlus } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function CreateDriver({
    campuses,
}: {
    campuses: { id: number; name: string }[];
}) {
    const form = useForm({
        name: '',
        email: '',
        license_number: '',
        hired_at: '',
        campus_id: '',
        password: '',
        password_confirmation: '',
    });
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
    return (
        <>
            <Head title="Registrar conductor" />
            <main className="min-h-full bg-background p-4 sm:p-8">
                <div className="mx-auto max-w-3xl space-y-6">
                    <Link
                        href="/admin/fleet/drivers"
                        className="text-sm text-primary"
                    >
                        ← Volver a conductores
                    </Link>
                    <header>
                        <p className="mb-2 text-xs tracking-widest text-primary uppercase">
                            UniRoute · Administración
                        </p>
                        <h1 className="text-3xl font-bold">
                            Registrar conductor
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Crea la cuenta de acceso y su perfil de conducción.
                        </p>
                    </header>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/admin/fleet/drivers', {
                                onError: () =>
                                    form.reset(
                                        'password',
                                        'password_confirmation',
                                    ),
                            });
                        }}
                        className="rounded-xl border border-border bg-card"
                    >
                        <div className="flex items-center gap-3 border-b border-border p-6">
                            <UserRoundPlus className="size-5 text-primary" />
                            <h2 className="font-semibold">
                                Datos del conductor
                            </h2>
                        </div>
                        <fieldset
                            disabled={form.processing}
                            className="grid gap-5 p-6 sm:grid-cols-2"
                        >
                            <legend className="sr-only">
                                Cuenta y licencia
                            </legend>
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
                                            form.setData(
                                                key,
                                                event.target.value,
                                            )
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
                                <Label htmlFor="campus_id">
                                    Campus (opcional)
                                </Label>
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
                                    <option value="">
                                        Sin campus asignado
                                    </option>
                                    {campuses.map((campus) => (
                                        <option
                                            key={campus.id}
                                            value={campus.id}
                                        >
                                            {campus.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.campus_id} />
                            </div>
                            {(
                                ['password', 'password_confirmation'] as const
                            ).map((key) => (
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
                            ))}
                            <p className="text-xs leading-relaxed text-muted-foreground sm:col-span-2">
                                La cuenta tendrá rol de conductor. Recibirá un
                                correo de verificación y deberá confirmar su
                                dirección antes de ingresar. En desarrollo, el
                                correo se consulta en el registro local.
                            </p>
                        </fieldset>
                        <footer className="flex justify-end gap-3 border-t border-border p-6">
                            <Button variant="outline" asChild>
                                <Link href="/admin/fleet/drivers">
                                    Cancelar
                                </Link>
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                <Save />
                                {form.processing
                                    ? 'Guardando…'
                                    : 'Guardar conductor'}
                            </Button>
                        </footer>
                    </form>
                </div>
            </main>
        </>
    );
}
