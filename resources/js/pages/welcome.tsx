import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BusFront,
    Clock3,
    MapPin,
    Radio,
    Route,
} from 'lucide-react';
import { dashboard, login, register } from '@/routes';
import AppLogo from '@/components/app-logo';

export default function Welcome() {
    const { auth } = usePage().props;
    const isAuthenticated = Boolean(auth.user);
    const accessUrl = isAuthenticated ? dashboard() : login();

    return (
        <>
            <Head title="Bienvenido a UniRoute" />

            <div className="dark min-h-screen bg-background text-foreground">
                <header className="border-b border-border/60">
                    <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-5 sm:px-6">
                        <Link href="/" aria-label="Inicio de UniRoute">
                            <AppLogo
                                alt="UniRoute"
                                className="h-11 max-w-[170px] object-contain"
                            />
                        </Link>

                        <nav
                            aria-label="Navegación de bienvenida"
                            className="hidden items-center gap-6 text-sm text-muted-foreground md:flex"
                        >
                            <a
                                href="#como-funciona"
                                className="transition-colors hover:text-primary"
                            >
                                Cómo funciona
                            </a>
                            <a
                                href="#rutas"
                                className="transition-colors hover:text-primary"
                            >
                                Rutas
                            </a>
                        </nav>

                        <div className="flex items-center gap-3">
                            <Link
                                href={accessUrl}
                                className="rounded-lg border border-border px-3 py-2 text-sm font-medium transition-colors hover:bg-secondary sm:px-4"
                            >
                                {isAuthenticated
                                    ? 'Ir al panel'
                                    : 'Iniciar sesión'}
                            </Link>

                            {!isAuthenticated && (
                                <Link
                                    href={register()}
                                    className="hidden rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary-hover sm:inline-flex"
                                >
                                    Crear cuenta
                                </Link>
                            )}
                        </div>
                    </div>

                    <nav
                        aria-label="Secciones de bienvenida en móvil"
                        className="flex justify-center gap-8 border-t border-border/50 px-4 py-3 text-xs text-muted-foreground md:hidden"
                    >
                        <a href="#como-funciona">Cómo funciona</a>
                        <a href="#rutas">Rutas</a>
                    </nav>
                </header>

                <main>
                    <div className="mx-auto grid max-w-6xl items-center gap-10 px-5 py-16 sm:px-6 md:py-24 lg:grid-cols-2">
                        <section className="space-y-7">
                            <p className="inline-flex items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-xs font-medium text-live">
                                <span className="size-2 rounded-full bg-live" />
                                Movilidad universitaria
                            </p>

                            <h1 className="text-4xl leading-tight font-extrabold tracking-tight sm:text-5xl">
                                Tu transporte universitario,
                                <span className="text-primary">
                                    {' '}
                                    en tiempo real.
                                </span>
                            </h1>

                            <p className="max-w-lg text-base leading-relaxed text-muted-foreground sm:text-lg">
                                Consulta las rutas universitarias, encuentra las
                                paradas de tu recorrido y recibe avisos sobre el
                                servicio de transporte de tu campus.
                            </p>

                            <div className="flex flex-wrap gap-3">
                                <Link
                                    href={
                                        isAuthenticated
                                            ? dashboard()
                                            : register()
                                    }
                                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary px-5 py-3 font-semibold text-primary-foreground transition-colors hover:bg-primary-hover"
                                >
                                    {isAuthenticated
                                        ? 'Abrir mi panel'
                                        : 'Crear mi cuenta'}
                                    <ArrowRight className="size-4" />
                                </Link>

                                {!isAuthenticated && (
                                    <Link
                                        href={login()}
                                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-border bg-card px-5 py-3 font-medium transition-colors hover:bg-secondary"
                                    >
                                        Ya tengo cuenta
                                    </Link>
                                )}
                            </div>

                            <div className="grid max-w-md grid-cols-2 gap-4 border-t border-border pt-6">
                                <div className="flex items-start gap-3">
                                    <Route className="mt-1 size-5 shrink-0 text-live" />
                                    <div>
                                        <p className="text-sm font-semibold">
                                            Consulta rutas
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Encuentra tu recorrido
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <Clock3 className="mt-1 size-5 shrink-0 text-live" />
                                    <div>
                                        <p className="text-sm font-semibold">
                                            Revisa horarios
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Organiza tus traslados
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            aria-label="Ilustración de una ruta universitaria"
                            className="relative overflow-hidden rounded-2xl border border-border bg-card"
                        >
                            <div className="absolute inset-x-0 top-0 z-10 flex items-center justify-between p-4">
                                <span className="rounded-full border border-border bg-background/90 px-3 py-1.5 text-xs font-medium">
                                    Red universitaria
                                </span>
                                <span className="rounded-full border border-border bg-background/90 px-3 py-1.5 text-[11px] text-muted-foreground">
                                    Vista ilustrativa
                                </span>
                            </div>

                            <svg
                                viewBox="0 0 600 440"
                                role="img"
                                aria-label="Esquema ilustrativo de una ruta de transporte universitario con seis paradas"
                                className="h-auto w-full"
                            >
                                <defs>
                                    <pattern
                                        id="welcome-grid"
                                        width="42"
                                        height="42"
                                        patternUnits="userSpaceOnUse"
                                    >
                                        <path
                                            d="M 42 0 L 0 0 0 42"
                                            fill="none"
                                            stroke="#31425c"
                                            strokeWidth="1"
                                            opacity="0.45"
                                        />
                                    </pattern>
                                </defs>

                                <rect width="600" height="440" fill="#08162c" />
                                <rect
                                    width="600"
                                    height="440"
                                    fill="url(#welcome-grid)"
                                />

                                <g
                                    fill="none"
                                    stroke="#263c5b"
                                    strokeWidth="13"
                                    opacity="0.75"
                                >
                                    <path d="M -30 120 L 630 50" />
                                    <path d="M -20 330 L 620 215" />
                                    <path d="M 110 -20 L 170 460" />
                                    <path d="M 435 -20 L 385 460" />
                                </g>

                                <g
                                    fill="#9aabc2"
                                    fontSize="12"
                                    letterSpacing="2"
                                    opacity="0.8"
                                >
                                    <text x="55" y="105">
                                        CAMPUS
                                    </text>
                                    <text x="365" y="115">
                                        ZONA UNIVERSITARIA
                                    </text>
                                    <text x="355" y="375">
                                        PARADAS
                                    </text>
                                </g>

                                <path
                                    d="M 78 348 L 145 275 L 215 205 L 320 178 L 410 225 L 485 310 L 540 185"
                                    fill="none"
                                    stroke="#84cc16"
                                    strokeWidth="15"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    opacity="0.2"
                                />

                                <path
                                    d="M 78 348 L 145 275 L 215 205 L 320 178 L 410 225 L 485 310 L 540 185"
                                    fill="none"
                                    stroke="#91db2a"
                                    strokeWidth="6"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />

                                {[
                                    [78, 348, '1'],
                                    [145, 275, '2'],
                                    [215, 205, '3'],
                                    [320, 178, '4'],
                                    [410, 225, '5'],
                                    [540, 185, '6'],
                                ].map(([cx, cy, number]) => (
                                    <g key={number}>
                                        <circle
                                            cx={cx}
                                            cy={cy}
                                            r="16"
                                            fill="#17243a"
                                            stroke="#e0c297"
                                            strokeWidth="3"
                                        />
                                        <text
                                            x={cx}
                                            y={Number(cy) + 5}
                                            textAnchor="middle"
                                            fill="#ffffff"
                                            fontSize="14"
                                            fontWeight="bold"
                                        >
                                            {number}
                                        </text>
                                    </g>
                                ))}

                                <g transform="translate(305 164)">
                                    <rect
                                        x="-24"
                                        y="-24"
                                        width="48"
                                        height="48"
                                        rx="13"
                                        fill="#fddeb1"
                                        stroke="#020e24"
                                        strokeWidth="3"
                                    />
                                    <rect
                                        x="-13"
                                        y="-12"
                                        width="26"
                                        height="20"
                                        rx="4"
                                        fill="#17243a"
                                    />
                                    <rect
                                        x="-9"
                                        y="-8"
                                        width="18"
                                        height="8"
                                        rx="2"
                                        fill="#fddeb1"
                                    />
                                    <circle
                                        cx="-8"
                                        cy="13"
                                        r="3"
                                        fill="#17243a"
                                    />
                                    <circle
                                        cx="8"
                                        cy="13"
                                        r="3"
                                        fill="#17243a"
                                    />
                                </g>
                            </svg>

                            <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-card px-5 py-4">
                                <div>
                                    <p className="text-sm font-semibold">
                                        Explora la red de transporte
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Consulta recorridos y paradas reales
                                        desde tu cuenta.
                                    </p>
                                </div>

                                <Link
                                    href={accessUrl}
                                    className="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"
                                >
                                    Ver rutas
                                    <ArrowRight className="size-4" />
                                </Link>
                            </div>
                        </section>
                    </div>

                    <section
                        id="rutas"
                        className="scroll-mt-8 border-y border-border bg-card/50"
                    >
                        <div className="mx-auto flex max-w-6xl flex-col gap-4 px-5 py-12 sm:px-6 md:flex-row md:items-center md:justify-between">
                            <div className="space-y-2">
                                <h2 className="text-2xl font-bold">
                                    Rutas de tu campus
                                </h2>
                                <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                                    Accede al panel para consultar las rutas,
                                    paradas y horarios disponibles. La
                                    información del servicio se obtiene desde la
                                    plataforma de UniRoute.
                                </p>
                            </div>
                            <Link
                                href={accessUrl}
                                className="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-lg bg-primary px-5 py-3 font-semibold text-primary-foreground hover:bg-primary-hover"
                            >
                                Consultar rutas
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </section>

                    <section
                        id="como-funciona"
                        aria-label="Cómo funciona UniRoute"
                        className="mx-auto max-w-6xl scroll-mt-8 px-5 py-16 sm:px-6 md:py-20"
                    >
                        <div className="mb-8 space-y-2">
                            <h2 className="text-2xl font-bold sm:text-3xl">
                                ¿Cómo funciona UniRoute?
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Todo lo que necesitas para organizar tus
                                recorridos universitarios.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-3">
                            {[
                                {
                                    icon: MapPin,
                                    title: 'Rutas y paradas',
                                    text: 'Encuentra los recorridos disponibles y sus paradas.',
                                },
                                {
                                    icon: BusFront,
                                    title: 'Transporte universitario',
                                    text: 'Consulta la información del servicio de tu campus.',
                                },
                                {
                                    icon: Radio,
                                    title: 'Avisos de transporte',
                                    text: 'Revisa notificaciones de llegadas, retrasos y servicio.',
                                },
                            ].map(({ icon: Icon, title, text }) => (
                                <article
                                    key={title}
                                    className="rounded-xl border border-border bg-card p-6"
                                >
                                    <span className="mb-4 inline-flex rounded-lg bg-secondary p-3">
                                        <Icon className="size-6 text-live" />
                                    </span>
                                    <h3 className="font-semibold">{title}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {text}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border px-5 py-6 text-center text-xs text-muted-foreground">
                    UniRoute · Movilidad universitaria
                </footer>
            </div>
        </>
    );
}
