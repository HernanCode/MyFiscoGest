<x-app-layout>
    <x-slot name="title">{{ __('Movimientos') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-serif text-4xl font-normal tracking-tight text-ink">
                    {{ __('Movimientos') }}
                </h2>
                @include('movements._tabs', ['activeTab' => 'all'])
            </div>
            <div class="flex items-center gap-2.5">
                <a
                    href="{{ route('movements.create', ['tipo' => 'ingreso']) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Nuevo ingreso') }}
                </a>
                <a
                    href="{{ route('movements.create', ['tipo' => 'gasto']) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-buttons border border-hairline bg-paper px-5 text-[16px] text-ink transition-colors hover:bg-mist"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21l2.5-11M9 21l5-10M15 21l-2-11M19 21l-3-9M7 10a3 3 0 01-3-3M13 11a3 3 0 01-3-3M20 12l-1-6M11 5l.5 3" />
                    </svg>
                    {{ __('Nuevo gasto') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $euros = fn ($value) => number_format((float) $value, 2, ',', '.').' €';
        $monthsShort = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $shortDate = fn ($date) => $date->format('j').' '.$monthsShort[$date->month - 1].' '.$date->format('Y');

        $hasFilters = $q !== '' || count($filters) > 0;

        // Estado del movimiento como pill monocroma.
        $statusOf = function (App\Models\Movement $m) {
            if ($m->payment_status === 'paid') {
                return ['label' => $m->type === 'income' ? __('Cobrada') : __('Pagado'), 'style' => 'bg-ink text-paper'];
            }
            if ($m->type === 'income' && $m->date->lt(today())) {
                return ['label' => __('Vencida'), 'style' => 'border border-ink text-ink'];
            }
            return ['label' => __('Pendiente'), 'style' => 'bg-mist text-ink'];
        };

        // Enlaces de ordenación por columnas.
        $sortLink = function (string $field) use ($filters, $q, $sort, $dir) {
            $next = ($sort === $field) ? ($dir === 'asc' ? 'desc' : 'asc') : 'desc';
            $params = array_merge($filters, ['sort' => $field, 'dir' => $next]);
            if ($q !== '') {
                $params['q'] = $q;
            }
            return route('movements.index', $params);
        };

        // Construye un enlace de filtro a partir de los filtros actuales.
        $filterLink = function (array $overrides) use ($filters, $q, $sort, $dir) {
            $params = array_merge($filters, $overrides);
            if ($q !== '') {
                $params['q'] = $q;
            }
            if ($sort !== 'date' || $dir !== 'desc') {
                $params['sort'] = $sort;
                $params['dir'] = $dir;
            }
            return route('movements.index', $params);
        };
    @endphp

    {{-- Resumen del periodo filtrado --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-metric-tile :title="__('Ingresos')" :value="$euros($summary['income'])" class="!p-5" />
        <x-metric-tile :title="__('Gastos')" :value="$euros($summary['expense'])" class="!p-5" />
        <x-metric-tile :title="__('IVA del periodo')" :value="$euros($summary['vat'])" class="!p-5" />
    </div>

    {{-- Barra de filtros compacta --}}
    <div class="mb-6 flex flex-wrap items-center gap-2">
        {{-- Búsqueda --}}
        <form method="GET" action="{{ route('movements.index') }}" class="relative">
            <svg class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
            </svg>
            <input
                type="text"
                name="q"
                value="{{ $q }}"
                placeholder="{{ __('Buscar por concepto o cliente…') }}"
                class="h-10 w-full rounded-inputs border border-hairline bg-paper ps-10 pe-3 text-[14px] text-ink placeholder:text-smoke focus:border-ink focus:outline-none focus:ring-0 sm:w-64"
            />
            @foreach ($filters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            @if ($sort !== 'date' || $dir !== 'desc')
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
            @endif
        </form>

        {{-- Tipo: segmented control --}}
        <div class="flex h-9 items-center rounded-buttons border border-hairline bg-paper p-0.5">
            @foreach ([
                ['label' => __('Todos'), 'params' => ['type' => null]],
                ['label' => __('Ingresos'), 'params' => ['type' => 'income']],
                ['label' => __('Gastos'), 'params' => ['type' => 'expense']],
            ] as $option)
                @php
                    $active = ($filters['type'] ?? '') === $option['params']['type'];
                @endphp
                <a
                    href="{{ $filterLink($option['params']) }}"
                    class="rounded-buttons px-3.5 py-1.5 text-[14px] transition-colors {{ $active ? 'bg-ink font-w480 text-paper' : 'text-mid hover:text-ink' }}"
                >{{ $option['label'] }}</a>
            @endforeach
        </div>

        {{-- Chips desplegables --}}
        @php
            $chipYearOptions = collect([['label' => __('Todos los años'), 'params' => ['year' => null]]])
                ->concat($fiscalYears->map(fn ($y) => ['label' => (string) $y->year, 'params' => ['year' => $y->id]]));
            $chipYearActive = isset($filters['year']) ? $fiscalYears->firstWhere('id', (int) $filters['year'])?->year : null;
        @endphp
        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
            <button type="button" @click="open = ! open" class="inline-flex h-9 items-center gap-1.5 rounded-buttons border px-3.5 text-[14px] transition-colors {{ $chipYearActive ? 'border-ink bg-ink font-w480 text-paper' : 'border-hairline bg-paper text-ink hover:bg-mist' }}">
                {{ $chipYearActive ? __('Año :year', ['year' => $chipYearActive]) : __('Año') }}
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open" style="display: none;" class="absolute left-0 z-30 mt-2 w-44 overflow-hidden rounded-small border border-hairline bg-paper p-1 shadow-subtle-2">
                @foreach ($chipYearOptions as $option)
                    @php
                        $optionActive = ($filters['year'] ?? null) === $option['params']['year'];
                    @endphp
                    <a href="{{ $filterLink($option['params']) }}" class="block rounded-small px-3 py-2 text-sm {{ $optionActive ? 'bg-mist font-w480 text-ink' : 'text-mid hover:bg-mist hover:text-ink' }}">
                        {{ $option['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
            <button type="button" @click="open = ! open" class="inline-flex h-9 items-center gap-1.5 rounded-buttons border px-3.5 text-[14px] transition-colors {{ isset($filters['quarter']) ? 'border-ink bg-ink font-w480 text-paper' : 'border-hairline bg-paper text-ink hover:bg-mist' }}">
                {{ isset($filters['quarter']) ? __('T:quarter', ['quarter' => $filters['quarter']]) : __('Trimestre') }}
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open" style="display: none;" class="absolute left-0 z-30 mt-2 w-44 overflow-hidden rounded-small border border-hairline bg-paper p-1 shadow-subtle-2">
                @foreach (collect([['label' => __('Todos'), 'params' => ['quarter' => null]]])->concat(collect([1, 2, 3, 4])->map(fn ($q) => ['label' => __('T:quarter', ['quarter' => $q]), 'params' => ['quarter' => $q]])) as $option)
                    @php $optionActive = ($filters['quarter'] ?? null) === $option['params']['quarter']; @endphp
                    <a href="{{ $filterLink($option['params']) }}" class="block rounded-small px-3 py-2 text-sm {{ $optionActive ? 'bg-mist font-w480 text-ink' : 'text-mid hover:bg-mist hover:text-ink' }}">
                        {{ $option['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
            <button type="button" @click="open = ! open" class="inline-flex h-9 items-center gap-1.5 rounded-buttons border px-3.5 text-[14px] transition-colors {{ isset($filters['payment_status']) ? 'border-ink bg-ink font-w480 text-paper' : 'border-hairline bg-paper text-ink hover:bg-mist' }}">
                {{ isset($filters['payment_status']) ? ($filters['payment_status'] === 'paid' ? __('Cobrado / Pagado') : __('Pendientes')) : __('Estado') }}
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open" style="display: none;" class="absolute left-0 z-30 mt-2 w-44 overflow-hidden rounded-small border border-hairline bg-paper p-1 shadow-subtle-2">
                @foreach ([
                    ['label' => __('Todos'), 'params' => ['payment_status' => null]],
                    ['label' => __('Pendientes'), 'params' => ['payment_status' => 'pending']],
                    ['label' => __('Cobrado / Pagado'), 'params' => ['payment_status' => 'paid']],
                ] as $option)
                    @php $optionActive = ($filters['payment_status'] ?? null) === $option['params']['payment_status']; @endphp
                    <a href="{{ $filterLink($option['params']) }}" class="block rounded-small px-3 py-2 text-sm {{ $optionActive ? 'bg-mist font-w480 text-ink' : 'text-mid hover:bg-mist hover:text-ink' }}">
                        {{ $option['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Limpiar filtros --}}
        @if ($hasFilters)
            <a href="{{ route('movements.index') }}" class="inline-flex h-9 items-center px-1 text-[14px] text-slate hover:text-ink">
                {{ __('Limpiar filtros') }}
            </a>
        @endif

        {{-- Exportar a CSV el periodo filtrado (para el gestor) --}}
        <a
            href="{{ route('movements.export', array_filter(request()->only(['year', 'quarter', 'type', 'payment_status', 'q']), fn ($value) => $value !== null && $value !== '')) }}"
            class="ms-auto inline-flex h-9 items-center gap-1.5 rounded-buttons border border-hairline bg-paper px-3.5 text-[14px] text-ink transition-colors hover:bg-mist"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14" />
            </svg>
            {{ __('Exportar CSV') }}
        </a>
    </div>

    @if ($movements->isEmpty())
        {{-- Estado vacío --}}
        <x-card variant="white" class="p-14 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-buttons bg-mist">
                <svg class="h-6 w-6 text-slate" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2zM14 3v4a1 1 0 001 1h4" />
                </svg>
            </div>
            @if ($hasFilters)
                <h3 class="mt-5 font-serif text-2xl font-normal tracking-tight text-ink">{{ __('Sin resultados con estos filtros') }}</h3>
                <p class="mt-2 text-body text-slate">{{ __('Prueba a ampliar la búsqueda o a limpiar los filtros activos.') }}</p>
                <div class="mt-6 flex justify-center gap-3">
                    <a href="{{ route('movements.index') }}" class="inline-flex h-10 items-center rounded-buttons border border-hairline bg-paper px-5 text-[16px] text-ink transition-colors hover:bg-mist">
                        {{ __('Limpiar filtros') }}
                    </a>
                </div>
            @else
                <h3 class="mt-5 font-serif text-2xl font-normal tracking-tight text-ink">{{ __('Sin movimientos') }}</h3>
                <p class="mt-2 text-body text-slate">{{ __('Registra tu primer ingreso o gasto para empezar a organizar el año fiscal.') }}</p>
                <div class="mt-6 flex justify-center gap-3">
                    <a href="{{ route('movements.create', ['tipo' => 'ingreso']) }}" class="inline-flex h-10 items-center rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800">
                        {{ __('Crear primer movimiento') }}
                    </a>
                </div>
            @endif
        </x-card>
    @else
        {{-- Versión móvil: filas apiladas --}}
        <div class="space-y-3 lg:hidden">
            @foreach ($movements as $movement)
                @php $movementStatus = $statusOf($movement); @endphp
                <div class="rounded-cards border border-hairline bg-paper p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-buttons bg-mist text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    @if ($movement->type === 'income')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M12 4v16" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 13l-7 7-7-7M12 20V4" />
                                    @endif
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-w480 text-ink">{{ $movement->concept }}</p>
                                <p class="truncate text-[13px] text-slate">
                                    {{ $movement->counterparty ?: $movement->category?->name }}
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-medium tabular-nums text-ink">{{ $euros($movement->total_amount) }}</p>
                            <p class="text-[12px] tabular-nums text-slate">Base {{ $euros($movement->base_amount) }} · IVA {{ $euros($movement->vat_amount) }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-hairline pt-3">
                        <div class="flex items-center gap-3">
                            <span class="text-[13px] text-slate">{{ $shortDate($movement->date) }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-buttons px-2.5 py-1 text-[11px] {{ $movementStatus['style'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ str_contains($movementStatus['style'], 'bg-ink') ? 'bg-paper/70' : 'bg-ash/70' }}"></span>
                                {{ $movementStatus['label'] }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            @if ($movement->invoice_path)
                                <a href="{{ route('movements.invoice', $movement) }}" target="_blank" aria-label="{{ __('Ver factura') }}" class="flex h-8 w-8 items-center justify-center rounded-buttons text-slate hover:bg-mist hover:text-ink">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                </a>
                            @endif
                            <a href="{{ route('movements.edit', $movement) }}" aria-label="{{ __('Editar') }}" class="flex h-8 w-8 items-center justify-center rounded-buttons text-slate hover:bg-mist hover:text-ink">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65L8 18.65 4.5 19.5 5.35 16 16.86 4.49zM13.5 6.86l3.64 3.64" />
                                </svg>
                            </a>
                            <button
                                type="button"
                                aria-label="{{ __('Eliminar') }}"
                                @click="$dispatch('deleting-movement', @js(route('movements.destroy', $movement))); $dispatch('open-modal', 'confirm-movement-deletion')"
                                class="flex h-8 w-8 items-center justify-center rounded-buttons text-slate hover:bg-mist hover:text-ink"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-2-1.86L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h14" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Versión escritorio: tabla --}}
        <x-card variant="white" class="hidden overflow-hidden lg:block" x-data="{ selected: [], selectAll: false }">
            {{-- Barra de acciones masivas --}}
            <div x-show="selected.length > 0" style="display: none;" class="flex items-center justify-between gap-4 border-b border-hairline bg-fog px-5 py-3">
                <p class="text-sm text-ink" x-text="selected.length + ' {{ __('seleccionados') }}'"></p>
                <button
                    type="button"
                    @click="$dispatch('deleting-movements', selected); $dispatch('open-modal', 'confirm-movement-deletion')"
                    class="inline-flex h-9 items-center gap-2 rounded-buttons border border-ink px-4 text-sm text-ink transition-colors hover:bg-paper"
                >
                    {{ __('Eliminar seleccionados') }}
                </button>
            </div>

            <div class="max-h-[640px] overflow-auto">
                <table class="min-w-[880px] w-full text-start text-sm text-ink">
                    <thead>
                        <tr class="border-b border-hairline text-left text-[14px] text-ash">
                            <th class="sticky top-0 z-10 w-12 bg-paper px-4 py-3 font-medium">
                                <input
                                    type="checkbox"
                                    x-model="selectAll"
                                    @change="selected = selectAll ? @js($movements->pluck('id')->all()) : []"
                                    class="h-4 w-4 rounded border-hairline text-ink focus:ring-ink"
                                    aria-label="{{ __('Seleccionar todos') }}"
                                />
                            </th>
                            <th class="sticky top-0 z-10 bg-paper px-4 py-3 font-medium">{{ __('Tipo') }}</th>
                            <th class="sticky top-0 z-10 bg-paper px-4 py-3 font-medium">
                                <a href="{{ $sortLink('concept') }}" class="inline-flex items-center gap-1 transition-colors hover:text-ink">
                                    {{ __('Concepto') }}
                                    @if ($sort === 'concept')
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            @if ($dir === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="sticky top-0 z-10 bg-paper px-4 py-3 font-medium">
                                <a href="{{ $sortLink('date') }}" class="inline-flex items-center gap-1 transition-colors hover:text-ink">
                                    {{ __('Fecha') }}
                                    @if ($sort === 'date')
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            @if ($dir === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="sticky top-0 z-10 bg-paper px-4 py-3 text-right font-medium">
                                <a href="{{ $sortLink('total_amount') }}" class="inline-flex items-center gap-1 transition-colors hover:text-ink">
                                    {{ __('Total') }}
                                    @if ($sort === 'total_amount')
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            @if ($dir === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="sticky top-0 z-10 bg-paper px-4 py-3 font-medium">{{ __('Estado') }}</th>
                            <th class="sticky top-0 z-10 w-16 bg-paper px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            @php $movementStatus = $statusOf($movement); @endphp
                            <tr class="group border-b border-hairline transition-colors last:border-0 hover:bg-fog">
                                <td class="px-4 py-3.5">
                                    <input
                                        type="checkbox"
                                        x-model="selected"
                                        :value="{{ $movement->id }}"
                                        class="h-4 w-4 rounded border-hairline text-ink focus:ring-ink"
                                        aria-label="{{ __('Seleccionar :concept', ['concept' => $movement->concept]) }}"
                                    />
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-buttons bg-mist text-ink">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            @if ($movement->type === 'income')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M12 4v16" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 13l-7 7-7-7M12 20V4" />
                                            @endif
                                        </svg>
                                    </span>
                                </td>
                                <td class="min-w-[220px] px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate font-w480 text-ink">{{ $movement->concept }}</span>
                                        @if ($movement->invoice_path)
                                            <a href="{{ route('movements.invoice', $movement) }}" target="_blank" title="{{ __('Ver factura') }}" class="shrink-0 text-slate hover:text-ink">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                    @if ($movement->counterparty || $movement->category)
                                        <p class="truncate text-[13px] text-slate">{{ $movement->counterparty ?: $movement->category->name }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-slate">{{ $shortDate($movement->date) }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                    <p class="font-medium tabular-nums text-ink">{{ $euros($movement->total_amount) }}</p>
                                    <p class="text-[13px] tabular-nums text-slate">Base {{ $euros($movement->base_amount) }} · IVA {{ $euros($movement->vat_amount) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-buttons px-2.5 py-1 text-[11px] {{ $movementStatus['style'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ str_contains($movementStatus['style'], 'bg-ink') ? 'bg-paper/70' : 'bg-ash/70' }}"></span>
                                        {{ $movementStatus['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div x-data="{ open: false }" class="relative flex justify-end opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100 max-lg:opacity-100">
                                        <button
                                            type="button"
                                            @click="open = ! open"
                                            aria-label="{{ __('Acciones') }}"
                                            class="flex h-8 w-8 items-center justify-center rounded-buttons text-mid transition-colors hover:bg-mist hover:text-ink"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="5" cy="12" r="1.7" />
                                                <circle cx="12" cy="12" r="1.7" />
                                                <circle cx="19" cy="12" r="1.7" />
                                            </svg>
                                        </button>
                                        <div
                                            x-show="open"
                                            style="display: none;"
                                            @click.outside="open = false"
                                            class="absolute right-0 top-full z-30 mt-1 w-40 rounded-small border border-hairline bg-paper p-1 shadow-subtle-2"
                                        >
                                            <a href="{{ route('movements.edit', $movement) }}" class="flex items-center gap-2.5 rounded-small px-3 py-2 text-sm text-mid hover:bg-mist hover:text-ink">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65L8 18.65 4.5 19.5 5.35 16 16.86 4.49zM13.5 6.86l3.64 3.64" />
                                                </svg>
                                                {{ __('Editar') }}
                                            </a>
                                            <button
                                                type="button"
                                                @click="open = false; $dispatch('deleting-movement', @js(route('movements.destroy', $movement))); $dispatch('open-modal', 'confirm-movement-deletion')"
                                                class="flex w-full items-center gap-2.5 rounded-small px-3 py-2 text-left text-sm text-mid hover:bg-mist hover:text-ink"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-2-1.86L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h14" />
                                                </svg>
                                                {{ __('Eliminar') }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="border-t border-hairline px-5 py-4">
                    {{ $movements->links() }}
                </div>
            @endif
        </x-card>
    @endif

    {{-- Confirmación de borrado (individual o masivo) --}}
    <x-modal name="confirm-movement-deletion" :show="false" focusable>
        <div class="p-6" x-data="{ url: '', ids: [], bulk: false, deleteUrl: @js(route('movements.destroy', ['movement' => '__ID__'])) }"
            @deleting-movement.window="url = $event.detail; bulk = false; ids = []"
            @deleting-movements.window="ids = $event.detail; bulk = true; url = ''"
        >
            <form
                method="post"
                :action="url"
                x-show="!bulk"
                @csrf
                @method('delete')
            >
                <h2 class="text-subheading font-medium text-ink">{{ __('¿Eliminar este movimiento?') }}</h2>
                <p class="mt-2 text-sm text-slate">
                    {{ __('El movimiento y su factura adjunta (si existe) se borrarán de forma permanente.') }}
                </p>
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancelar') }}</x-secondary-button>
                    <x-danger-button class="ms-3">{{ __('Eliminar movimiento') }}</x-danger-button>
                </div>
            </form>

            <div x-show="bulk">
                <h2 class="text-subheading font-medium text-ink" x-text="'{{ __('¿Eliminar') }} ' + ids.length + ' {{ __('movimientos?') }}'"></h2>
                <p class="mt-2 text-sm text-slate">
                    {{ __('Se borrarán de forma permanente los movimientos seleccionados y sus facturas adjuntas (si existen).') }}
                </p>
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancelar') }}</x-secondary-button>
                    <button
                        type="button"
                        @click="
                            (async () => {
                                for (const id of ids) {
                                    await fetch(deleteUrl.replace('__ID__', id), {
                                        method: 'DELETE',
                                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                        credentials: 'same-origin',
                                    });
                                }
                                window.location.reload();
                            })()
                        "
                        class="ms-3 inline-flex h-10 items-center rounded-buttons bg-ink px-5 text-sm text-paper transition-colors hover:bg-slate-800"
                    >{{ __('Eliminar seleccionados') }}</button>
                </div>
            </div>
        </div>
    </x-modal>
</x-app-layout>