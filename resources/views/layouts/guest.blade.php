<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon (SVG + PNG/ICO de reserva) -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <meta name="theme-color" content="#17191c">

        <!-- Fonts: Inter variable (pesos intermedios 430/450/480 del design.md) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen bg-paper lg:grid lg:grid-cols-2">
            {{-- Panel editorial (solo escritorio): foto de fondo + titular --}}
            <aside class="relative hidden overflow-hidden lg:flex">
                <img
                    src="{{ asset('images/login-cover.webp') }}"
                    alt=""
                    loading="eager"
                    fetchpriority="high"
                    class="absolute inset-0 h-full w-full object-cover grayscale"
                >
                <div class="absolute inset-0 bg-ink/40"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-ink/85 via-ink/25 to-transparent"></div>

                <div class="relative flex w-full flex-col justify-between p-12 xl:p-16">
                    <a href="/" class="inline-flex w-fit">
                        <x-application-logo class="!text-paper" />
                    </a>

                    <div>
                        <h2 class="font-serif text-[clamp(88px,9vw,140px)] font-normal leading-[0.95] tracking-[-0.03em] text-paper">
                            {{ __('Lleva tu fiscalidad en orden.') }}
                        </h2>
                        <p class="mt-6 max-w-[44ch] text-body-lg text-paper/80">
                            {{ __('Ingresos, gastos y estimación de IVA e IRPF del trimestre. Todo en un mismo sitio, sin hojas de cálculo.') }}
                        </p>
                    </div>
                </div>
            </aside>

            {{-- Formulario --}}
            <main class="flex min-h-screen items-center justify-center px-5 py-12 sm:px-8">
                <div class="w-full max-w-md lg:max-w-lg">
                    <a href="/" class="mb-8 inline-flex lg:hidden">
                        <x-application-logo />
                    </a>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
