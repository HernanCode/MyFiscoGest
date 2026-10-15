@props(['narrow' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts: Inter variable (pesos intermedios 430/450/480 del design.md) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div
            class="min-h-screen bg-paper"
            x-data="{ sidebarCollapsed: (() => { try { return localStorage.getItem('sidebar.collapsed') === '1' } catch (e) { return false } })() }"
        >
            <x-sidebar />

            {{-- Contenido: barra superior + página (el sidebar fijo ocupa 232px en escritorio).
                 Un único contenedor (x-page-container) incluye la barra superior y el contenido
                 para que ambos compartan columna. Ancho adaptable pegado al sidebar; solo en
                 pantallas muy grandes se limita a 1800px y se centra.
                 Las pantallas de formulario usan la variante estrecha (`narrow`). --}}
            <div
                x-cloak
                class="min-h-screen transition-[padding] duration-200"
                :class="sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-[232px]'"
            >
                <x-page-container :narrow="$narrow">
                    <x-topbar :title="$title ?? config('app.name', 'Fiscal')" />

                    <main class="pb-16 pt-6">
                        @isset($header)
                            <header class="mb-10">
                                {{ $header }}
                            </header>
                        @endisset

                        {{ $slot }}
                    </main>
                </x-page-container>
            </div>
        </div>

        {{-- Toast flotante: se autodescarta a los 4 s --}}
        @if (session('status') && request()->routeIs('movements.*', 'filings.*'))
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 4000)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-3"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="display: none;"
                role="status"
                class="fixed bottom-6 right-6 z-50 max-w-xs"
            >
                <div class="flex items-center gap-3 rounded-inputs bg-ink px-5 py-4 text-sm text-paper shadow-subtle-2">
                    <svg class="h-4 w-4 shrink-0 text-paper/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="leading-snug">{{ session('status') }}</span>
                </div>
            </div>
        @endif
    </body>
</html>