@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-inputs border-hairline bg-paper px-4 py-3 text-body text-ink placeholder:text-smoke focus:border-ink focus:ring-0 focus:outline-none']) }}>