import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BusFront, MapPin, Radio } from 'lucide-react';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="UniRoute" />

            <div className="min-h-screen bg-background text-foreground">
                <header className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-6">
                    <img
                        src="/branding/logo-horizontal-compacto-oscuro.svg"
                        alt="UniRoute"
                        className="h-10 max-w-[180px]"
                    />

                    {/* Navegación de la página de bienvenida */}
                    <nav
                        className="hidden items-center gap-6 text-sm text-muted-foreground md:flex"
                        aria-label="Secciones"
                    >
                        <a
                            href="#como-funciona"
                            className="transition-colors hover:text-foreground"
                        >
                            Cómo funciona
                        </a>
                    </nav>

                    <Link
                        href={auth.user ? dashboard() : login()}
                        className="rounded-lg border border-border px-4 py-2 text-sm text-primary transition-colors hover:bg-secondary"
                    >
                        {auth.user ? 'Ir al panel' : 'Iniciar sesión'}
                    </Link>
                </header>

                <main className="mx-auto grid max-w-6xl items-center gap-12 px-6 py-16 md:py-24 lg:grid-cols-2">
                    {/* Sección principal de bienvenida */}
                    <section className="space-y-6">
                        <p className="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-2 text-xs tracking-wide text-muted-foreground">
                            <span className="size-2 rounded-full bg-live" />{' '}
                            Movilidad universitaria
                        </p>

                        <h1 className="text-4xl leading-tight font-bold tracking-tight sm:text-5xl">
                            Tu campus.
                            <br />
                            <span className="text-primary">Tu ruta.</span>
                        </h1>

                        <p className="max-w-md text-lg leading-relaxed text-muted-foreground">
                            Consulta el transporte universitario y organiza tus
                            recorridos desde un solo lugar.
                        </p>

                        <div className="flex flex-wrap gap-3">
                            <Link
                                href={auth.user ? dashboard() : login()}
                                className="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-3 font-semibold text-primary-foreground transition-colors hover:bg-primary-hover"
                            >
                                {auth.user
                                    ? 'Abrir mi panel'
                                    : 'Entrar a UniRoute'}

                                <ArrowRight className="size-4" />
                            </Link>

                            {!auth.user && (
                                <Link
                                    href={register()}
                                    className="rounded-lg border border-border px-5 py-3 font-medium hover:bg-secondary"
                                >
                                    Crear cuenta
                                </Link>
                            )}
                        </div>
                    </section>

                    {/* Sección de servicios: destino de "Cómo funciona" */}
                    <section
                        id="como-funciona"
                        aria-label="Servicios de UniRoute"
                        className="scroll-mt-6 rounded-2xl border border-border bg-card p-6 sm:p-8"
                    >
                        <div className="mb-8 flex items-center gap-3">
                            <span className="rounded-xl bg-primary p-3 text-primary-foreground">
                                <BusFront className="size-7" />
                            </span>

                            <div>
                                <h2 className="text-xl font-semibold">
                                    UniRoute
                                </h2>

                                <p className="text-sm text-muted-foreground">
                                    Campus Mobility
                                </p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            {[
                                {
                                    icon: MapPin,
                                    title: 'Rutas y paradas',
                                    text: 'Información para planear tu próximo recorrido.',
                                },
                                {
                                    icon: BusFront,
                                    title: 'Transporte universitario',
                                    text: 'Unidades y operación al servicio de tu campus.',
                                },
                                {
                                    icon: Radio,
                                    title: 'Una plataforma conectada',
                                    text: 'Estudiantes, conductores y administración.',
                                },
                            ].map(({ icon: Icon, title, text }) => (
                                <div
                                    key={title}
                                    className="flex gap-4 rounded-xl border border-border bg-background p-4"
                                >
                                    <Icon className="mt-1 size-5 shrink-0 text-live" />

                                    <div>
                                        <h3 className="font-medium">{title}</h3>

                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {text}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}
