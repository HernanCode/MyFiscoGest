<x-app-layout>
    <x-slot name="title">{{ __('Perfil') }}</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="font-serif text-4xl font-normal tracking-tight text-ink">
                {{ __('Perfil') }}
            </h2>
            <p class="mt-3 text-body text-slate">{{ __('Tu cuenta y tus datos de acceso.') }}</p>
        </div>
    </x-slot>

    @php
        $displayName = trim((string) $user->name);
        $initials = collect(explode(' ', $displayName))->filter()->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    @endphp

    <div class="grid grid-cols-12 gap-6">
        {{-- Ajustes: información y contraseña --}}
        <div class="col-span-12 space-y-4 lg:col-span-8">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')

            <x-form-section
                :title="__('Tus datos')"
                :description="__('Descarga una copia de seguridad completa: movimientos, categorías, recurrentes, presentaciones y facturas adjuntas.')"
            >
                <a
                    href="{{ route('export.all') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-buttons border border-ink bg-paper px-5 text-[16px] text-ink transition-colors hover:bg-mist"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14" />
                    </svg>
                    {{ __('Exportar copia completa (.zip)') }}
                </a>
            </x-form-section>
        </div>

        {{-- Resumen de la cuenta + zona de riesgo --}}
        <aside class="col-span-12 lg:col-span-4">
            <div class="space-y-4 lg:sticky lg:top-20">
                <x-card variant="tint" class="p-6">
                    <p class="text-sm text-navy/80">{{ __('Cuenta') }}</p>
                    <div class="mt-4 flex items-center gap-4">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-navy text-[18px] font-w480 text-paper">
                            {{ $initials }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-w480 text-ink">{{ $user->name }}</p>
                            <p class="truncate text-[13px] text-navy/80">{{ $user->email }}</p>
                        </div>
                    </div>
                </x-card>

                @include('profile.partials.delete-user-form')
            </div>
        </aside>
    </div>
</x-app-layout>
