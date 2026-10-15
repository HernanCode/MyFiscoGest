@props(['title' => 'Fiscal'])

<header class="sticky top-0 z-20 -mx-5 flex h-14 items-center gap-3 bg-paper/90 px-5 backdrop-blur lg:mx-0 lg:px-0">
    {{-- Botón de menú (móvil) --}}
    <button
        type="button"
        @click="$dispatch('open-sidebar')"
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-buttons text-mid hover:bg-mist hover:text-ink lg:hidden"
        aria-label="{{ __('Abrir menú') }}"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Logo (móvil) --}}
    <a href="{{ route('dashboard') }}" class="font-serif text-[20px] tracking-tight text-ink lg:hidden">
        Fiscal<span class="italic">.</span>
    </a>

    {{-- Plegar / desplegar la sidebar (escritorio) --}}
    <button
        type="button"
        @click="sidebarCollapsed = ! sidebarCollapsed; try { localStorage.setItem('sidebar.collapsed', sidebarCollapsed ? '1' : '0') } catch (e) {}"
        class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-buttons text-mid hover:bg-mist hover:text-ink lg:flex"
        :aria-label="sidebarCollapsed ? '{{ __('Desplegar menú') }}' : '{{ __('Plegar menú') }}'"
    >
        <svg class="h-4 w-4 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
        </svg>
    </button>

    {{-- Título de la página (escritorio) --}}
    <h1 class="hidden truncate text-[15px] font-medium text-ink lg:block">{{ $title }}</h1>
</header>
