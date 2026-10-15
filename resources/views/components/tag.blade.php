@props(['value' => null])

<span {{ $attributes->merge(['class' => 'text-sm font-normal text-ash']) }}>
    {{ $value ?? $slot }}
</span>