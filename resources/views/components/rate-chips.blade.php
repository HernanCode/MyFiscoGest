{{-- Selector de porcentaje en chips (IVA, retención IRPF, % deducible).
     Los valores habituales son botones; "Otro" revela un campo manual.
     Espera que el scope Alpine padre exponga `isOption(valor, opciones)`. --}}
@props([
    'name',
    'model',
    'options',
    'label' => null,
    'id' => null,
    'suffix' => '%',
    'help' => null,
])

@php
    $values = array_values($options);
    $on = 'bg-ink text-paper font-w480';
    $off = 'bg-paper text-mid hover:text-ink';
    $chip = 'h-9 rounded-buttons px-4 text-[14px] tabular-nums transition-colors';
@endphp

<div {{ $attributes }}>
    @if ($label)
        <x-form-label :for="$id">{{ $label }}</x-form-label>
    @endif

    <input type="hidden" name="{{ $name }}" :value="{{ $model }}">

    <div class="mt-2 flex flex-wrap gap-2" role="group" aria-label="{{ $label }}">
        @foreach ($values as $value)
            <button
                type="button"
                @click="{{ $model }} = {{ json_encode($value) }}"
                :aria-pressed="isOption({{ $model }}, @js($values)) && Number({{ $model }}) === Number({{ $value }})"
                :class="isOption({{ $model }}, @js($values)) && Number({{ $model }}) === Number({{ $value }}) ? '{{ $on }}' : '{{ $off }}'"
                class="{{ $chip }}"
            >{{ $value }}{{ $suffix }}</button>
        @endforeach

        <button
            type="button"
            @click="if (isOption({{ $model }}, @js($values))) { {{ $model }} = '' }"
            :aria-pressed="! isOption({{ $model }}, @js($values))"
            :class="! isOption({{ $model }}, @js($values)) ? '{{ $on }}' : '{{ $off }}'"
            class="{{ $chip }}"
        >{{ __('Otro') }}</button>
    </div>

    <div x-show="! isOption({{ $model }}, @js($values))" x-cloak class="mt-2">
        <x-form-input
            :id="$id"
            type="number"
            step="0.01"
            min="0"
            x-model.number="{{ $model }}"
            class="max-w-[150px]"
        />
    </div>

    @if ($help)
        <p class="mt-2 text-[13px] text-slate">{{ $help }}</p>
    @endif
</div>
