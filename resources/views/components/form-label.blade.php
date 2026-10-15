{{-- Etiqueta de campo para los bloques de formulario (gris suave). --}}
@props(['value' => null, 'for' => null])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'block text-sm font-w480 text-slate']) }}
>
    {{ $value ?? $slot }}
</label>
