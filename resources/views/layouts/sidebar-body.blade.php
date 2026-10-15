@php
    $user = Auth::user();
    $name = trim($user?->name ?? '');
    $initials = collect(explode(' ', $name))->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    $selectedYear = $selectedYear ?? null;
    $fiscalYears = $fiscalYears ?? collect();
    $quarters = $quarters ?? [];
    $topClients = $topClients ?? collect();
    $dashboardUrl = route('dashboard');
    $movementsBase = route('movements.index');

    $quarterMonths = [
        1 => 'Ene–Mar',
        2 => 'Abr–Jun',
        3 => 'Jul–Sep',
        4 => 'Oct–Dic',
    ];

    $items = [
        ['label' => __('Panel'), 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'icon' => 'home'],
        ['label' => __('Movimientos'), 'route' => route('movements.index'), 'active' => request()->routeIs('movements.*'), 'icon' => 'list'],
        ['label' => __('Presentaciones'), 'route' => route('filings.index'), 'active' => request()->routeIs('filings.*'), 'icon' => 'doc'],
    ];

    if ($user?->isAdmin()) {
        $items[] = ['label' => __('Usuarios'), 'route' => route('users.index'), 'active' => request()->routeIs('users.*', 'invitations.*'), 'icon' => 'users'];
    }

    // Clases compartidas de los items de navegación.
    $itemBase = 'group relative flex items-center gap-3 rounded-[8px] px-[10px] py-2 text-[15px] transition-colors';
@endphp

<div class="flex h-full flex-col">
    {{-- Cabecera: logo, plegar, selector de año y búsqueda (visual) --}}
    <div class="px-2.5 pt-4 pb-1">
        <a
            href="{{ $dashboardUrl }}"
            class="flex min-w-0 items-center"
            :class="sidebarCollapsed ? 'lg:hidden' : ''"
        >
            <span class="font-serif text-[22px] tracking-tight text-ink">{{ config('app.name', 'MyFiscoGest') }}<span class="italic">.</span></span>
        </a>

        @if ($fiscalYears->isNotEmpty())
            <div class="mt-3" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                <select
                    onchange="window.location = '{{ $dashboardUrl }}?year=' + this.value"
                    class="w-full rounded-inputs border border-hairline bg-paper py-2 pl-2.5 pr-2 text-[15px] text-ink focus:border-ink focus:outline-none focus:ring-0"
                    aria-label="{{ __('Año fiscal') }}"
                >
                    @foreach ($fiscalYears as $year)
                        <option value="{{ $year->year }}" @selected($selectedYear && $selectedYear->year === $year->year)>{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Búsqueda: solo visual, sin funcionalidad --}}
        <div class="mt-2 flex items-center gap-3 rounded-[8px] px-[10px] py-2 text-[15px] text-smoke" :class="sidebarCollapsed ? 'lg:hidden' : ''" aria-hidden="true">
            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
            </svg>
            <span class="truncate">{{ __('Buscar') }}</span>
        </div>
    </div>

    {{-- Navegación --}}
    <nav x-data="{ tip: null, tipTop: 0, tipLeft: 0 }" class="flex-1 overflow-x-hidden overflow-y-auto px-2.5 pb-4">
        {{-- Grupo principal --}}
        <p class="px-2 pt-5 pb-1 text-xs text-ash" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ __('Principal') }}</p>
        <div class="space-y-0.5">
            @foreach ($items as $item)
                <a
                    href="{{ $item['route'] }}"
                    class="{{ $itemBase }} {{ $item['active'] ? 'bg-mist font-w480 text-ink' : 'text-slate hover:bg-mist/60 hover:text-ink' }}"
                    :class="sidebarCollapsed ? 'lg:justify-center' : ''"
                    @click="$dispatch('close-sidebar')"
                    @mouseenter="if (sidebarCollapsed) { const r = $event.currentTarget.getBoundingClientRect(); tip = @js($item['label']); tipTop = r.top + r.height / 2; tipLeft = r.right + 12 }"
                    @mouseleave="tip = null"
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-[7px] transition-colors {{ $item['active'] ? 'bg-paper text-ink' : 'bg-mist text-mid' }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            @if ($item['icon'] === 'home')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10" />
                            @elseif ($item['icon'] === 'list')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                            @elseif ($item['icon'] === 'users')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-3-3.87M9 20H4v-1a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-4a3 3 0 11-3-3" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h6M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2zM14 3v4a1 1 0 001 1h4" />
                            @endif
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1 truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Grupo trimestres --}}
        @if ($quarters)
            <p class="px-2 pt-5 pb-1 text-xs text-ash" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ __('Trimestres') }}</p>
            <div class="space-y-0.5">
                @foreach ($quarters as $q)
                    @php
                        $toneClass = match ($q['tone']) {
                            'done' => 'bg-success',
                            'current' => 'bg-warning',
                            default => 'bg-ash',
                        };
                    @endphp
                    <a
                        href="{{ $movementsBase }}?year={{ $selectedYear->id }}&quarter={{ $q['quarter'] }}"
                        class="{{ $itemBase }} text-slate hover:bg-mist/60 hover:text-ink"
                        :class="sidebarCollapsed ? 'lg:justify-center' : ''"
                        @click="$dispatch('close-sidebar')"
                        @mouseenter="if (sidebarCollapsed) { const r = $event.currentTarget.getBoundingClientRect(); tip = @js('T'.$q['quarter'].' · '.$quarterMonths[$q['quarter']]); tipTop = r.top + r.height / 2; tipLeft = r.right + 12 }"
                        @mouseleave="tip = null"
                    >
                        {{-- Icono: solo cuando está plegada --}}
                        <span class="hidden h-6 w-6 shrink-0 items-center justify-center rounded-[7px] bg-mist text-[12px] font-medium text-ink" :class="sidebarCollapsed ? 'lg:flex' : 'lg:hidden'">T{{ $q['quarter'] }}</span>
                        <span class="min-w-0 flex-1 truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">T{{ $q['quarter'] }} · {{ $quarterMonths[$q['quarter']] }}</span>
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $toneClass }}" :class="sidebarCollapsed ? 'lg:hidden' : ''" title="{{ $q['status'] }}"></span>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Grupo clientes --}}
        @if ($topClients->isNotEmpty())
            <p class="px-2 pt-5 pb-1 text-xs text-ash" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ __('Clientes') }}</p>
            <div class="space-y-0.5">
                @foreach ($topClients as $client)
                    <div
                        class="{{ $itemBase }} text-slate"
                        :class="sidebarCollapsed ? 'lg:justify-center' : ''"
                        @mouseenter="if (sidebarCollapsed) { const r = $event.currentTarget.getBoundingClientRect(); tip = @js($client->counterparty); tipTop = r.top + r.height / 2; tipLeft = r.right + 12 }"
                        @mouseleave="tip = null"
                    >
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-navy-tint text-[11px] font-medium text-navy">
                            {{ mb_strtoupper(mb_substr($client->counterparty, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1 truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $client->counterparty }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Tooltip único del modo plegado. Es `fixed`, así que no crea scroll. --}}
        <div
            x-show="tip"
            x-cloak
            class="pointer-events-none fixed z-50 -translate-y-1/2 whitespace-nowrap rounded-[6px] bg-ink px-2 py-1.5 text-xs text-paper shadow-subtle-2"
            :style="`top: ${tipTop}px; left: ${tipLeft}px;`"
            x-text="tip"
        ></div>
    </nav>

    {{-- Pie: usuario --}}
    <div class="border-t border-hairline px-2.5 py-3">
        <div x-data="{ open: false }" class="relative">
            <button
                type="button"
                @click="open = ! open"
                class="flex w-full items-center gap-3 rounded-[8px] px-[10px] py-2 text-[15px] text-ink transition-colors hover:bg-mist/60"
                :class="sidebarCollapsed ? 'lg:justify-center' : ''"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-mist text-[12px] font-medium text-ink">{{ $initials }}</span>
                <span class="min-w-0 flex-1 truncate text-left" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $name }}</span>
                <svg class="h-4 w-4 text-mid" :class="sidebarCollapsed ? 'lg:hidden' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click.outside="open = false"
                class="absolute inset-x-0 bottom-full z-20 mb-2 rounded-small border border-hairline bg-paper p-1 shadow-subtle-2"
            >
                <a href="{{ route('profile.edit') }}" class="block rounded-small px-3 py-2 text-sm text-mid hover:bg-mist hover:text-ink">{{ __('Perfil') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full rounded-small px-3 py-2 text-left text-sm text-mid hover:bg-mist hover:text-ink">{{ __('Cerrar sesión') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
