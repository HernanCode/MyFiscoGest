<x-app-layout>
    <x-slot name="title">{{ __('Movimientos') }} — {{ __('Recurrentes') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-serif text-4xl font-normal tracking-tight text-ink">
                    {{ __('Movimientos') }}
                </h2>
                @include('movements._tabs', ['activeTab' => 'recurring'])
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a
                    href="{{ route('movements.create', ['tipo' => 'ingreso']) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-buttons border border-hairline bg-paper px-5 text-[16px] text-ink transition-colors hover:bg-mist"
                >
                    {{ __('Nuevo ingreso') }}
                </a>
                <a
                    href="{{ route('movements.create', ['tipo' => 'gasto']) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-buttons border border-hairline bg-paper px-5 text-[16px] text-ink transition-colors hover:bg-mist"
                >
                    {{ __('Nuevo gasto') }}
                </a>
                <form method="POST" action="{{ route('movements.recurring.generate') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ __('Generar ahora') }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    @php
        $euros = fn ($value) => number_format((float) $value, 2, ',', '.').' €';
        $monthsShort = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $shortDate = fn ($date) => $date->format('j').' '.$monthsShort[$date->month - 1].' '.$date->format('Y');

        // Estado de la plantilla como pill monocroma (mismo lenguaje que movimientos).
        $statusOf = function (App\Models\RecurringMovement $recurring) {
            if (! $recurring->active) {
                return ['label' => __('Pausada'), 'style' => 'bg-mist text-ink'];
            }
            if ($recurring->isFinished()) {
                return ['label' => __('Finalizada'), 'style' => 'bg-mist text-ink'];
            }
            if ($recurring->hasDueOccurrences()) {
                return ['label' => __('Pendiente'), 'style' => 'border border-ink text-ink'];
            }
            return ['label' => __('En curso'), 'style' => 'bg-ink text-paper'];
        };

        $activeRecurring = $recurringMovements
            ->filter(fn ($recurring) => $recurring->active && ! $recurring->isFinished());

        $monthlyExpenses = $activeRecurring->where('type', 'expense')->sum('total_amount');
        $monthlyIncomes = $activeRecurring->where('type', 'income')->sum('total_amount');
    @endphp

    {{-- Resumen --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-metric-tile :title="__('Activas')" :value="$activeRecurring->count()" class="!p-5" />
        <x-metric-tile :title="__('Pendientes de generar')" :value="$dueCount" class="!p-5" />
        <x-metric-tile :title="__('Gastos al mes')" :value="$euros($monthlyExpenses)" class="!p-5" />
        <x-metric-tile :title="__('Ingresos al mes')" :value="$euros($monthlyIncomes)" class="!p-5" />
    </div>

    @if ($recurringMovements->isEmpty())
        {{-- Estado vacío --}}
        <x-card variant="white" class="p-14 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-buttons bg-mist">
                <svg class="h-6 w-6 text-slate" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <h3 class="mt-5 font-serif text-2xl font-normal tracking-tight text-ink">{{ __('Sin recurrencias') }}</h3>
            <p class="mt-2 text-body text-slate">{{ __('Crea un gasto o ingreso y marca "Repetir cada mes": la cuota de autónomos, el alquiler o una suscripción.') }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('movements.create', ['tipo' => 'gasto']) }}" class="inline-flex h-10 items-center rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800">
                    {{ __('Crear primer gasto recurrente') }}
                </a>
            </div>
        </x-card>
    @else
        {{-- Versión móvil: filas apiladas --}}
        <div class="space-y-3 lg:hidden">
            @foreach ($recurringMovements as $recurring)
                @php $recurringStatus = $statusOf($recurring); @endphp
                <div class="rounded-cards border border-hairline bg-paper p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-buttons bg-mist text-ink">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    @if ($recurring->type === 'income')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M12 4v16" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 13l-7 7-7-7M12 20V4" />
                                    @endif
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-w480 text-ink">{{ $recurring->concept }}</p>
                                <p class="truncate text-[13px] text-slate">{{ $recurring->counterparty ?: $recurring->category?->name }}</p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-medium tabular-nums text-ink">{{ $euros($recurring->total_amount) }}</p>
                            <p class="text-[12px] tabular-nums text-slate">{{ __('Día') }} {{ $recurring->day_of_month }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-hairline pt-3">
                        <div class="flex items-center gap-3">
                            <span class="text-[13px] text-slate">
                                {{ $recurring->isFinished() || ! $recurring->active ? __('Sin próxima generación') : __('Próxima') }}
                                @if ($recurring->active && ! $recurring->isFinished())
                                    {{ $shortDate($recurring->next_date) }}
                                @endif
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-buttons px-2.5 py-1 text-[11px] {{ $recurringStatus['style'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ str_contains($recurringStatus['style'], 'bg-ink') && ! str_contains($recurringStatus['style'], 'bg-mist') ? 'bg-paper/70' : 'bg-ash/70' }}"></span>
                                {{ $recurringStatus['label'] }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('movements.recurring.edit', $recurring) }}" aria-label="{{ __('Editar') }}" class="flex h-8 w-8 items-center justify-center rounded-buttons text-slate hover:bg-mist hover:text-ink">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65L8 18.65 4.5 19.5 5.35 16 16.86 4.49zM13.5 6.86l3.64 3.64" />
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('movements.recurring.toggle', $recurring) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" aria-label="{{ $recurring->active ? __('Pausar') : __('Activar') }}" class="flex h-8 w-8 items-center justify-center rounded-buttons text-slate hover:bg-mist hover:text-ink">
                                    @if ($recurring->active)
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2zm5 4l6 3-6 3V9z" />
                                        </svg>
                                    @endif
                                </button>
                            </form>
                            <button
                                type="button"
                                aria-label="{{ __('Eliminar') }}"
                                @click="$dispatch('deleting-recurring', @js(route('movements.recurring.destroy', $recurring))); $dispatch('open-modal', 'confirm-recurring-deletion')"
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
        <x-card variant="white" class="hidden overflow-hidden lg:block">
            <div class="overflow-auto">
                <table class="min-w-[880px] w-full text-start text-sm text-ink">
                    <thead>
                        <tr class="border-b border-hairline text-left text-[14px] text-ash">
                            <th class="px-4 py-3 font-medium">{{ __('Tipo') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Concepto') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Cada mes') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Día') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Próxima generación') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Estado') }}</th>
                            <th class="w-16 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recurringMovements as $recurring)
                            @php $recurringStatus = $statusOf($recurring); @endphp
                            <tr class="group border-b border-hairline transition-colors last:border-0 hover:bg-fog">
                                <td class="px-4 py-3.5">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-buttons bg-mist text-ink">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            @if ($recurring->type === 'income')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M12 4v16" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 13l-7 7-7-7M12 20V4" />
                                            @endif
                                        </svg>
                                    </span>
                                </td>
                                <td class="min-w-[220px] px-4 py-3.5">
                                    <span class="truncate font-w480 text-ink">{{ $recurring->concept }}</span>
                                    @if ($recurring->counterparty || $recurring->category)
                                        <p class="truncate text-[13px] text-slate">{{ $recurring->counterparty ?: $recurring->category->name }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right tabular-nums">
                                    <p class="font-medium text-ink">{{ $euros($recurring->total_amount) }}</p>
                                    <p class="text-[13px] tabular-nums text-slate">Base {{ $euros($recurring->base_amount) }} · IVA {{ $euros((float) $recurring->base_amount * $recurring->vat_rate / 100) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-slate">{{ __('Día') }} {{ $recurring->day_of_month }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-slate">
                                    @if ($recurring->active && ! $recurring->isFinished())
                                        {{ $shortDate($recurring->next_date) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-buttons px-2.5 py-1 text-[11px] {{ $recurringStatus['style'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ str_contains($recurringStatus['style'], 'bg-ink') && ! str_contains($recurringStatus['style'], 'bg-mist') ? 'bg-paper/70' : 'bg-ash/70' }}"></span>
                                        {{ $recurringStatus['label'] }}
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
                                            class="absolute right-0 top-full z-30 mt-1 w-44 rounded-small border border-hairline bg-paper p-1 shadow-subtle-2"
                                        >
                                            <a href="{{ route('movements.recurring.edit', $recurring) }}" class="flex items-center gap-2.5 rounded-small px-3 py-2 text-sm text-mid hover:bg-mist hover:text-ink">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65L8 18.65 4.5 19.5 5.35 16 16.86 4.49zM13.5 6.86l3.64 3.64" />
                                                </svg>
                                                {{ __('Editar') }}
                                            </a>
                                            <form method="POST" action="{{ route('movements.recurring.toggle', $recurring) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="flex w-full items-center gap-2.5 rounded-small px-3 py-2 text-left text-sm text-mid hover:bg-mist hover:text-ink">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                        @if ($recurring->active)
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2zm5 4l6 3-6 3V9z" />
                                                        @endif
                                                    </svg>
                                                    {{ $recurring->active ? __('Pausar') : __('Activar') }}
                                                </button>
                                            </form>
                                            <button
                                                type="button"
                                                @click="open = false; $dispatch('deleting-recurring', @js(route('movements.recurring.destroy', $recurring))); $dispatch('open-modal', 'confirm-recurring-deletion')"
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
        </x-card>
    @endif

    {{-- Confirmación de borrado --}}
    <x-modal name="confirm-recurring-deletion" :show="false" focusable>
        <div class="p-6" x-data="{ url: '' }" @deleting-recurring.window="url = $event.detail">
            <form method="post" :action="url" @csrf @method('delete')>
                <h2 class="text-subheading font-medium text-ink">{{ __('¿Eliminar esta recurrencia?') }}</h2>
                <p class="mt-2 text-sm text-slate">
                    {{ __('Dejará de generarse cada mes. Los movimientos ya creados (y sus facturas) se mantienen.') }}
                </p>
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancelar') }}</x-secondary-button>
                    <x-danger-button class="ms-3">{{ __('Eliminar recurrencia') }}</x-danger-button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
