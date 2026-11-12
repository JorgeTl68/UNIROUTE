@php
    // Tema elegido en el menú de la cuenta (cookie "appearance"); oscuro si no hay.
    $appearance = in_array(request()->cookie('appearance'), ['dark', 'light', 'system'], true)
        ? request()->cookie('appearance')
        : 'dark';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $appearance !== 'light'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- "Como el sistema": se quita el oscuro antes de pintar si el sistema va en claro --}}
        @if ($appearance === 'system')
            <script>
                if (!window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.remove('dark');
                }
            </script>
        @endif

        {{-- Fondo del tema mientras carga la app --}}
        <style>
            html {
                background-color: #f7f9ff;
            }

            html.dark {
                background-color: #020e24;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
