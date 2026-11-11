/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    /** Llave del navegador de Google Maps. Ver docs/google-maps.md. */
    readonly VITE_GOOGLE_MAPS_API_KEY: string;
    /** Map ID de Google Maps (lo exigen los Advanced Markers). */
    readonly VITE_GOOGLE_MAPS_MAP_ID: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
