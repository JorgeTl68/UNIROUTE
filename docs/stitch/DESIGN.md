---
name: UniRoute Civic Transit
colors:
    surface: '#f7f9ff'
    surface-dim: '#d5dae2'
    surface-bright: '#f7f9ff'
    surface-container-lowest: '#ffffff'
    surface-container-low: '#eff4fc'
    surface-container: '#e9eef6'
    surface-container-high: '#e3e9f0'
    surface-container-highest: '#dde3eb'
    on-surface: '#161c22'
    on-surface-variant: '#44474d'
    inverse-surface: '#2b3137'
    inverse-on-surface: '#ecf1f9'
    outline: '#75777e'
    outline-variant: '#c5c6cd'
    surface-tint: '#525f77'
    primary: '#020e24'
    on-primary: '#ffffff'
    primary-container: '#17243a'
    on-primary-container: '#7e8ba6'
    inverse-primary: '#bac7e4'
    secondary: '#715b37'
    on-secondary: '#ffffff'
    secondary-container: '#fadbae'
    on-secondary-container: '#765f3b'
    tertiary: '#071100'
    on-tertiary: '#ffffff'
    tertiary-container: '#162900'
    on-tertiary-container: '#619b00'
    error: '#ba1a1a'
    on-error: '#ffffff'
    error-container: '#ffdad6'
    on-error-container: '#93000a'
    primary-fixed: '#d6e3ff'
    primary-fixed-dim: '#bac7e4'
    on-primary-fixed: '#0e1c31'
    on-primary-fixed-variant: '#3a475f'
    secondary-fixed: '#fddeb1'
    secondary-fixed-dim: '#e0c297'
    on-secondary-fixed: '#281900'
    on-secondary-fixed-variant: '#584322'
    tertiary-fixed: '#acf847'
    tertiary-fixed-dim: '#91db2a'
    on-tertiary-fixed: '#102000'
    on-tertiary-fixed-variant: '#304f00'
    background: '#f7f9ff'
    on-background: '#161c22'
    surface-variant: '#dde3eb'
typography:
    headline-xl:
        fontFamily: Inter
        fontSize: 40px
        fontWeight: '700'
        lineHeight: 48px
        letterSpacing: -0.02em
    headline-xl-mobile:
        fontFamily: Inter
        fontSize: 32px
        fontWeight: '700'
        lineHeight: 40px
        letterSpacing: -0.015em
    headline-lg:
        fontFamily: Inter
        fontSize: 32px
        fontWeight: '600'
        lineHeight: 40px
        letterSpacing: -0.01em
    headline-lg-mobile:
        fontFamily: Inter
        fontSize: 26px
        fontWeight: '600'
        lineHeight: 34px
        letterSpacing: -0.01em
    headline-md:
        fontFamily: Inter
        fontSize: 24px
        fontWeight: '600'
        lineHeight: 32px
    headline-sm:
        fontFamily: Inter
        fontSize: 20px
        fontWeight: '600'
        lineHeight: 28px
    title-md:
        fontFamily: Inter
        fontSize: 18px
        fontWeight: '600'
        lineHeight: 24px
    body-lg:
        fontFamily: Inter
        fontSize: 16px
        fontWeight: '400'
        lineHeight: 24px
    body-md:
        fontFamily: Inter
        fontSize: 14px
        fontWeight: '400'
        lineHeight: 20px
    label-md:
        fontFamily: Inter
        fontSize: 13px
        fontWeight: '500'
        lineHeight: 18px
        letterSpacing: 0.01em
    label-sm:
        fontFamily: Inter
        fontSize: 12px
        fontWeight: '500'
        lineHeight: 16px
        letterSpacing: 0.02em
    caption:
        fontFamily: Inter
        fontSize: 11px
        fontWeight: '600'
        lineHeight: 14px
        letterSpacing: 0.04em
rounded:
    sm: 0.25rem
    DEFAULT: 0.5rem
    md: 0.75rem
    lg: 1rem
    xl: 1.5rem
    full: 9999px
spacing:
    gutter: 1rem
    gutter-desktop: 1.5rem
    margin: 1rem
    margin-desktop: 2rem
    space-xs: 0.25rem
    space-sm: 0.5rem
    space-md: 1rem
    space-lg: 1.5rem
    space-xl: 2rem
---

## Brand & Style

The design system blends institutional academic prestige with high-efficiency urban mobility tracking. Built for students, university faculty, and campus fleet administrators, it balances the dignity and authority of institutional colors with rapid, scan-friendly transit telemetry.

### Personality & Emotional Response

- **Dependable & Punctual:** Provides instant cognitive clarity regarding arrival times, bus proximity, and schedule precision.
- **Academic Authority & Innovation:** Conveys technological reliability through rich midnight blues paired with warm champagne brass accents.
- **Clarity Under Motion:** High legibility under direct sunlight, while moving, or in low-contrast ambient environments.

### Design Movement

- **Modern Utilitarian Transit:** Characterized by crisp card surfaces, refined micro-borders, high-performance telemetry indicators (lime/chartreuse vectors), and structured floating bottom sheets that maximize interactive map surface area.

## Colors

The color palette integrates institutional heritage colors with purposeful mobility status indicators:

### Role Breakdown

- **Primary (`#17243A` - Midnight Blue):** Used for primary header bars, foundational branding, prominent buttons, and active bottom navigation anchors.
- **Secondary (`#B69B72` - Champagne Gold):** Applied to badges, premium/express route tags, timeline milestone indicators, and accent borders.
- **Tertiary (`#84CC16` - Bright Lime / Chartreuse):** Dedicated telemetry accent for real-time live route paths on maps, active vehicle pins, and pulsing ping rings.
- **Neutral (`#343A40` - Graphite):** Primary body text, dark surface backdrops, contextual icon fills, and high-emphasis labels.
- **Backgrounds:**
    - `#F8FAFC` (Slate Canvas): Default application viewport and underlying map frame background.
    - `#F7F5F0` / `#E8E4DC` (Warm Ivory / Sand): Alternate card and list row surfaces, giving tactile warmth against the cool slate and dark blue.
- **Functional Semantics:**
    - Success (`#22C55E`): On-time arrival status, unit available, capacity low.
    - Warning (`#F59E0B`): Moderate traffic delay (5–15 mins), medium occupancy.
    - Destructive / Alert (`#EF4444`): Route detour, unit out of service, critical delay.

## Typography

Inter serves across all headline, body, and label roles to maximize technical readability, numerical tabular clarity, and cross-platform consistency.

### Hierarchy & Treatment

- **Headlines (H1 40px, H2 32px, H3 24px):** Rendered in bold or semi-bold weights using `#17243A`. They present university hub names, route titles, and active terminal designations.
- **Data & Numbers:** ETA clocks, bus unit identifiers (e.g., "Ruta 04", "04:41 AM"), and speed telemetry utilize tabular lining figures (`font-variant-numeric: tabular-nums`) to prevent jittering when real-time updates push new values.
- **Labels & Microcopy:** Badges, occupancy levels, and schedule captions rely on 11px to 13px medium/semibold weights with slight tracking expansion to maintain instant recognition during commute movement.

## Layout & Spacing

The layout model adapts to high-density interactive mapping and structured information overlays.

### Screen Adaptations

- **Mobile (< 768px):** Full-bleed dynamic map underlay. Floating upper glass chips (traffic toggle, active route selector) paired with a persistent floating bottom navigation bar (64px height) or a collapsible pull-up card sheet containing stop schedules. Canvas margin is fixed at `1rem`.
- **Tablet (768px – 1024px):** Split layout featuring a persistent left sidebar panel (340px) displaying active timelines, vehicle capacity, and stop lists, while the remaining viewport renders the map and live markers.
- **Desktop (> 1024px):** 12-column grid container (`gutter: 1.5rem`, `margin: 2rem`) for administrative consoles and detailed analytics, or a modular 3-tier view: Route Explorer, Real-time Map Center, and Fleet Telemetry Drawer.

## Elevation & Depth

Visual hierarchy is established through soft ambient shadows and low-contrast borders rather than harsh drop-shadows, maintaining a clean transit aesthetic.

### Elevation Levels

- **Level 0 (Flat Ground):** Native map canvas layer (`#F8FAFC`).
- **Level 1 (Card Surfaces):** Timeline station cards and standard list modules using `#FFFFFF` or `#F7F5F0`, bounded by a 1px border of `#E2E8F0` or `#E8E4DC`.
- **Level 2 (Floating Controls & Bottom Sheets):** Map controls, traffic toggle pills, and bottom sheet summaries resting at `0 4px 16px -2px rgba(23, 36, 58, 0.08)`.
- **Level 3 (Modales & Real-Time Alerts):** Urgent detour alerts, vehicle selection overlays, and navigation dialogs elevated at `0 12px 28px -4px rgba(23, 36, 58, 0.16)`.

## Shapes

The geometric architecture balances modern ergonomics with high visual comfort:

- **Base Radius (0.5rem / 8px):** Secondary buttons, form input fields, segmented route toggles, and occupancy indicator pills.
- **Large Radius (`rounded-lg`, 0.75rem / 12px):** Route preview cards, stop itinerary rows, and vehicle information containers.
- **Extra Large Radius (`rounded-xl`, 1rem / 16px):** Floating bottom sheets, interactive schedule drawers, and modal confirmation panels.
- **Pill (Full Radius / 9999px):** Live route badge indicators, floating map quick-toggles (e.g., "Tráfico"), and vehicle live tracking status dots.

## Components

### Buttons

- **Primary:** Background `#17243A`, text `#FFFFFF`, border-radius 8px. Hover shifts to `#243756`. Active scale 0.98.
- **Accent / Action:** Background `#B69B72`, text `#FFFFFF` or `#17243A`, used for tracking start, alarms, and boarding confirmations.
- **Ghost / Map Floating Buttons:** White surface `#FFFFFF` with `#343A40` iconography, 12px rounded corners, subtle border (`#E2E8F0`), elevated at Level 2.

### Chips & Route Badges

- **Live Transit Chip:** Chartreuse background `#84CC16` with dark text `#17243A`, bold weight, accompanied by a pulsing status dot.
- **Status Chips:**
    - On-Time: `#DCFCE7` fill with `#15803D` text.
    - Delayed: `#FEF3C7` fill with `#B45309` text.
    - Off-Service: `#FEE2E2` fill with `#B91C1C` text.

### Stop Timeline & Station Lists

- **Station Line:** Continuous 2px vertical guide in `#E2E8F0`.
- **Active Bus Node:** Concentric circular rings—outer ring in `#84CC16` (40% opacity), center solid `#17243A` with bus glyph or stop icon.
- **Passed Stops:** Muted `#94A3B8` icon with solid connector; upcoming stops highlighted in high-contrast `#17243A`.

### Input Fields & Search Bars

- Background `#FFFFFF` or `#F7F5F0`, 1px border `#CBD5E1`, text `#17243A`, placeholder `#64748B`. Focused state introduces a 2px outer ring in `#B69B72`.

### Map Tracking Overlays

- **Polyline Track:** Route line rendered in vibrant chartreuse `#84CC16` (6px stroke width) with a subtle darker casing `#4D7C0F` (8px stroke) to ensure high visibility against both satellite and light vector maps.
- **Active Bus Marker:** Stylized bus glyph badge tinted in `#17243A` or vibrant vehicle yellow with directional arrow indicating bearing.
