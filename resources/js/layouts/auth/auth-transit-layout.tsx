import { Link } from '@inertiajs/react';
import { BellRing, Clock3, MapPin } from 'lucide-react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthTransitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="auth-transit min-h-svh bg-background text-foreground lg:grid lg:grid-cols-[55%_45%]">
            <aside className="auth-transit-panel relative isolate flex min-h-[340px] flex-col overflow-hidden border-b border-border bg-card px-6 py-8 sm:px-10 lg:min-h-svh lg:border-r lg:border-b-0">
                <div
                    aria-hidden="true"
                    className="auth-map-grid absolute inset-0 -z-20"
                />
                <svg
                    aria-hidden="true"
                    viewBox="0 0 900 900"
                    preserveAspectRatio="xMidYMid slice"
                    className="pointer-events-none absolute inset-0 -z-10 size-full opacity-75"
                >
                    <defs>
                        <linearGradient
                            id="route-fade"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop offset="0%" stopColor="#A3E635" />
                            <stop offset="60%" stopColor="#91DB2A" />
                            <stop
                                offset="100%"
                                stopColor="#91DB2A"
                                stopOpacity="0"
                            />
                        </linearGradient>
                    </defs>
                    <path
                        d="M200 -40 L390 920 M-50 265 L970 85"
                        fill="none"
                        stroke="#BAC7E4"
                        strokeOpacity=".08"
                        strokeWidth="22"
                    />
                    <path
                        d="M490 -80 L530 35 L380 280 L540 390 L655 600 L610 690"
                        fill="none"
                        stroke="#91DB2A"
                        strokeOpacity=".2"
                        strokeWidth="22"
                    />
                    <path
                        d="M490 -80 L530 35 L380 280 L540 390 L655 600 L610 690"
                        fill="none"
                        stroke="url(#route-fade)"
                        strokeWidth="10"
                    />
                    <circle
                        cx="380"
                        cy="280"
                        r="12"
                        fill="#17243A"
                        stroke="#ECF1F9"
                        strokeWidth="5"
                    />
                    <circle
                        cx="530"
                        cy="35"
                        r="12"
                        fill="#17243A"
                        stroke="#ECF1F9"
                        strokeWidth="5"
                    />
                    <g transform="translate(455 337) rotate(35)">
                        <circle r="42" fill="#91DB2A" fillOpacity=".12" />
                        <rect
                            x="-40"
                            y="-23"
                            width="80"
                            height="46"
                            rx="10"
                            fill="#FDDEB1"
                            stroke="#020E24"
                            strokeWidth="5"
                        />
                        <rect
                            x="-30"
                            y="-14"
                            width="13"
                            height="28"
                            rx="3"
                            fill="#17243A"
                        />
                        <text
                            x="9"
                            y="6"
                            textAnchor="middle"
                            fill="#17243A"
                            fontSize="17"
                            fontWeight="800"
                        >
                            UTC
                        </text>
                    </g>
                </svg>
                <Link href={home()} className="relative w-fit">
                    <img
                        src="/branding/logo-horizontal-compacto-oscuro.svg"
                        alt="UniRoute · Campus Mobility"
                        className="h-12 max-w-[210px]"
                    />
                </Link>
                <div className="my-auto max-w-xl py-12 lg:py-20">
                    <p className="mb-7 inline-flex items-center gap-2 rounded-full bg-secondary px-3 py-1.5 text-xs font-semibold text-live">
                        <span className="size-2 rounded-full bg-live" />
                        Servicio en tiempo real
                    </p>
                    <h2 className="text-3xl leading-tight font-bold tracking-tight sm:text-4xl xl:text-[44px]">
                        Tu transporte universitario,
                        <br className="hidden xl:block" /> en tiempo real.
                    </h2>
                    <p className="mt-7 max-w-lg text-base leading-relaxed text-muted-foreground xl:text-lg">
                        Consulta rutas, paradas y la llegada de tu unidad desde
                        cualquier lugar del campus.
                    </p>
                    <ul className="mt-7 hidden space-y-3 text-sm sm:block">
                        {[
                            {
                                Icon: MapPin,
                                text: 'Ubica tu unidad en el mapa en vivo',
                                color: 'text-live',
                            },
                            {
                                Icon: BellRing,
                                text: 'Recibe un aviso antes de que llegue a tu parada',
                                color: 'text-primary',
                            },
                            {
                                Icon: Clock3,
                                text: 'Consulta horarios por ruta y campus',
                                color: 'text-muted-foreground',
                            },
                        ].map(({ Icon, text, color }) => (
                            <li key={text} className="flex items-center gap-3">
                                <span
                                    className={`rounded-lg bg-secondary p-2 ${color}`}
                                >
                                    <Icon className="size-5" />
                                </span>
                                {text}
                            </li>
                        ))}
                    </ul>
                </div>
                <footer className="hidden items-center justify-between gap-4 text-xs font-semibold text-muted-foreground lg:flex">
                    <span>
                        Universidad Tecnológica de Coahuila · Saltillo – Ramos
                        Arizpe
                    </span>
                    <span className="shrink-0">
                        © {new Date().getFullYear()} UniRoute
                    </span>
                </footer>
            </aside>
            <div className="flex min-w-0 flex-col">
                <main className="flex flex-1 items-center justify-center px-6 py-12 sm:px-10 lg:px-12">
                    <div className="w-full max-w-[420px]">
                        <header className="mb-9">
                            <h1 className="text-2xl font-bold tracking-tight">
                                {title}
                            </h1>
                            <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                                {description}
                            </p>
                        </header>
                        {children}
                    </div>
                </main>
                <footer className="flex items-center gap-2 border-t border-border/60 px-8 py-5 text-xs font-medium tracking-wide text-placeholder">
                    <span className="size-1.5 rounded-full bg-live" />
                    UniRoute · Campus Mobility
                </footer>
            </div>
        </div>
    );
}
