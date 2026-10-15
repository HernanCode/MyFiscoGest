@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-inputs bg-mist px-4 py-3 text-sm font-w480 text-ink']) }}>
        {{ $status }}
    </div>
@endif
