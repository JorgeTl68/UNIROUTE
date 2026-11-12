import { Form, Head, Link, usePage } from '@inertiajs/react';
import { BellRing, Building2, IdCard } from 'lucide-react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { send } from '@/routes/verification';

const selectClass =
    'mt-1 block h-10 w-full rounded-md border border-border bg-background px-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50';

/** Minutos de aviso que se pueden elegir para "Avisarme al llegar". */
const alarmOptions = [2, 3, 5, 10, 15];

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth, campuses } = usePage().props;
    const user = auth.user;
    const minutes = [
        ...new Set([...alarmOptions, user.alarm_minutes_before]),
    ].sort((a, b) => a - b);

    return (
        <>
            <Head title="Perfil y Ajustes" />

            <h1 className="sr-only">Perfil y Ajustes</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Perfil"
                    description="Tu nombre, tu correo, tu campus y cuándo quieres los avisos"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Nombre completo"
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    Correo electrónico
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="tu.correo@ejemplo.com"
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Tu correo no está verificado.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="font-semibold text-primary underline-offset-4 hover:underline"
                                            >
                                                Reenviar el correo de
                                                verificación.
                                            </Link>
                                        </p>
                                        {status ===
                                            'verification-link-sent' && (
                                            <p className="mt-2 text-sm font-medium text-live">
                                                Te mandamos un nuevo enlace de
                                                verificación.
                                            </p>
                                        )}
                                    </div>
                                )}

                            {user.student_id && (
                                <div className="grid gap-2">
                                    <span className="text-sm font-medium">
                                        Matrícula
                                    </span>
                                    <p className="flex items-center gap-2 rounded-md border border-border bg-background/50 px-3 py-2.5 text-sm text-muted-foreground">
                                        <IdCard className="size-4" />
                                        {user.student_id}
                                        <span className="ml-auto text-xs">
                                            No se puede cambiar
                                        </span>
                                    </p>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="campus_id"
                                    className="flex items-center gap-2"
                                >
                                    <Building2 className="size-4 text-gold" />
                                    Campus
                                </Label>
                                <select
                                    id="campus_id"
                                    name="campus_id"
                                    defaultValue={user.campus_id ?? ''}
                                    className={selectClass}
                                >
                                    <option value="">Sin campus</option>
                                    {campuses.map((campus) => (
                                        <option
                                            key={campus.id}
                                            value={campus.id}
                                        >
                                            {campus.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.campus_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="alarm_minutes_before"
                                    className="flex items-center gap-2"
                                >
                                    <BellRing className="size-4 text-primary" />
                                    Aviso de llegada por defecto
                                </Label>
                                <select
                                    id="alarm_minutes_before"
                                    name="alarm_minutes_before"
                                    defaultValue={user.alarm_minutes_before}
                                    className={selectClass}
                                >
                                    {minutes.map((value) => (
                                        <option key={value} value={value}>
                                            {value} minutos antes
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.alarm_minutes_before}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    {processing ? 'Guardando…' : 'Guardar'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}
