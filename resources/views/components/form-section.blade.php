{{-- Bloque de formulario: fondo gris suave y esquinas grandes, como las tarjetas
     neutras (IVA / IRPF) del Panel. Agrupa campos relacionados sin marco. --}}
@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-cards bg-mist p-5 sm:p-6']) }}>
    @if ($title)
        <h3 class="font-serif text-body-lg font-normal tracking-tight text-ink">{{ $title }}</h3>
    @endif

    @if ($description)
        <p class="mt-1 text-sm text-slate">{{ $description }}</p>
    @endif

    <div class="mt-4">
        {{ $slot }}
    </div>
</section>
