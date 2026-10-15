@props([
    'title',
    'value',
    'chip' => null,
    'rows' => [],
    'points' => null,
])

@php
// Sparkline gestual (token sienna, sin ejes ni rejilla) al fondo de la tarjeta.
$sparkPoints = null;
if (is_array($points) && count($points) > 1) {
    $min = min(array_merge($points, [0]));
    $max = max(array_merge($points, [0]));
    $range = max($max - $min, 0.01);
    $sparkW = 280;
    $sparkH = 56;
    $step = $sparkW / (count($points) - 1);
    $coords = [];
    foreach ($points as $i => $v) {
        $coords[] = round($i * $step, 1).','.round($sparkH - (($v - $min) / $range * ($sparkH - 8) + 4), 1);
    }
    $sparkPoints = implode(' ', $coords);
}
@endphp

<div {{ $attributes->merge(['class' => 'flex h-full flex-col rounded-cards bg-mist p-6 lg:p-7']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm text-ash">{{ $title }}</p>
        @if ($chip)
            <span class="shrink-0 rounded-buttons border border-hairline bg-paper px-2.5 py-1 text-xs text-ink">{{ $chip }}</span>
        @endif
    </div>

    <p class="mt-3 font-sans text-[28px] font-w450 leading-tight tracking-tight text-ink tabular-nums">
        {{ $value }}
    </p>

    @if ($rows)
        <dl class="mt-5 space-y-2 text-[14px]">
            @foreach ($rows as $row)
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate">{{ $row['label'] }}</dt>
                    <dd class="tabular-nums text-ink">{{ $row['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if ($sparkPoints)
        <div class="mt-auto pt-6">
            <svg viewBox="0 0 280 56" class="h-14 w-full text-sienna" preserveAspectRatio="none" aria-hidden="true">
                <polygon
                    points="{{ $sparkPoints }} 280,56 0,56"
                    fill="currentColor" opacity="0.05"
                />
                <polyline
                    points="{{ $sparkPoints }}"
                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                />
            </svg>
        </div>
    @endif
</div>
