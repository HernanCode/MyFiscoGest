<x-app-layout>
    <x-slot name="title">{{ __('Panel') }}</x-slot>

    @php
        $euros = fn ($value) => number_format((float) $value, 2, ',', '.').' €';

        // Saludo según la hora.
        $hour = (int) now()->format('G');
        $greeting = $hour < 6 ? __('Buenas noches') : ($hour < 12 ? __('Buenos días') : ($hour < 21 ? __('Buenas tardes') : __('Buenas noches')));
        $firstName = collect(explode(' ', trim(Auth::user()->name)))->first() ?? '';

        // Frase destacada calculada con datos reales.
        $subtitle = '';
        if (! $selectedYear) {
            $subtitle = __('Aún no hay datos fiscales para mostrar.');
        } elseif ($nextFiling) {
            $daysLeft = (int) round($nextFiling->due_date->startOfDay()->diffInDays(now()->startOfDay(), true));
            if ($daysLeft === 0) {
                $subtitle = __('El modelo :model del T:quarter vence hoy.', ['model' => $nextFiling->model, 'quarter' => $nextFiling->quarter]);
            } elseif ($daysLeft === 1) {
                $subtitle = __('El modelo :model del T:quarter vence en 1 día.', ['model' => $nextFiling->model, 'quarter' => $nextFiling->quarter]);
            } else {
                $subtitle = __('El modelo :model del T:quarter vence en :days días.', ['model' => $nextFiling->model, 'quarter' => $nextFiling->quarter, 'days' => $daysLeft]);
            }
        } elseif ($overdueFilings->isNotEmpty()) {
            $subtitle = __('Tienes :count presentaciones vencidas pendientes de marcar.', ['count' => $overdueFilings->count()]);
        } elseif ($pendingInvoices->isNotEmpty()) {
            $subtitle = __('Tienes :count facturas pendientes de cobro.', ['count' => $pendingInvoices->count()]);
        } else {
            $subtitle = __('Todo al día: sin vencimientos pendientes.');
        }

        // Meses del trimestre seleccionado (barras en color pleno).
        $qMonths = range(($quarter - 1) * 3 + 1, $quarter * 3);

        // Barras mensuales: solo el trimestre seleccionado, escala local.
        $qIncome = array_map(fn ($m) => $monthly['income'][$m - 1], $qMonths);
        $qExpense = array_map(fn ($m) => $monthly['expense'][$m - 1], $qMonths);
        $barMax = max(max($qIncome), max($qExpense), 1);
        $barMaxH = 160;

        // Resultado acumulado: acumulado del año HASTA el trimestre seleccionado,
        // coherente con la cifra destacada ($summary['net_income']) y su desglose
        // ($summary['income_base'] / ['expense_base']). El sparkline dibuja solo los
        // meses transcurridos del año, no los 12.
        $ytdMonths = $quarter * 3;
        $ytdNet = array_slice($monthly['net'], 0, $ytdMonths);
        $prevLevel = $previousSummary['net_income'] ?? null;
        $netMin = min(0, min($ytdNet), (float) ($prevLevel ?? 0));
        $netMax = max(0, max($ytdNet), (float) ($prevLevel ?? 0));
        $netRange = max($netMax - $netMin, 1);
        $sparkW = 300;
        $sparkH = 92;
        $sparkY = fn ($v) => $sparkH - 8 - (($v - $netMin) / $netRange * ($sparkH - 20));
        $step = $sparkW / max(1, count($ytdNet) - 1);
        $netPoints = collect($ytdNet)->map(fn ($v, $i) => ['x' => round($i * $step, 1), 'y' => round($sparkY($v), 1)])->values();

        // Curva suave tipo spline cúbico con tensión baja (solo presentación).
        $smoothPath = function ($pts, $tension = 0.15) {
            $n = count($pts);
            if ($n < 2) return '';
            $d = "M {$pts[0]['x']},{$pts[0]['y']}";
            for ($i = 0; $i < $n - 1; $i++) {
                $p0 = $pts[max(0, $i - 1)];
                $p1 = $pts[$i];
                $p2 = $pts[$i + 1];
                $p3 = $pts[min($n - 1, $i + 2)];
                $t1x = ($p2['x'] - $p0['x']) * $tension;
                $t1y = ($p2['y'] - $p0['y']) * $tension;
                $t2x = ($p3['x'] - $p1['x']) * $tension;
                $t2y = ($p3['y'] - $p1['y']) * $tension;
                $d .= ' C '.round($p1['x'] + $t1x, 1).','.round($p1['y'] + $t1y, 1).' '.round($p2['x'] - $t2x, 1).','.round($p2['y'] - $t2y, 1).' '.$p2['x'].','.$p2['y'];
            }
            return $d;
        };
        $netPath = $smoothPath($netPoints);
        $netAreaPath = $netPath." L {$sparkW},{$sparkH} L 0,{$sparkH} Z";

        // Anillo de progreso del próximo vencimiento (mín. 120px, trazo sienna).
        $ringDays = null;
        if ($nextFiling) {
            $ringDays = (int) round($nextFiling->due_date->startOfDay()->diffInDays(now()->startOfDay(), true));
        }
        $ringTotal = 90; // ventana de referencia del trimestre
        $ringPct = $ringDays !== null ? min(1, max(0, $ringDays / $ringTotal)) : 0;
        $ringR = 54;
        $ringC = 2 * pi() * $ringR;

        // Estados de los modelos 303 / 130.
        $chip303 = $summary['modelo_303'] > 0 ? __('A pagar') : ($summary['modelo_303'] < 0 ? __('A compensar') : __('Sin cuota'));
        $chip130 = $summary['modelo_130'] > 0 ? __('A ingresar') : ($summary['modelo_130'] < 0 ? __('A compensar') : __('Sin cuota'));
    @endphp

    {{-- Saludo + acciones de creación, alineadas por su base --}}
    <div class="mb-8 flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div class="min-w-0 flex-1 basis-72">
            <h1 class="font-serif text-4xl font-normal tracking-tight text-ink lg:text-[44px]">
                {{ $greeting }}, {{ $firstName }}
            </h1>
            <p class="mt-3 text-body text-slate">{{ $subtitle }}</p>
        </div>

        <div class="flex shrink-0 items-center gap-2.5">
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
                class="inline-flex h-10 items-center gap-2 rounded-buttons border border-ink bg-paper px-5 text-[16px] text-ink transition-colors hover:bg-mist"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21l2.5-11M9 21l5-10M15 21l-2-11M19 21l-3-9M7 10a3 3 0 01-3-3M13 11a3 3 0 01-3-3M20 12l-1-6M11 5l.5 3" />
                </svg>
                {{ __('Nuevo gasto') }}
            </a>
        </div>
    </div>

    @if ($selectedYear)
        @php
            // Checklist "ponte al día": solo lo que requiere una acción.
            $todo = [];
            if ($overdueFilings->isNotEmpty()) {
                $todo[] = [
                    'title' => __(':count presentaciones vencidas sin marcar', ['count' => $overdueFilings->count()]),
                    'detail' => __('Los vencimientos del :models ya han pasado y siguen pendientes.', ['models' => $overdueFilings->pluck('model')->unique()->implode(' y ')]),
                    'url' => route('filings.index'),
                    'action' => __('Ir a Presentaciones'),
                ];
            }
            if ($overdueCollectionsCount > 0) {
                $todo[] = [
                    'title' => __(':count cobros vencidos pendientes', ['count' => $overdueCollectionsCount]),
                    'detail' => __('Facturas de ingreso con fecha pasada que siguen sin cobrar.'),
                    'url' => route('movements.index', ['type' => 'income', 'payment_status' => 'pending']),
                    'action' => __('Ver cobros'),
                ];
            }
            if ($movementsWithoutInvoiceCount > 0) {
                $todo[] = [
                    'title' => __(':count movimientos sin factura adjunta', ['count' => $movementsWithoutInvoiceCount]),
                    'detail' => __('Adjunta el PDF o la imagen para tener el soporte completo.'),
                    'url' => route('movements.index'),
                    'action' => __('Ver movimientos'),
                ];
            }
            if ($expensesWithoutCategoryCount > 0) {
                $todo[] = [
                    'title' => __(':count gastos sin categoría', ['count' => $expensesWithoutCategoryCount]),
                    'detail' => __('Categorizarlos mejora tus estimaciones y el desglose por categoría.'),
                    'url' => route('movements.index', ['type' => 'expense']),
                    'action' => __('Ver gastos'),
                ];
            }
        @endphp

        {{-- Rejilla de 12 columnas, gap uniforme de 16px --}}
        <div class="grid grid-cols-12 gap-4">

            {{-- A) Ingresos del trimestre (tarjeta principal; única excepción azul) --}}
            <x-card variant="tint" class="col-span-12 flex h-full flex-col p-6 lg:col-span-8 lg:p-8">
                <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
                    <div class="flex min-w-0 flex-wrap items-start gap-x-8 gap-y-4">
                        {{-- Ingresos: cifra protagonista del trimestre --}}
                        <div class="min-w-0">
                            <p class="text-sm text-navy/80">{{ __('Ingresos') }}</p>
                            <p class="mt-2 font-sans text-[40px] font-w450 leading-none tracking-tight text-ink tabular-nums lg:text-[44px]">
                                {{ $euros($quarterIncome) }}
                            </p>
                            @if ($incomeDelta !== null)
                                <p class="mt-3 flex items-center gap-1.5 text-sm font-w480 text-navy">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        @if ($incomeDelta < 0)
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7-7-7M12 21V3" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7 7 7M12 3v18" />
                                        @endif
                                    </svg>
                                    {{ number_format(abs($incomeDelta), 1, ',', '.') }} % vs {{ $previousLabel }}
                                </p>
                            @else
                                <p class="mt-3 text-[13px] text-navy/70">{{ __('Sin datos del trimestre anterior') }}</p>
                            @endif
                        </div>

                        {{-- Gastos del mismo trimestre --}}
                        <div>
                            <p class="text-sm text-navy/80">{{ __('Gastos') }}</p>
                            <p class="mt-2 font-sans text-[22px] font-w450 leading-tight tracking-tight text-ink tabular-nums">
                                {{ $euros($quarterExpense) }}
                            </p>
                        </div>

                        {{-- Margen del trimestre --}}
                        <div>
                            <p class="text-sm text-navy/80">{{ __('Margen') }}</p>
                            <p class="mt-2 font-sans text-[22px] font-w450 leading-tight tracking-tight tabular-nums {{ $quarterMargin < 0 ? 'text-ink' : 'text-navy' }}">
                                {{ $euros($quarterMargin) }}
                            </p>
                            @if ($quarterMarginPct !== null)
                                <p class="mt-1 text-[13px] text-navy/70">{{ number_format($quarterMarginPct, 1, ',', '.') }} % {{ __('sobre ingresos') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Segmented control T1–T4 --}}
                    <div class="flex shrink-0 items-center gap-1 rounded-buttons bg-paper/80 p-1">
                        @foreach ([1, 2, 3, 4] as $q)
                            <a
                                href="{{ route('dashboard', ['year' => $selectedYear->year, 'quarter' => $q]) }}"
                                class="rounded-buttons px-3.5 py-1.5 text-sm transition-colors {{ $q === $quarter ? 'bg-navy font-w480 text-paper' : 'text-navy hover:bg-paper' }}"
                            >T{{ $q }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Barras mensuales + Por categoría --}}
                <div class="mt-8 flex flex-1 flex-col gap-8 xl:flex-row xl:items-stretch">
                    <div class="flex min-w-0 flex-1 flex-col">
                        <div class="flex min-h-[160px] flex-1 items-end gap-4" role="img" aria-label="{{ __('Ingresos y gastos por mes') }}">
                            @foreach ($qMonths as $m)
                                @php
                                    $i = $m - 1;
                                    $hasIncome = $monthly['income'][$i] > 0;
                                    $hasExpense = $monthly['expense'][$i] > 0;
                                    $incomeH = $hasIncome ? max(6, $monthly['income'][$i] / $barMax * $barMaxH) : 6;
                                    $expenseH = $hasExpense ? max(6, $monthly['expense'][$i] / $barMax * $barMaxH) : 6;
                                @endphp
                                <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                    <div class="flex h-[160px] items-end justify-center gap-1">
                                        <div
                                            class="w-12 rounded-t-[8px] bg-navy transition-all"
                                            style="height: {{ (int) round($incomeH) }}px; opacity: {{ $hasIncome ? 1 : 0.35 }};"
                                            title="{{ __('Ingresos') }}: {{ $euros($monthly['income'][$i]) }}"
                                        ></div>
                                        <div
                                            class="w-12 rounded-t-[8px] bg-bar transition-all"
                                            style="height: {{ (int) round($expenseH) }}px; opacity: {{ $hasExpense ? 1 : 0.5 }};"
                                            title="{{ __('Gastos') }}: {{ $euros($monthly['expense'][$i]) }}"
                                        ></div>
                                    </div>
                                    <span class="max-w-full truncate text-[11px] text-ash">{{ $monthly['labels'][$i] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Por categoría: lista con separadores hairline --}}
                    <div class="w-full shrink-0 xl:w-64">
                        <p class="text-sm text-navy/80">{{ __('Por categoría') }}</p>
                        <div class="mt-2 divide-y divide-navy/10">
                            @forelse ($categoryRows as $row)
                                <div class="flex items-baseline justify-between gap-2 py-2.5 text-sm">
                                    <span class="truncate text-ink">{{ $row['name'] }}</span>
                                    <span class="shrink-0 tabular-nums font-w450 text-navy">{{ $euros($row['total']) }}</span>
                                </div>
                            @empty
                                <p class="py-2.5 text-[13px] text-navy/70">{{ __('Sin ingresos este trimestre.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- B) Próximo vencimiento (única tarjeta durazno) --}}
            <x-card variant="accent" class="col-span-12 flex h-full flex-col p-6 lg:col-span-4 lg:p-8">
                <p class="text-sm text-sienna/80">{{ __('Próximo vencimiento') }}</p>

                @if ($nextFiling && $nextPayment)
                    <div class="relative mx-auto mt-10 h-[128px] w-[128px] shrink-0">
                        <svg viewBox="0 0 128 128" class="h-full w-full -rotate-90 text-sienna" title="{{ $ringDays }} {{ __('días para el vencimiento') }}">
                            <circle cx="64" cy="64" r="{{ $ringR }}" fill="none" stroke="currentColor" opacity="0.08" stroke-width="16" />
                            <circle
                                cx="64" cy="64" r="{{ $ringR }}" fill="none" stroke="currentColor" stroke-width="16"
                                stroke-linecap="round"
                                stroke-dasharray="{{ round($ringPct * $ringC, 1) }} {{ round($ringC, 1) }}"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl font-w480 leading-none tracking-tight text-sienna tabular-nums">{{ $ringDays }}</span>
                            <span class="mt-1 text-xs text-sienna/70">{{ __('días') }}</span>
                        </div>
                    </div>

                    <div class="mt-auto pt-10">
                        <p class="text-xs text-sienna/70">{{ __('Importe estimado') }}</p>
                        <p class="mt-1 font-sans text-[28px] font-w450 leading-tight tracking-tight text-sienna tabular-nums">
                            {{ $euros($nextPayment['pay_total']) }}
                        </p>
                        <p class="mt-1 text-[14px] text-sienna/80">{{ $nextPayment['due_date']->format('d/m/Y') }}</p>

                        @if ($nextPayment['items']->count() > 1)
                            <dl class="mt-4 space-y-1.5 border-t border-sienna/20 pt-3 text-[14px]">
                                @foreach ($nextPayment['items'] as $item)
                                    <div class="flex items-baseline justify-between gap-3">
                                        <dt class="text-sienna/80">{{ __('Modelo :model · T:quarter', ['model' => $item['model'], 'quarter' => $item['quarter']]) }}</dt>
                                        <dd class="tabular-nums text-sienna">
                                            @if ($item['estimate'] >= 0)
                                                {{ $euros($item['estimate']) }}
                                            @else
                                                <span class="text-sienna/70">{{ __('A compensar') }}</span> {{ $euros(abs($item['estimate'])) }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="mt-1 text-[14px] text-sienna/80">
                                {{ __('Modelo :model · T:quarter', ['model' => $nextPayment['items']->first()['model'], 'quarter' => $nextPayment['items']->first()['quarter']]) }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="mt-10">
                        <p class="text-body font-medium text-sienna">{{ __('Sin vencimientos pendientes.') }}</p>
                        <p class="mt-2 text-sm text-sienna/80">{{ __('Todas las presentaciones están al día.') }}</p>
                    </div>
                @endif
            </x-card>

            {{-- C) Resultado acumulado (artefacto flotante blanco con gráfico) --}}
            <x-card variant="white" class="col-span-12 flex h-full flex-col p-6 shadow-subtle-2 lg:col-span-4 lg:p-7">
                <p class="text-sm text-ash">{{ __('Resultado acumulado a T:quarter', ['quarter' => $quarter]) }}</p>
                <p class="mt-3 font-sans text-[28px] font-w450 leading-tight tracking-tight text-ink tabular-nums">
                    {{ $euros($summary['net_income']) }}
                </p>

                <dl class="mt-5 space-y-2 text-[14px]">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate">{{ __('Ingresos acumulados') }}</dt>
                        <dd class="tabular-nums text-ink">{{ $euros($summary['income_base']) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate">{{ __('Gastos deducibles') }}</dt>
                        <dd class="tabular-nums text-ink">{{ $euros($summary['expense_base']) }}</dd>
                    </div>
                </dl>

                <div class="mt-auto pt-6">
                    <svg viewBox="0 0 300 92" class="h-20 w-full overflow-visible text-sienna" preserveAspectRatio="none" role="img" aria-label="{{ __('Evolución del resultado acumulado') }}">
                        <defs>
                            <linearGradient id="net-area" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#5d2a1a" stop-opacity="0.12" />
                                <stop offset="100%" stop-color="#5d2a1a" stop-opacity="0.02" />
                            </linearGradient>
                        </defs>
                        @if ($prevLevel !== null)
                            <line
                                x1="0" y1="{{ round($sparkY($prevLevel), 1) }}" x2="300" y2="{{ round($sparkY($prevLevel), 1) }}"
                                stroke="currentColor" stroke-width="1.5" stroke-dasharray="5 5" opacity="0.45"
                            />
                        @endif
                        <path
                            d="{{ $netAreaPath }}"
                            fill="url(#net-area)"
                        />
                        <path
                            d="{{ $netPath }}"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        >
                            <title>{{ __('Resultado neto acumulado por mes') }}</title>
                        </path>
                    </svg>

                    @if ($prevLevel !== null)
                        <p class="mt-2 flex items-center gap-1.5 text-[11px] text-ash">
                            <span class="inline-block w-4 border-t border-dashed border-sienna opacity-70"></span>
                            {{ __('Nivel :label', ['label' => $previousLabel]) }}
                        </p>
                    @endif
                </div>
            </x-card>

            {{-- D) Modelos estimados (Neutral Card #f2f2f3, sin sombra) --}}
            <x-metric-tile
                class="col-span-12 lg:col-span-4"
                :title="__('303 · IVA estimado')"
                :value="$euros($summary['modelo_303'])"
                :chip="$chip303"
                :rows="[
                    ['label' => __('Repercutido'), 'value' => $euros($summary['vat_charged'])],
                    ['label' => __('Soportado'), 'value' => $euros($summary['vat_paid'])],
                ]"
            />

            <x-metric-tile
                class="col-span-12 lg:col-span-4"
                :title="__('130 · IRPF estimado')"
                :value="$euros($summary['modelo_130'])"
                :chip="$chip130"
                :rows="[
                    ['label' => __('Base 20 %'), 'value' => $euros($summary['net_income'] * 0.2)],
                    ['label' => __('Retenciones'), 'value' => $euros($retentions)],
                    ['label' => __('130 ya pagados'), 'value' => $euros($summary['previous_130'])],
                ]"
            />

            {{-- Últimos movimientos --}}
            <x-card variant="white" class="col-span-12 flex h-full flex-col p-6 lg:col-span-4 lg:p-7">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm text-ash">{{ __('Últimos movimientos') }}</p>
                    <a href="{{ route('movements.index') }}" class="shrink-0 text-[13px] text-slate hover:text-ink">{{ __('Ver todos') }} →</a>
                </div>
                <div class="mt-2 divide-y divide-hairline">
                    @forelse ($recentMovements as $movement)
                        <a href="{{ route('movements.edit', $movement) }}" class="flex items-baseline justify-between gap-3 py-3 text-sm hover:bg-fog">
                            <span class="min-w-0">
                                <span class="block truncate text-ink">{{ $movement->concept }}</span>
                                <span class="block text-[13px] text-ash">{{ $movement->date->format('d/m/Y') }}</span>
                            </span>
                            <span class="shrink-0 tabular-nums text-ink">{{ $euros($movement->total_amount) }}</span>
                        </a>
                    @empty
                        <p class="py-3 text-[13px] text-ash">{{ __('Sin movimientos este año.') }}</p>
                    @endforelse
                </div>
            </x-card>

            {{-- E) Caja pendiente: lo que entra y lo que sale --}}
            <x-card variant="white" class="col-span-12 flex h-full flex-col p-6 lg:col-span-8 lg:p-7">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-ash">{{ __('Caja pendiente') }}</p>
                    <a href="{{ route('movements.index', ['payment_status' => 'pending']) }}" class="text-[13px] text-slate hover:text-ink">
                        {{ __('Ver pendientes') }} →
                    </a>
                </div>

                {{-- Resumen: por cobrar, por pagar y neto --}}
                <dl class="mt-5 grid grid-cols-3 gap-4 border-b border-hairline pb-5">
                    <div>
                        <dt class="text-[13px] text-slate">{{ __('Por cobrar') }}</dt>
                        <dd class="mt-1 font-sans text-[22px] font-w450 leading-tight tracking-tight tabular-nums text-ink">{{ $euros($pendingReceivable) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] text-slate">{{ __('Por pagar') }}</dt>
                        <dd class="mt-1 font-sans text-[22px] font-w450 leading-tight tracking-tight tabular-nums text-ink">{{ $euros($pendingPayable) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] text-slate">{{ __('Caja neta') }}</dt>
                        <dd class="mt-1 font-sans text-[22px] font-w450 leading-tight tracking-tight tabular-nums {{ $cashNet < 0 ? 'text-ink' : 'text-navy' }}">{{ $euros($cashNet) }}</dd>
                    </div>
                </dl>

                {{-- Movimientos pendientes (cobros y pagos) --}}
                <div class="mt-1 divide-y divide-hairline">
                    @forelse ($pendingCashItems as $item)
                        @php $isIncome = $item->type === 'income'; @endphp
                        <a href="{{ route('movements.edit', $item) }}" class="flex items-center justify-between gap-4 py-3">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-buttons bg-mist text-ink">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        @if ($isIncome)
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M12 4v16" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 13l-7 7-7-7M12 20V4" />
                                        @endif
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-w480 text-ink">{{ $item->counterparty ?: $item->concept }}</span>
                                    <span class="block truncate text-[13px] text-slate">{{ $item->concept }} · {{ $item->date->format('d/m/Y') }}</span>
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-4">
                                <span class="inline-flex items-center gap-1.5 rounded-buttons px-2.5 py-1 text-[11px] text-ink {{ $isIncome ? 'bg-mist' : 'border border-ink' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isIncome ? 'bg-ash/70' : 'bg-ink/70' }}"></span>
                                    {{ $isIncome ? __('Por cobrar') : __('Por pagar') }}
                                </span>
                                <span class="tabular-nums text-ink">{{ $euros($item->total_amount) }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="py-3 text-[13px] text-ash">{{ __('Sin cobros ni pagos pendientes.') }}</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Ponte al día: checklist al pie, solo si hay algo que hacer --}}
        @if (! empty($todo))
            <x-card variant="white" class="mt-4 p-6">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0 text-ink" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <p class="text-sm font-w480 text-ink">{{ __('Ponte al día') }}</p>
                </div>
                <div class="mt-2 divide-y divide-hairline">
                    @foreach ($todo as $task)
                        <a href="{{ $task['url'] }}" class="group flex flex-wrap items-center justify-between gap-3 py-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-w480 text-ink">{{ $task['title'] }}</span>
                                <span class="mt-0.5 block text-[13px] text-slate">{{ $task['detail'] }}</span>
                            </span>
                            <span class="shrink-0 text-[13px] text-slate transition-colors group-hover:text-ink">{{ $task['action'] }} →</span>
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- Nota al pie --}}
        <p class="mt-6 text-[13px] text-slate">Estimaciones orientativas. Verifica antes de presentar.</p>
    @else
        {{-- Estado vacío --}}
        <x-card variant="neutral" class="mt-8 p-10 text-center">
            <p class="text-body-lg font-medium text-ink">{{ __('Aún no hay datos para mostrar.') }}</p>
            <p class="mt-2 text-body text-slate">{{ __('Crea un año fiscal añadiendo tu primer movimiento.') }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('movements.create', ['tipo' => 'ingreso']) }}">
                    <x-primary-button type="button">{{ __('Nuevo ingreso') }}</x-primary-button>
                </a>
                <a href="{{ route('movements.create', ['tipo' => 'gasto']) }}">
                    <x-secondary-button type="button">{{ __('Nuevo gasto') }}</x-secondary-button>
                </a>
            </div>
        </x-card>
    @endif
</x-app-layout>