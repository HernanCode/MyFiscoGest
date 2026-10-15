{{-- Pestañas de Movimientos: los movimientos normales o las plantillas recurrentes.
     Compartida por movements/index y movements/recurring. --}}
@php
    $activeTab = $activeTab ?? 'all';
@endphp

<nav class="mt-4 flex w-fit items-center gap-1 rounded-buttons border border-hairline p-1">
    <a
        href="{{ route('movements.index') }}"
        @class([
            'rounded-buttons px-4 py-2 text-body transition',
            'bg-ink text-paper font-medium' => $activeTab === 'all',
            'text-slate hover:text-ink' => $activeTab !== 'all',
        ])
    >{{ __('Todos') }}</a>
    <a
        href="{{ route('movements.recurring.index') }}"
        @class([
            'rounded-buttons px-4 py-2 text-body transition',
            'bg-ink text-paper font-medium' => $activeTab === 'recurring',
            'text-slate hover:text-ink' => $activeTab !== 'recurring',
        ])
    >{{ __('Recurrentes') }}</a>
</nav>
