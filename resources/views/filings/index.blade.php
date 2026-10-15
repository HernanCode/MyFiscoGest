<x-app-layout>
    <x-slot name="title">{{ __('Presentaciones') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-serif text-4xl font-normal tracking-tight text-ink">
                    {{ __('Presentaciones') }}
                </h2>
                <p class="mt-3 text-body text-slate">
                    {{ __('Vencimientos de los modelos 303 (IVA) y 130 (IRPF). Marca cada uno al presentarlo.') }}
                </p>
            </div>

            @if ($fiscalYears->isNotEmpty())
                <div class="relative shrink-0">
                    <select
                        onchange="window.location = '{{ route('filings.index') }}?year=' + this.value"
                        class="appearance-none rounded-inputs border border-hairline bg-paper py-2 pl-3 pr-9 text-sm text-ink focus:border-ink focus:outline-none focus:ring-0"
                        aria-label="{{ __('Año fiscal') }}"
                    >
                        @foreach ($fiscalYears as $year)
                            <option value="{{ $year->id }}" @selected($selectedYear && $selectedYear->id === $year->id)>{{ $year->year }}</option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute top-1/2 right-3 h-3.5 w-3.5 -translate-y-1/2 text-mid" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            @endif
        </div>
    </x-slot>

    @php
        $euros = fn ($value) => number_format((float) $value, 2, ',', '.').' €';
        $monthsShort = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $shortDate = fn ($date) => $date->format('j').' '.$monthsShort[$date->month - 1].' '.$date->format('Y');

        $modelLabels = [
            '303' => __('IVA trimestral'),
            '130' => __('IRPF fraccionado'),
        ];

        // Estado de cada vencimiento, como pill monocroma (mismo lenguaje que Movimientos).
        $statusOf = function (App\Models\QuarterFiling $filing) {
            if ($filing->status === 'filed') {
                return ['label' => __('Presentado'), 'style' => 'bg-ink text-paper', 'dot' => 'bg-paper/70'];
            }
            if ($filing->due_date->lt(today())) {
                return ['label' => __('Vencido'), 'style' => 'border border-ink text-ink', 'dot' => 'bg-ash/70'];
            }
            return ['label' => __('Pendiente'), 'style' => 'bg-mist text-ink', 'dot' => 'bg-ash/70'];
        };

        $allFilings = $filingsByQuarter->flatten(1);
        $pendingCount = $allFilings->filter(fn ($row) => $row['filing']->status === 'pending')->count();
        $filedCount = $allFilings->count() - $pendingCount;
        $yearEstimate = $allFilings->sum(fn ($row) => $row['estimate']);
    @endphp

    @if (! $selectedYear)
        {{-- Estado vacío: aún no hay ningún año fiscal --}}
        <x-card variant="white" class="p-14 text-center">
            <h3 class="font-serif text-2xl font-normal tracking-tight text-ink">{{ __('Sin presentaciones') }}</h3>
            <p class="mt-2 text-body text-slate">{{ __('Crea tu primer movimiento y el año fiscal generará sus vencimientos automáticamente.') }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('movements.create', ['tipo' => 'ingreso']) }}" class="inline-flex h-10 items-center rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800">
                    {{ __('Crear primer ingreso') }}
                </a>
            </div>
        </x-card>
    @else
        {{-- Resumen --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-metric-tile
                :title="__('Pendientes')"
                :value="$pendingCount"
                :chip="$pendingCount === 0 ? __('Todo presentado') : null"
                class="!p-5"
            />
            <x-metric-tile :title="__('Presentadas')" :value="$filedCount" class="!p-5" />
            <x-metric-tile :title="__('Estimado del año')" :value="$euros($yearEstimate)" class="!p-5" />
        </div>

        <x-card variant="white" class="overflow-hidden" x-data>
            <div class="divide-y divide-hairline">
                @foreach ($filingsByQuarter as $quarter => $rows)
                    @php
                        $quarterAllFiled = $rows->every(fn ($row) => $row['filing']->status === 'filed');
                    @endphp

                    {{-- Cabecera del trimestre --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 bg-fog px-6 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-[8px] bg-paper text-sm font-w480 text-ink ring-1 ring-hairline">
                                T{{ $quarter }}
                            </span>
                            <span class="text-sm text-slate">{{ __('Trimestre :quarter', ['quarter' => $quarter]) }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-buttons px-2.5 py-1 text-[11px] {{ $quarterAllFiled ? 'bg-ink text-paper' : 'bg-mist text-ink' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $quarterAllFiled ? 'bg-paper/70' : 'bg-ash/70' }}"></span>
                            {{ $quarterAllFiled ? __('Presentado') : __('Pendiente') }}
                        </span>
                    </div>

                    {{-- Vencimientos del trimestre --}}
                    @foreach ($rows as $row)
                        @php
                            $filing = $row['filing'];
                            $filingStatus = $statusOf($filing);
                        @endphp
                        <div class="flex flex-wrap items-center gap-4 px-6 py-4 transition-colors hover:bg-fog">
                            {{-- Modelo --}}
                            <div class="flex min-w-[200px] flex-1 items-center gap-3">
                                <span class="flex h-10 w-12 shrink-0 items-center justify-center rounded-[10px] bg-mist font-sans text-[15px] font-w480 tabular-nums text-ink">
                                    {{ $filing->model }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-[15px] font-w480 text-ink">{{ $modelLabels[$filing->model] }}</p>
                                    <p class="truncate text-[13px] text-slate">{{ __('Vence el :date', ['date' => $shortDate($filing->due_date)]) }}</p>
                                </div>
                            </div>

                            {{-- Estimado / pagado --}}
                            <div class="min-w-[150px] shrink-0 text-right">
                                @if ($filing->status === 'filed')
                                    <p class="font-medium tabular-nums text-ink">{{ $filing->amount_paid !== null ? $euros($filing->amount_paid) : '—' }}</p>
                                    <p class="text-[12px] text-slate">
                                        {{ __('Presentado :date', ['date' => $filing->filed_at ? $shortDate($filing->filed_at) : '—']) }}
                                    </p>
                                @else
                                    <p class="font-medium tabular-nums text-ink">{{ $euros($row['estimate']) }}</p>
                                    <p class="text-[12px] text-slate">
                                        {{ $row['estimate'] < 0 ? __('A compensar') : __('Estimado') }}
                                    </p>
                                @endif
                            </div>

                            {{-- Estado --}}
                            <div class="w-28 shrink-0">
                                <span class="inline-flex items-center gap-1.5 rounded-buttons px-2.5 py-1 text-[11px] {{ $filingStatus['style'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $filingStatus['dot'] }}"></span>
                                    {{ $filingStatus['label'] }}
                                </span>
                            </div>

                            {{-- Acción --}}
                            <div class="shrink-0">
                                @if ($filing->status === 'filed')
                                    <form method="POST" action="{{ route('filings.reopen', $filing) }}">
                                        @csrf
                                        @method('patch')
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 items-center rounded-buttons border border-hairline bg-paper px-4 text-sm text-ink transition-colors hover:bg-mist"
                                        >
                                            {{ __('Reabrir') }}
                                        </button>
                                    </form>
                                @else
                                    <button
                                        type="button"
                                        @click="$dispatch('filing-selected', { url: @js(route('filings.mark-filed', $filing)), estimate: '{{ number_format((float) $row['estimate'], 2, '.', '') }}', label: '{{ $filing->model }} · T{{ $filing->quarter }}' }); $dispatch('open-modal', 'mark-filing')"
                                        class="inline-flex h-9 items-center rounded-buttons bg-ink px-4 text-sm text-paper transition-colors hover:bg-slate-800"
                                    >
                                        {{ __('Marcar presentado') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </x-card>

        <p class="mt-6 text-[13px] text-slate">
            {{ __('Los importes son estimaciones para organizarte. Ajusta el importe pagado al presentar.') }}
        </p>

        {{-- Modal para marcar como presentado --}}
        <x-modal name="mark-filing" :show="false" focusable>
            <div
                class="p-6"
                x-data="{ url: '', amount: '', label: '' }"
                @filing-selected.window="url = $event.detail.url; amount = $event.detail.estimate; label = $event.detail.label"
            >
                <form method="POST" :action="url">
                    @csrf
                    @method('patch')

                    <h2 class="text-subheading font-medium text-ink">
                        {{ __('Marcar como presentado') }} <span class="tabular-nums" x-text="label"></span>
                    </h2>
                    <p class="mt-2 text-sm text-slate">
                        {{ __('Indica el importe que has pagado realmente. Puedes dejarlo vacío o corregirlo si fue distinto del estimado.') }}
                    </p>

                    <label for="amount_paid" class="mt-5 block text-sm font-medium text-ink">{{ __('Importe pagado') }}</label>
                    <div class="relative mt-1">
                        <input
                            id="amount_paid"
                            type="number"
                            step="0.01"
                            name="amount_paid"
                            x-model="amount"
                            class="block w-full rounded-inputs border border-hairline bg-paper px-4 py-3 pe-10 text-body text-ink focus:border-ink focus:outline-none focus:ring-0"
                        />
                        <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-slate">€</span>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancelar') }}</x-secondary-button>
                        <x-primary-button class="ms-3">{{ __('Marcar presentado') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endif
</x-app-layout>
