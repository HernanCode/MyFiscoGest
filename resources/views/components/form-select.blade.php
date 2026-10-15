{{-- Select "flotante" con el mismo tratamiento que x-form-input. --}}
@props(['disabled' => false, 'invalid' => false])

<select
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'w-full appearance-none rounded-inputs border-0 bg-paper ps-4 pe-10 py-3 text-base text-ink shadow-none transition-colors focus:outline-none focus:ring-2 focus:ring-ink/15 disabled:cursor-not-allowed disabled:bg-paper/50 disabled:text-smoke'
            .($invalid ? ' ring-2 ring-red-400 focus:ring-red-400' : ''),
    ]) }}
>
    {{ $slot }}
</select>
