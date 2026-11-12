import { useSyncExternalStore } from 'react';

export type Appearance = 'dark' | 'light' | 'system';

/** UniRoute nació oscura: es el tema de quien no ha elegido otro. */
const DEFAULT_APPEARANCE: Appearance = 'dark';

const COOKIE = 'appearance';

const listeners = new Set<() => void>();

function isAppearance(value: string | undefined): value is Appearance {
    return value === 'dark' || value === 'light' || value === 'system';
}

/**
 * La preferencia vive en una cookie para que app.blade.php ponga la clase
 * .dark desde el servidor y la página no parpadee al cargar.
 */
function storedAppearance(): Appearance {
    if (typeof document === 'undefined') return DEFAULT_APPEARANCE;

    const value = document.cookie
        .split('; ')
        .find((item) => item.startsWith(`${COOKIE}=`))
        ?.split('=')[1];

    return isAppearance(value) ? value : DEFAULT_APPEARANCE;
}

let current: Appearance = storedAppearance();

function prefersDark(): boolean {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function apply(appearance: Appearance): void {
    const dark =
        appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
}

/** Sigue al sistema operativo cuando la preferencia es "Sistema". */
export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    apply(current);
    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            if (current === 'system') apply(current);
        });
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

export function useAppearance() {
    const appearance = useSyncExternalStore(
        subscribe,
        () => current,
        () => DEFAULT_APPEARANCE,
    );

    const updateAppearance = (mode: Appearance): void => {
        current = mode;
        document.cookie = `${COOKIE}=${mode};path=/;max-age=${60 * 60 * 24 * 365};SameSite=Lax`;
        apply(mode);
        listeners.forEach((listener) => listener());
    };

    return { appearance, updateAppearance } as const;
}
