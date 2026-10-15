@php
use App\Models\FiscalYear;

// Años disponibles del usuario autenticado (más reciente primero).
$fiscalYears = FiscalYear::forUser(Auth::id())->orderByDesc('year')->get();

// Año seleccionado: el del query string si coincide (por valor de año o por id),
// si no, el año en curso si existe, y si no, el más reciente.
$requestYear = request()->integer('year');
$selectedYear = $fiscalYears->firstWhere('year', $requestYear)
    ?? $fiscalYears->firstWhere('id', $requestYear)
    ?? $fiscalYears->firstWhere('year', now()->year)
    ?? $fiscalYears->first();

// Estado de los 4 trimestres del año seleccionado ("Presentado" / "Pendiente").
// El trimestre en curso solo existe si el año seleccionado es el actual.
$currentQuarter = ($selectedYear && $selectedYear->year === now()->year)
    ? (int) ceil(now()->month / 3)
    : null;

$quarters = [];
if ($selectedYear) {
    $selectedYear->loadMissing('filings');
    foreach ([1, 2, 3, 4] as $quarter) {
        $pending = $selectedYear->filings->where('quarter', $quarter)
            ->where('status', 'pending')->isNotEmpty();
        $quarters[] = [
            'quarter' => $quarter,
            'status' => $pending ? __('Pendiente') : __('Presentado'),
            'tone' => $pending ? ($currentQuarter === $quarter ? 'current' : 'pending') : 'done',
        ];
    }
}

// Los 3 clientes con más ingresos del año seleccionado (sin favoritos).
$topClients = collect();
if ($selectedYear) {
    $topClients = $selectedYear->movements()->income()
        ->whereNotNull('counterparty')
        ->where('counterparty', '!=', '')
        ->selectRaw('counterparty, SUM(base_amount) as total')
        ->groupBy('counterparty')
        ->orderByDesc('total')
        ->limit(3)
        ->get();
}
@endphp

{{-- Sidebar de escritorio (a partir de 1024px). Se pliega a 64px y recuerda el estado. --}}
<aside
    x-cloak
    class="fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-hairline bg-fog transition-[width] duration-200 lg:flex"
    :class="sidebarCollapsed ? 'lg:w-16' : 'lg:w-[232px]'"
>
    @include('layouts.sidebar-body', [
        'fiscalYears' => $fiscalYears,
        'selectedYear' => $selectedYear,
        'quarters' => $quarters,
        'topClients' => $topClients,
    ])
</aside>

{{-- Sidebar móvil: cajón con velo (Alpine). Se cierra al pulsar fuera, con Escape o al elegir. --}}
<div
    x-data="{ open: false }"
    @open-sidebar.window="open = true"
    @close-sidebar.window="open = false"
    @keydown.escape.window="open = false"
    class="lg:hidden"
>
    {{-- Velo --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 z-40 bg-ink/40 backdrop-blur-[2px]"
        style="display: none;"
    ></div>

    {{-- Cajón --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 flex w-[280px] max-w-[85vw] flex-col bg-fog shadow-subtle-3"
        style="display: none;"
    >
        <div class="flex justify-end px-3 pt-3">
            <button type="button" @click="open = false" class="flex h-8 w-8 items-center justify-center rounded-buttons text-mid hover:bg-mist hover:text-ink" aria-label="{{ __('Cerrar menú') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @include('layouts.sidebar-body', [
            'fiscalYears' => $fiscalYears,
            'selectedYear' => $selectedYear,
            'quarters' => $quarters,
            'topClients' => $topClients,
        ])
    </div>
</div>
