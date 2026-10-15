@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-small px-3 py-2 text-start text-body font-medium text-ink bg-mist focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full rounded-small px-3 py-2 text-start text-body font-normal text-slate hover:bg-mist hover:text-ink focus:outline-none focus:text-ink focus:bg-mist transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>