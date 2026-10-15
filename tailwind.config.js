import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            // Sistema "Steep" — docs/DESIGN.md
            colors: {
                ink: '#17191c',      // texto principal, botón relleno
                paper: '#ffffff',    // lienzo
                mist: '#f2f2f3',     // tarjetas neutras
                fog: '#fafafb',      // bandas de sección
                slate: '#777b86',    // texto secundario / enlaces
                ash: '#979799',      // etiquetas de categoría
                smoke: '#a3a6af',    // placeholders
                peach: '#fbe1d1',    // tarjeta acento
                sienna: '#5d2a1a',   // texto sobre peach
                hairline: '#ececec', // bordes finos
                // EXCEPCIÓN aprobada: azul marino de datos (tarjeta principal del panel y barras de gráficas)
                navy: '#1f4a8a',
                'navy-tint': '#e8eef9',
                // Texto secundario del sidebar (solo esta app)
                mid: '#4d5159',
                // Barras de gastos en las gráficas del panel
                bar: '#c9ccd3',
                // EXCEPCIONES funcionales: estados de los trimestres en la sidebar.
                success: '#2f7d5b', // presentado
                warning: '#b7791f', // trimestre en curso
            },

            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                // DESIGN.md: Signifier (serif editorial) — sustituto Georgia/ui-serif.
                // (El css_variables.css del origen cae a ui-sans-serif; se ignora por contradecir al propio DESIGN.md.)
                serif: ['Georgia', 'ui-serif', 'Georgia', ...defaultTheme.fontFamily.serif],
            },

            // Pesos intermedios del sistema (docs/css_variables.css · --font-weight-w*).
            // Sustituyen a los valores arbitrarios font-[430|450|480] usados en las vistas.
            fontWeight: {
                w430: '430',
                w450: '450',
                w480: '480',
            },

            fontSize: {
                caption: ['15px', { lineHeight: '1.5' }],
                body: ['17px', { lineHeight: '1.35' }],
                'body-lg': ['20px', { lineHeight: '1.35' }],
                subheading: ['22px', { lineHeight: '1.5' }],
                'heading-sm': ['26px', { lineHeight: '1.18', letterSpacing: '-0.23px' }],
                heading: ['44px', { lineHeight: '1.3', letterSpacing: '-0.66px' }],
                'heading-lg': ['64px', { lineHeight: '1.3', letterSpacing: '-0.96px' }],
                display: ['90px', { lineHeight: '1.3', letterSpacing: '-2.25px' }],
            },

            borderRadius: {
                cards: '24px',
                images: '12px',
                inputs: '16px',
                buttons: '9999px',
                small: '16px',
                elevated: '20px',
            },

            boxShadow: {
                subtle: 'oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.08) 0px 4px 24px 0px',
                'subtle-2': 'oklab(0 0 0 / 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 8px 40px 0px',
                'subtle-3': 'rgba(4, 23, 43, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 20px 25px -5px, rgba(0, 0, 0, 0.1) 0px 8px 10px -6px',
            },
        },
    },

    plugins: [forms],
};