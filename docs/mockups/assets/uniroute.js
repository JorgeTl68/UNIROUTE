/*
  Base compartida de los mockups de UniRoute (tema oscuro).

  - Configura Tailwind con los mismos nombres de resources/css/app.css, así que
    las clases de los mockups (bg-card, bg-primary, text-live…) se copian tal
    cual a la app.
  - Dibuja el layout (sidebar, header y barra inferior en celular) alrededor del
    <main> de cada mockup. En la app es AppSidebarLayout (S1-05, Andrés).
  - Dibuja el mapa ilustrado de los mockups. En la app el mapa es Leaflet con
    OpenStreetMap (docs/mapas.md); aquí es un SVG para que se vea sin internet.

  Solo sirve para ver los mockups: no se copia al proyecto.
*/

tailwind.config = {
    theme: {
        extend: {
            colors: {
                background: '#020e24',
                foreground: '#ecf1f9',
                card: { DEFAULT: '#17243a', foreground: '#ecf1f9' },
                popover: { DEFAULT: '#17243a', foreground: '#ecf1f9' },
                primary: {
                    DEFAULT: '#fddeb1',
                    foreground: '#020e24',
                    hover: '#e0c297',
                },
                secondary: { DEFAULT: '#2f3e57', foreground: '#ecf1f9' },
                muted: { DEFAULT: '#17243a', foreground: '#bac7e4' },
                accent: { DEFAULT: '#2f3e57', foreground: '#fddeb1' },
                destructive: { DEFAULT: '#ffb4ab', foreground: '#020e24' },
                border: '#2f3e57',
                input: '#2f3e57',
                ring: '#b69b72',
                live: '#91db2a',
                route: '#a3e635',
                placeholder: '#7e8ba6',
                // Propuestos en docs/DESIGN.md; todavía no están en app.css.
                warning: '#fbbf24',
                gold: '#b69b72',
            },
            borderRadius: {
                sm: '8px',
                md: '10px',
                lg: '12px',
                xl: '12px',
                '2xl': '16px',
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
};

(function () {
    var css = document.createElement('style');
    css.textContent = [
        'body{-webkit-font-smoothing:antialiased;font-variant-numeric:tabular-nums}',
        'h1,h2,h3,h4{color:#fff}',
        'input::placeholder,textarea::placeholder{color:#7e8ba6}',
        'select{color-scheme:dark}',
        '.ur-scroll::-webkit-scrollbar{width:8px;height:8px}',
        '.ur-scroll::-webkit-scrollbar-thumb{background:#2f3e57;border-radius:9999px}',
        '@keyframes ur-ping{0%{transform:scale(.6);opacity:.7}100%{transform:scale(1.6);opacity:0}}',
        '.ur-ping{transform-box:fill-box;transform-origin:center;animation:ur-ping 1.8s ease-out infinite}',
        '@media (prefers-reduced-motion:reduce){.ur-ping,.animate-ping,.animate-pulse{animation:none!important}}',
    ].join('\n');
    document.head.appendChild(css);
})();

var UR = (window.UR = {});

UR.icons = function () {
    if (window.lucide) {
        window.lucide.createIcons();
    }
};

UR.nav = [
    {
        id: 'dashboard',
        label: 'Dashboard en Vivo',
        icon: 'layout-grid',
        href: 'dashboard.html',
        corto: 'Mapa',
    },
    {
        id: 'rutas',
        label: 'Detalle de Rutas y Paradas',
        icon: 'route',
        href: 'detalle-ruta.html',
        corto: 'Rutas',
    },
    {
        id: 'horarios',
        label: 'Horarios e Itinerarios',
        icon: 'clock-3',
        href: 'horarios.html',
        corto: 'Horarios',
    },
    {
        id: 'notificaciones',
        label: 'Mis Notificaciones y Alertas',
        icon: 'bell-ring',
        href: 'notificaciones.html',
        corto: 'Alertas',
        badge: '3',
    },
    {
        id: 'admin',
        label: 'Panel de Administración',
        icon: 'layout-dashboard',
        href: 'admin-flota.html',
        soloAdmin: true,
    },
    {
        id: 'perfil',
        label: 'Perfil y Ajustes',
        icon: 'settings',
        href: 'perfil.html',
        corto: 'Perfil',
    },
];

UR.usuarios = {
    estudiante: {
        nombre: 'Valeria Cárdenas',
        rol: 'Estudiante',
        iniciales: 'VC',
    },
    admin: { nombre: 'Mariana Ortiz', rol: 'Administración', iniciales: 'MO' },
};

UR.tabsAdmin = [
    {
        id: 'flota',
        label: 'Unidades',
        icon: 'bus-front',
        href: 'admin-flota.html',
    },
    {
        id: 'conductores',
        label: 'Conductores',
        icon: 'id-card',
        href: 'admin-conductores.html',
    },
    {
        id: 'mantenimiento',
        label: 'Mantenimiento',
        icon: 'wrench',
        href: 'admin-mantenimiento.html',
        punto: true,
    },
    {
        id: 'rutas',
        label: 'Rutas y paradas',
        icon: 'route',
        href: 'admin-rutas.html',
    },
    {
        id: 'horarios',
        label: 'Horarios de salida',
        icon: 'calendar-clock',
        href: 'admin-horarios.html',
    },
    {
        id: 'incidencias',
        label: 'Incidencias',
        icon: 'triangle-alert',
        href: 'admin-incidencias.html',
        punto: true,
    },
    {
        id: 'aforo',
        label: 'Reportes de aforo',
        icon: 'chart-column',
        href: 'admin-aforo.html',
    },
];

function navItem(item, activo) {
    var base =
        'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors';
    var clase = activo
        ? base + ' bg-primary font-semibold text-primary-foreground'
        : base +
          ' text-muted-foreground hover:bg-secondary hover:text-foreground';
    var badge = item.badge
        ? '<span class="ml-auto rounded-full ' +
          (activo
              ? 'bg-primary-foreground/15 text-primary-foreground'
              : 'bg-secondary text-foreground') +
          ' px-2 py-0.5 text-[11px] font-bold">' +
          item.badge +
          '</span>'
        : '';
    return (
        '<a href="' +
        item.href +
        '" class="' +
        clase +
        '"' +
        (activo ? ' aria-current="page"' : '') +
        '>' +
        '<i data-lucide="' +
        item.icon +
        '" class="size-[18px] shrink-0"></i>' +
        '<span>' +
        item.label +
        '</span>' +
        badge +
        '</a>'
    );
}

function sidebar(pagina, rol) {
    var items = UR.nav
        .filter(function (i) {
            return !i.soloAdmin || rol === 'admin';
        })
        .map(function (i) {
            return navItem(i, i.id === pagina);
        })
        .join('');

    return (
        '<div data-ur-fondo class="fixed inset-0 z-40 hidden bg-background/70 backdrop-blur-sm lg:hidden"></div>' +
        '<aside id="ur-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-border bg-card transition-transform lg:translate-x-0" aria-label="Menú principal">' +
        '<div class="flex h-16 items-center justify-between gap-3 px-5">' +
        '<a href="dashboard.html" class="flex items-center gap-2.5">' +
        '<img src="../../public/branding/isotipo-oscuro.svg" alt="" class="size-9">' +
        '<span class="flex flex-col"><span class="text-lg font-bold leading-none tracking-tight text-white">UniRoute</span>' +
        '<span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-gold">Campus Mobility</span></span></a>' +
        '<button type="button" data-ur-cerrar class="flex size-9 items-center justify-center rounded-md bg-secondary text-foreground lg:hidden" aria-label="Cerrar menú"><i data-lucide="x" class="size-5"></i></button>' +
        '</div>' +
        '<div class="mx-4 mb-3 flex items-center justify-between rounded-md bg-background/60 px-3 py-2 text-xs">' +
        '<span class="flex items-center gap-2 text-muted-foreground"><span class="size-1.5 rounded-full bg-live"></span>Red operativa</span>' +
        '<span class="font-semibold text-foreground">18 activas</span></div>' +
        '<nav class="ur-scroll flex-1 space-y-1 overflow-y-auto px-3">' +
        items +
        '</nav>' +
        '<div class="space-y-3 p-4">' +
        '<div class="rounded-xl border border-border bg-background/50 p-4">' +
        '<p class="flex items-center gap-2 text-sm font-semibold text-foreground"><i data-lucide="siren" class="size-4 text-destructive"></i>Soporte en Ruta</p>' +
        '<p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">Incidencias en tiempo real y ayuda directa del área de transporte.</p>' +
        '<a href="emergencia.html" class="mt-3 flex w-full items-center justify-center gap-2 rounded-md border border-destructive/40 bg-destructive/10 py-2 text-xs font-semibold text-destructive hover:bg-destructive/20"><i data-lucide="siren" class="size-4"></i>Reportar emergencia</a>' +
        '</div>' +
        '<div class="flex items-center justify-between px-1 text-[11px] text-placeholder"><span>UniRoute v1.0</span><span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-live"></span>Servidor OK</span></div>' +
        '</div></aside>'
    );
}

function header(rol) {
    var u = UR.usuarios[rol] || UR.usuarios.estudiante;
    return (
        '<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-border bg-background/85 px-4 backdrop-blur sm:px-6 lg:px-8">' +
        '<button type="button" data-ur-abrir class="flex size-9 shrink-0 items-center justify-center rounded-md bg-card text-foreground lg:hidden" aria-label="Abrir menú"><i data-lucide="menu" class="size-5"></i></button>' +
        '<button type="button" class="hidden shrink-0 items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium text-foreground hover:bg-secondary md:flex">' +
        '<i data-lucide="building-2" class="size-4 text-gold"></i>Campus Central Saltillo<i data-lucide="chevron-down" class="size-4 text-muted-foreground"></i></button>' +
        '<label class="relative min-w-0 flex-1 md:max-w-sm">' +
        '<span class="sr-only">Buscar</span>' +
        '<i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-placeholder"></i>' +
        '<input type="search" placeholder="Buscar ruta, parada o unidad…" class="w-full rounded-md border border-border bg-card py-2 pl-9 pr-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50 md:pr-16">' +
        '<kbd class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded bg-secondary px-1.5 py-0.5 text-[11px] font-semibold text-muted-foreground md:block">Ctrl K</kbd>' +
        '</label>' +
        '<div class="hidden items-center gap-2 rounded-full bg-card px-3 py-1.5 text-xs xl:flex">' +
        '<span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-live opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-live"></span></span>' +
        '<span class="font-semibold text-foreground">En servicio:</span><span class="text-muted-foreground">18 unidades en circulación</span></div>' +
        '<div class="ml-auto flex shrink-0 items-center gap-3">' +
        '<div class="relative">' +
        '<button type="button" data-ur-campana class="relative flex size-9 items-center justify-center rounded-md bg-card text-foreground hover:bg-secondary" aria-label="Notificaciones" aria-expanded="false">' +
        '<i data-lucide="bell" class="size-[18px]"></i><span class="absolute right-2 top-2 size-2 rounded-full bg-primary ring-2 ring-card"></span></button>' +
        centroNotificaciones() +
        '</div>' +
        '<div class="hidden h-6 w-px bg-border sm:block"></div>' +
        '<a href="perfil.html" class="flex items-center gap-3">' +
        '<span class="hidden text-right sm:block"><span class="block text-sm font-semibold leading-tight text-foreground">' +
        u.nombre +
        '</span>' +
        '<span class="block text-xs leading-tight text-gold">' +
        u.rol +
        '</span></span>' +
        '<span class="flex size-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">' +
        u.iniciales +
        '</span></a>' +
        '</div></header>'
    );
}

function centroNotificaciones() {
    var items = [
        [
            'bus-front',
            'text-live bg-live/15',
            'Tu unidad llega en 4 min',
            'Ruta 1 · Parada Santa Fe',
            'Hace 1 min',
        ],
        [
            'clock-alert',
            'text-warning bg-warning/15',
            'Retraso de 9 min en Ruta 2',
            'Tráfico denso en Blvd. Plan de Guadalupe',
            'Hace 12 min',
        ],
        [
            'megaphone',
            'text-primary bg-primary/15',
            'Horario especial el viernes',
            'Último viaje a las 18:00',
            'Ayer',
        ],
    ]
        .map(function (n) {
            return (
                '<li class="flex gap-3 px-4 py-3 hover:bg-secondary/50">' +
                '<span class="flex size-9 shrink-0 items-center justify-center rounded-md ' +
                n[1] +
                '"><i data-lucide="' +
                n[0] +
                '" class="size-4"></i></span>' +
                '<span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-foreground">' +
                n[2] +
                '</span>' +
                '<span class="block truncate text-xs text-muted-foreground">' +
                n[3] +
                '</span>' +
                '<span class="mt-0.5 block text-[11px] text-placeholder">' +
                n[4] +
                '</span></span></li>'
            );
        })
        .join('');
    return (
        '<div data-ur-panel-notificaciones class="absolute right-0 top-11 z-50 hidden w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-xl border border-border bg-popover shadow-2xl shadow-black/50">' +
        '<div class="flex items-center justify-between border-b border-border px-4 py-3">' +
        '<p class="text-sm font-semibold text-foreground">Notificaciones</p>' +
        '<button type="button" class="text-xs font-semibold text-primary hover:underline">Marcar todas como leídas</button></div>' +
        '<ul class="divide-y divide-border">' +
        items +
        '</ul>' +
        '<a href="notificaciones.html" class="block border-t border-border px-4 py-3 text-center text-sm font-semibold text-primary hover:bg-secondary/50">Ver todas</a></div>'
    );
}

function barraInferior(pagina) {
    var items = UR.nav
        .filter(function (i) {
            return i.corto;
        })
        .map(function (i) {
            var activo = i.id === pagina;
            return (
                '<a href="' +
                i.href +
                '" class="relative flex flex-1 flex-col items-center justify-center gap-1 text-[11px] font-semibold ' +
                (activo ? 'text-primary' : 'text-muted-foreground') +
                '"' +
                (activo ? ' aria-current="page"' : '') +
                '>' +
                (activo
                    ? '<span class="absolute inset-x-5 top-0 h-0.5 rounded-full bg-primary"></span>'
                    : '') +
                '<i data-lucide="' +
                i.icon +
                '" class="size-5"></i>' +
                i.corto +
                (i.badge
                    ? '<span class="absolute right-[22%] top-2 size-2 rounded-full bg-primary"></span>'
                    : '') +
                '</a>'
            );
        })
        .join('');
    return (
        '<nav class="fixed inset-x-0 bottom-0 z-30 flex h-16 border-t border-border bg-card/95 backdrop-blur md:hidden" aria-label="Navegación inferior">' +
        items +
        '</nav>'
    );
}

UR.htmlTabsAdmin = function (activa) {
    return (
        '<div class="ur-scroll -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0"><div class="flex w-max gap-1 rounded-xl border border-border bg-card p-1">' +
        UR.tabsAdmin
            .map(function (t) {
                var on = t.id === activa;
                return (
                    '<a href="' +
                    t.href +
                    '" class="flex items-center gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm ' +
                    (on
                        ? 'bg-primary font-semibold text-primary-foreground'
                        : 'text-muted-foreground hover:bg-secondary hover:text-foreground') +
                    '"' +
                    (on ? ' aria-current="page"' : '') +
                    '>' +
                    '<i data-lucide="' +
                    t.icon +
                    '" class="size-4"></i>' +
                    t.label +
                    (t.punto && !on
                        ? '<span class="size-1.5 rounded-full bg-destructive"></span>'
                        : '') +
                    '</a>'
                );
            })
            .join('') +
        '</div></div>'
    );
};

/*
  Mapa ilustrado. Coordenadas en un lienzo de 1000 × 700.
  Ruta 1: Campus Central – Santa Fe, con las paradas del seeder.
*/
UR.ruta1 = {
    puntos: [
        [150, 585],
        [205, 520],
        [255, 455],
        [300, 405],
        [350, 360],
        [405, 322],
        [470, 300],
        [530, 305],
        [585, 340],
        [630, 390],
        [665, 430],
        [715, 465],
        [770, 460],
        [815, 425],
        [850, 375],
        [875, 315],
        [895, 245],
    ],
    paradas: [
        { i: 0, nombre: 'Campus Central - Puerta Principal' },
        { i: 3, nombre: 'Blanca Esthela' },
        { i: 7, nombre: 'Manantiales del Valle' },
        { i: 10, nombre: 'Fraccionamiento Arborea' },
        { i: 13, nombre: 'Santa Fe' },
        { i: 16, nombre: 'El Cortijo de Abajo' },
    ],
};

UR.otrasRutas = [
    {
        color: '#e0c297',
        puntos: [
            [960, 90],
            [840, 130],
            [700, 150],
            [560, 185],
            [430, 215],
            [320, 260],
            [230, 330],
            [180, 430],
            [150, 585],
        ],
    },
    {
        color: '#7e8ba6',
        puntos: [
            [665, 430],
            [600, 470],
            [480, 520],
            [330, 560],
            [150, 585],
        ],
    },
    {
        color: '#c9a36b',
        puntos: [
            [60, 230],
            [120, 330],
            [150, 440],
            [150, 585],
        ],
    },
];

function linea(pts) {
    return pts
        .map(function (p, k) {
            return (k ? 'L' : 'M') + p[0] + ' ' + p[1];
        })
        .join(' ');
}

/*
  opciones:
    recorrido: índice del punto donde va la unidad (0–16)
    otras: true para dibujar las demás rutas tenues
    unidades: lista de [x, y, etiqueta] extra
    usuario: [x, y] para la ubicación del usuario
    editar: true para el modo "Crear ruta" (paradas numeradas y línea punteada)
    etiquetas: false para ocultar los nombres de las colonias
*/
UR.mapa = function (contenedor, opciones) {
    var o = opciones || {};
    var r = UR.ruta1;
    var actual = o.recorrido == null ? 9 : o.recorrido;
    var siguiente = null;
    r.paradas.forEach(function (p) {
        if (siguiente === null && p.i > actual) {
            siguiente = p.i;
        }
    });

    var s = [];
    s.push(
        '<svg viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid meet" class="absolute inset-0 size-full" role="img" aria-label="Mapa de la ruta">',
    );
    s.push(
        '<defs><pattern id="ur-calles" width="56" height="56" patternUnits="userSpaceOnUse" patternTransform="rotate(-14)">' +
            '<path d="M0 0H56M0 0V56" stroke="#15253f" stroke-width="3"/></pattern></defs>',
    );
    s.push(
        '<rect x="-1500" y="-1500" width="4000" height="4000" fill="#0a1830"/>',
    );
    s.push(
        '<rect x="-1500" y="-1500" width="4000" height="4000" fill="url(#ur-calles)"/>',
    );
    s.push(
        '<path d="M520 -1000 L560 0 C 600 120 540 210 600 330 S 720 560 680 700 L640 1700" fill="none" stroke="#0d2a3a" stroke-width="26"/>',
    );
    s.push(
        '<rect x="40" y="610" width="230" height="70" rx="10" fill="#10262a"/><rect x="700" y="40" width="190" height="110" rx="10" fill="#10262a"/>',
    );
    [
        'M-1000 740 L2000 80',
        'M-1000 250 L2000 40',
        'M-266 -1000 L776 1700',
        'M446 -1000 L1294 1700',
        'M-1000 700 L2000 550',
        'M473 -1000 L87 1700',
    ].forEach(function (d, k) {
        s.push(
            '<path d="' +
                d +
                '" stroke="' +
                (k < 2 ? '#22355a' : '#1b2c4a') +
                '" stroke-width="' +
                (k < 2 ? 14 : 9) +
                '" fill="none"/>',
        );
    });

    if (o.etiquetas !== false) {
        [
            [340, 380, 'BLANCA ESTHELA'],
            [520, 268, 'MANANTIALES DEL VALLE'],
            [640, 500, 'FRACC. ARBOREA'],
            [835, 490, 'SANTA FE'],
            [905, 205, 'EL CORTIJO'],
            [155, 640, 'UTC · CAMPUS CENTRAL'],
            [300, 140, 'ZONA CENTRO'],
        ].forEach(function (t) {
            s.push(
                '<text x="' +
                    t[0] +
                    '" y="' +
                    t[1] +
                    '" fill="#4f5f7c" font-family="Inter,sans-serif" font-size="13" font-weight="700" letter-spacing="2" text-anchor="middle">' +
                    t[2] +
                    '</text>',
            );
        });
    }

    if (o.otras) {
        UR.otrasRutas.forEach(function (ruta) {
            s.push(
                '<path d="' +
                    linea(ruta.puntos) +
                    '" fill="none" stroke="' +
                    ruta.color +
                    '" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" opacity=".45"/>',
            );
        });
    }

    var d = linea(r.puntos);
    if (o.editar) {
        s.push(
            '<path d="' +
                d +
                '" fill="none" stroke="#a3e635" stroke-width="5" stroke-dasharray="2 12" stroke-linecap="round" stroke-linejoin="round"/>',
        );
    } else {
        s.push(
            '<path d="' +
                d +
                '" fill="none" stroke="#3f6e00" stroke-width="13" stroke-linecap="round" stroke-linejoin="round" opacity=".55"/>',
        );
        s.push(
            '<path d="' +
                d +
                '" fill="none" stroke="#a3e635" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>',
        );
        s.push(
            '<path d="' +
                linea(r.puntos.slice(0, actual + 1)) +
                '" fill="none" stroke="#020e24" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" opacity=".55"/>',
        );
    }

    r.paradas.forEach(function (p, n) {
        var x = r.puntos[p.i][0];
        var y = r.puntos[p.i][1];
        if (o.editar) {
            s.push(
                '<circle cx="' +
                    x +
                    '" cy="' +
                    y +
                    '" r="15" fill="#fddeb1" stroke="#020e24" stroke-width="3"/>',
            );
            s.push(
                '<text x="' +
                    x +
                    '" y="' +
                    (y + 5) +
                    '" fill="#020e24" font-family="Inter,sans-serif" font-size="13" font-weight="800" text-anchor="middle">' +
                    (n + 1) +
                    '</text>',
            );
            return;
        }
        var fin = n === r.paradas.length - 1;
        var paso = p.i <= actual;
        var sig = p.i === siguiente;
        if (sig) {
            s.push(
                '<circle cx="' +
                    x +
                    '" cy="' +
                    y +
                    '" r="20" fill="#b69b72" opacity=".3"/>',
            );
        }
        s.push(
            '<circle cx="' +
                x +
                '" cy="' +
                y +
                '" r="' +
                (fin ? 13 : 11) +
                '" fill="' +
                (paso ? '#a3e635' : sig ? '#b69b72' : '#17243a') +
                '" stroke="' +
                (paso || sig ? '#020e24' : '#ecf1f9') +
                '" stroke-width="3"/>',
        );
        s.push(
            '<text x="' +
                x +
                '" y="' +
                (y + 4) +
                '" fill="' +
                (paso || sig ? '#020e24' : '#ecf1f9') +
                '" font-family="Inter,sans-serif" font-size="11" font-weight="800" text-anchor="middle">' +
                (fin ? 'F' : n + 1) +
                '</text>',
        );
    });

    function bus(x, y, etiqueta, principal) {
        var b = [];
        if (principal) {
            b.push(
                '<circle cx="' +
                    x +
                    '" cy="' +
                    y +
                    '" r="26" fill="#91db2a" opacity=".35" class="ur-ping"/>',
            );
        }
        b.push(
            '<rect x="' +
                (x - 17) +
                '" y="' +
                (y - 17) +
                '" width="34" height="34" rx="9" fill="' +
                (principal ? '#fddeb1' : '#bac7e4') +
                '" stroke="#020e24" stroke-width="3"/>',
        );
        b.push(
            '<path d="M' +
                (x - 8) +
                ' ' +
                (y + 6) +
                'v-11a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v11M' +
                (x - 8) +
                ' ' +
                (y - 1) +
                'h16M' +
                (x - 5) +
                ' ' +
                (y + 9) +
                'v-3M' +
                (x + 5) +
                ' ' +
                (y + 9) +
                'v-3" fill="none" stroke="#020e24" stroke-width="2.2" stroke-linecap="round"/>',
        );
        b.push(
            '<rect x="' +
                (x - 34) +
                '" y="' +
                (y + 22) +
                '" width="68" height="18" rx="9" fill="#020e24"/>',
        );
        b.push(
            '<text x="' +
                x +
                '" y="' +
                (y + 35) +
                '" fill="#fddeb1" font-family="Inter,sans-serif" font-size="10" font-weight="800" text-anchor="middle" letter-spacing=".5">' +
                etiqueta +
                '</text>',
        );
        return b.join('');
    }

    if (!o.editar && o.recorrido !== -1) {
        var pos = r.puntos[actual];
        s.push(bus(pos[0], pos[1], 'BUS-UTC-01', true));
    }
    (o.unidades || []).forEach(function (u) {
        s.push(bus(u[0], u[1], u[2], false));
    });

    if (o.usuario) {
        var ux = o.usuario[0];
        var uy = o.usuario[1];
        s.push(
            '<circle cx="' +
                ux +
                '" cy="' +
                uy +
                '" r="22" fill="#ecf1f9" opacity=".12"/>',
        );
        s.push(
            '<circle cx="' +
                ux +
                '" cy="' +
                uy +
                '" r="8" fill="#ecf1f9" stroke="#020e24" stroke-width="3"/>',
        );
    }

    s.push('</svg>');
    s.push(
        '<span class="absolute bottom-1 right-2 rounded bg-background/70 px-1.5 py-0.5 text-[10px] text-muted-foreground">© OpenStreetMap</span>',
    );
    contenedor.insertAdjacentHTML('afterbegin', s.join(''));

    // Encuadra la ruta según el tamaño del contenedor, como fitBounds de Leaflet.
    var svg = contenedor.querySelector('svg');
    var caja = o.encuadre || [110, 180, 830, 470];
    function encuadrar() {
        var ancho = contenedor.clientWidth || 1;
        var alto = contenedor.clientHeight || 1;
        var w = caja[2];
        var h = caja[3];
        if (ancho / alto > w / h) {
            w = h * (ancho / alto);
        } else {
            h = w / (ancho / alto);
        }
        var x = caja[0] + caja[2] / 2 - w / 2;
        var y = caja[1] + caja[3] / 2 - h / 2;
        svg.setAttribute('viewBox', [x, y, w, h].join(' '));
    }
    encuadrar();
    if (window.ResizeObserver) {
        new ResizeObserver(encuadrar).observe(contenedor);
    }
};

UR.toast = function (mensaje) {
    var t = document.getElementById('ur-toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'ur-toast';
        t.setAttribute('role', 'status');
        t.setAttribute('aria-live', 'polite');
        t.className =
            'fixed bottom-20 left-1/2 z-[60] hidden -translate-x-1/2 items-center gap-2 rounded-xl border border-border bg-card px-4 py-3 text-sm font-medium text-foreground shadow-xl shadow-black/40 md:bottom-6';
        t.innerHTML =
            '<i data-lucide="circle-check" class="size-[18px] text-live"></i><span></span>';
        document.body.appendChild(t);
        UR.icons();
    }
    t.querySelector('span').textContent = mensaje;
    t.classList.remove('hidden');
    t.classList.add('flex');
    clearTimeout(UR._toast);
    UR._toast = setTimeout(function () {
        t.classList.add('hidden');
        t.classList.remove('flex');
    }, 2600);
};

document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var pagina = body.dataset.pagina;
    var rol = body.dataset.rol || 'estudiante';
    var main = document.querySelector('main');

    if (pagina && main) {
        var envoltura = document.createElement('div');
        envoltura.className = 'flex min-h-screen flex-col lg:pl-72';
        envoltura.innerHTML = header(rol);
        main.parentNode.insertBefore(envoltura, main);
        envoltura.appendChild(main);
        body.insertAdjacentHTML('afterbegin', sidebar(pagina, rol));
        if (rol !== 'admin') {
            body.insertAdjacentHTML('beforeend', barraInferior(pagina));
            main.classList.add('pb-20', 'md:pb-0');
        }
    }

    document.querySelectorAll('[data-ur-tabs-admin]').forEach(function (el) {
        el.innerHTML = UR.htmlTabsAdmin(el.dataset.urTabsAdmin);
    });

    var barra = document.getElementById('ur-sidebar');
    var fondo = document.querySelector('[data-ur-fondo]');
    function menu(abrir) {
        if (!barra) return;
        barra.classList.toggle('-translate-x-full', !abrir);
        fondo.classList.toggle('hidden', !abrir);
    }
    document.querySelectorAll('[data-ur-abrir]').forEach(function (b) {
        b.addEventListener('click', function () {
            menu(true);
        });
    });
    document
        .querySelectorAll('[data-ur-cerrar],[data-ur-fondo]')
        .forEach(function (b) {
            b.addEventListener('click', function () {
                menu(false);
            });
        });

    var campana = document.querySelector('[data-ur-campana]');
    var panel = document.querySelector('[data-ur-panel-notificaciones]');
    if (campana && panel) {
        campana.addEventListener('click', function (e) {
            e.stopPropagation();
            var abierto = panel.classList.toggle('hidden') === false;
            campana.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target)) {
                panel.classList.add('hidden');
                campana.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('[data-ur-mapa]').forEach(function (el) {
        var opciones = {};
        try {
            opciones = JSON.parse(el.dataset.urMapa || '{}');
        } catch {
            opciones = {};
        }
        UR.mapa(el, opciones);
    });

    document.querySelectorAll('[data-ur-toast]').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.preventDefault();
            UR.toast(b.dataset.urToast);
        });
    });

    UR.icons();
});
