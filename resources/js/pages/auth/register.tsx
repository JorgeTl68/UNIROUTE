import { Form, Head } from '@inertiajs/react';
import {
    Building2,
    ContactRound,
    LockKeyhole,
    Mail,
    MailCheck,
    UserRound,
    UserRoundPlus,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
    campuses: { id: number; name: string }[];
};

export default function Register({ passwordRules, campuses }: Props) {
    const [password, setPassword] = useState('');
    const strength =
        password.length === 0
            ? 0
            : Number(password.length >= 8) +
              Number(/[a-z]/.test(password) && /[A-Z]/.test(password)) +
              Number(/\d/.test(password)) +
              Number(/[^a-zA-Z0-9]/.test(password));
    return (
        <>
            <Head title="Crear cuenta" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                onSuccess={() => setPassword('')}
                disableWhileProcessing
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nombre completo</Label>
                            <div className="relative">
                                <UserRound
                                    aria-hidden="true"
                                    className="auth-field-icon"
                                />
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    placeholder="Nombre y apellidos"
                                    className="pl-11"
                                />
                            </div>
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="student_id">Matrícula</Label>
                                <div className="relative">
                                    <ContactRound
                                        aria-hidden="true"
                                        className="auth-field-icon"
                                    />
                                    <Input
                                        id="student_id"
                                        name="student_id"
                                        required
                                        maxLength={255}
                                        placeholder="Tu matrícula"
                                        className="pl-11"
                                    />
                                </div>
                                <InputError message={errors.student_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="campus_id">Campus</Label>
                                <div className="relative">
                                    <Building2
                                        aria-hidden="true"
                                        className="auth-field-icon"
                                    />
                                    <select
                                        id="campus_id"
                                        name="campus_id"
                                        required
                                        defaultValue=""
                                        className="h-11 w-full rounded-lg border border-input bg-card pr-7 pl-10 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                                    >
                                        <option value="" disabled>
                                            Elige uno
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
                                </div>
                                <InputError message={errors.campus_id} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo electrónico</Label>
                            <div className="relative">
                                <Mail
                                    aria-hidden="true"
                                    className="auth-field-icon"
                                />
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    placeholder="tu.correo@ejemplo.com"
                                    className="pl-11"
                                />
                            </div>
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password">Contraseña</Label>
                            <div className="relative">
                                <LockKeyhole
                                    aria-hidden="true"
                                    className="auth-field-icon"
                                />
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    minLength={8}
                                    autoComplete="new-password"
                                    placeholder="Mínimo 8 caracteres"
                                    passwordrules={passwordRules}
                                    onChange={(event) =>
                                        setPassword(event.target.value)
                                    }
                                    className="pl-11"
                                />
                            </div>
                            <div aria-hidden="true" className="flex gap-1">
                                {[1, 2, 3, 4].map((level) => (
                                    <span
                                        key={level}
                                        className={`h-1 flex-1 rounded-full ${strength >= level ? 'bg-live' : 'bg-secondary'}`}
                                    />
                                ))}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Usa al menos 8 caracteres; mejor si combinas
                                letras y números.
                            </p>
                            <InputError message={errors.password} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirmar contraseña
                            </Label>
                            <div className="relative">
                                <LockKeyhole
                                    aria-hidden="true"
                                    className="auth-field-icon"
                                />
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    placeholder="Repite tu contraseña"
                                    passwordrules={passwordRules}
                                    className="pl-11"
                                />
                            </div>
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={processing || campuses.length === 0}
                            data-test="register-user-button"
                            className="w-full"
                        >
                            {processing ? <Spinner /> : <UserRoundPlus />}Crear
                            cuenta
                        </Button>
                        {campuses.length === 0 && (
                            <p
                                role="status"
                                className="text-sm text-destructive"
                            >
                                No hay campus disponibles. Contacta al área de
                                transporte.
                            </p>
                        )}
                    </>
                )}
            </Form>
            <p className="mt-4 flex items-start gap-2 text-xs leading-relaxed text-muted-foreground">
                <MailCheck className="size-4 shrink-0 text-live" />
                Te enviaremos un correo para confirmar tu cuenta antes de
                entrar.
            </p>
            <p className="mt-6 text-center text-sm text-muted-foreground">
                ¿Ya tienes cuenta?{' '}
                <TextLink href={login()} className="font-semibold">
                    Inicia sesión
                </TextLink>
            </p>
        </>
    );
}
Register.layout = {
    title: 'Crea tu cuenta',
    description:
        'Regístrate con tu matrícula para ver rutas y recibir avisos de llegada.',
};
