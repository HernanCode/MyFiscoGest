{{-- Previsualización en vivo del movimiento. Toma del scope Alpine padre:
     `type`, `base`, `vat`, `irpf`, `total` y `money()`. Cambia de color según el
     tipo (azul de Ingresos del Panel / durazno cálido para gasto). --}}
@props([
    'incomeLabel' => null,
    'expenseLabel' => null,
    'baseLabel' => null,
    'vatLabel' => null,
    'retentionLabel' => null,
])

@php
    $incomeLabel = $incomeLabel ?? __('Total a cobrar');
    $expenseLabel = $expenseLabel ?? __('Total a pagar');
    $baseLabel = $baseLabel ?? __('Base imponible');
    $vatLabel = $vatLabel ?? __('IVA');
    $retentionLabel = $retentionLabel ?? __('Retención IRPF');
@endphp

<div
    class="rounded-cards p-6 transition-colors duration-300 sm:p-7"
    :class="type === 'expense' ? 'bg-peach' : 'bg-navy-tint'"
>
    <p
        class="text-sm transition-colors duration-300"
        :class="type === 'expense' ? 'text-sienna/80' : 'text-navy/80'"
        x-text="type === 'expense' ? @js($expenseLabel) : @js($incomeLabel)"
    ></p>

    <p
        class="mt-3 font-sans text-[40px] font-w450 leading-none tracking-tight tabular-nums transition-colors duration-300 lg:text-[44px]"
        :class="type === 'expense' ? 'text-sienna' : 'text-ink'"
        x-text="money(total)"
    ></p>

    <dl class="mt-6 space-y-2 text-[14px]">
        <div class="flex items-baseline justify-between gap-3">
            <dt class="transition-colors duration-300" :class="type === 'expense' ? 'text-sienna/80' : 'text-navy/80'">{{ $baseLabel }}</dt>
            <dd class="tabular-nums transition-colors duration-300" :class="type === 'expense' ? 'text-sienna' : 'text-ink'" x-text="money(base)"></dd>
        </div>
        <div class="flex items-baseline justify-between gap-3">
            <dt class="transition-colors duration-300" :class="type === 'expense' ? 'text-sienna/80' : 'text-navy/80'">{{ $vatLabel }}</dt>
            <dd class="tabular-nums transition-colors duration-300" :class="type === 'expense' ? 'text-sienna' : 'text-ink'" x-text="money(vat)"></dd>
        </div>
        <template x-if="type === 'income'">
            <div class="flex items-baseline justify-between gap-3">
                <dt class="text-navy/80">{{ $retentionLabel }}</dt>
                <dd class="tabular-nums text-ink" x-text="'− ' + money(irpf)"></dd>
            </div>
        </template>
    </dl>
</div>
