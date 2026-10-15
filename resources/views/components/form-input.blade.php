{{-- Input "flotante": sin borde y radio grande. `tone="paper"` (blanco) para
     bloques gris suave; `tone="mist"` para superficies blancas. --}}
@props(['disabled' => false, 'invalid' => false, 'tone' => 'paper'])

@php
    $background = $tone === 'mist' ? 'bg-mist' : 'bg-paper';
@endphp

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'w-full rounded-inputs border-0 '.$background.' px-4 py-3 text-base text-ink placeholder:text-smoke shadow-none transition-colors focus:outline-none focus:ring-2 focus:ring-ink/15 disabled:cursor-not-allowed disabled:opacity-60'
            .($invalid ? ' ring-2 ring-red-400 focus:ring-red-400' : ''),
    ]) }}
>
