@props(['variant' => 'neutral'])

@php
$classes = [
    'neutral' => 'bg-mist rounded-cards',
    'accent' => 'bg-peach text-sienna rounded-cards',
    // Las tarjetas de contenido no llevan sombra: solo borde fino.
    // (sombra solo en menús y modales — docs/DESIGN.md, adaptación de la app)
    'white' => 'bg-paper rounded-cards border border-hairline',
    // Tarjeta principal del panel: excepción aprobada de azul marino (#e8eef9).
    'tint' => 'bg-navy-tint rounded-cards',
][$variant];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>