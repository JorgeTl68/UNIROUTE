import { Form, Head } from '@inertiajs/react';
import { LockKeyhole, LogIn, Mail } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import PasskeyVerify from '@/components/passkey-verify';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = { status?: string; canResetPassword: boolean };

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Iniciar sesión" />
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo electrónico</Label>
                            <div className="relative">
                                <Mail
                                    aria-hidden="true"
                                    className="auth-field-icon"
                                />
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    placeholder="tu.correo@ejemplo.com"
                                    className="pl-11"
                                />
                            </div>
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="text-xs font-semibold"
                                    >
                                        ¿Olvidaste tu contraseña?
                                    </TextLink>
                                )}
                            </div>
                            <div className="relative">
                                <LockKeyhole
                                    aria-hidden="true"
                                    className="auth-field-icon"
                                />
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder="Tu contraseña"
                                    className="pl-11"
                                />
                            </div>
                            <InputError message={errors.password} />
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox id="remember" name="remember" />
                            <Label htmlFor="remember" className="font-normal">
                                Recordarme
                            </Label>
                        </div>
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing ? <Spinner /> : <LogIn />}Iniciar sesión
                        </Button>
                    </>
                )}
            </Form>
            <PasskeyVerify
                label="Entrar con passkey"
                loadingLabel="Verificando…"
                separator="o"
                separatorFirst
            />
            <div className="mt-8 space-y-2 text-center text-sm text-muted-foreground">
                <p>
                    ¿No tienes cuenta?{' '}
                    <TextLink href={register()} className="font-semibold">
                        Regístrate
                    </TextLink>
                </p>
                <p className="text-xs leading-relaxed text-placeholder">
                    ¿Eres conductor o administrador? Tu cuenta la crea el área
                    de transporte.
                </p>
            </div>
            {status && (
                <p role="status" className="mt-4 text-center text-sm text-live">
                    {status}
                </p>
            )}
        </>
    );
}
Login.layout = {
    title: 'Inicia sesión',
    description: 'Entra con tu correo y contraseña para ver tus rutas.',
};
