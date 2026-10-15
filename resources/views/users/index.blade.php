<x-app-layout>
    <x-slot name="title">{{ __('Usuarios') }}</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="font-serif text-4xl font-normal tracking-tight text-ink">{{ __('Usuarios') }}</h2>
            <p class="mt-3 text-body text-slate">
                {{ __('Invita a quien quieras dar acceso. Cada cuenta ve únicamente sus propios datos.') }}
            </p>
        </div>
    </x-slot>

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 space-y-4 lg:col-span-8">
            @if (session('status'))
                <p class="rounded-inputs bg-mist px-4 py-3 text-sm font-w480 text-ink">{{ session('status') }}</p>
            @endif

            <x-form-section
                :title="__('Invitar a alguien')"
                :description="__('Genera un enlace y compártelo. Caduca en 7 días y sirve una sola vez.')"
            >
                <form method="POST" action="{{ route('invitations.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf

                    <div class="min-w-0 flex-1 basis-64">
                        <x-form-label for="email">{{ __('Correo electrónico (opcional)') }}</x-form-label>
                        <x-form-input
                            id="email"
                            class="mt-2"
                            type="email"
                            name="email"
                            :value="old('email')"
                            :invalid="$errors->has('email')"
                            placeholder="amigo@email.com"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <button type="submit" class="inline-flex h-10 items-center rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800">
                        {{ __('Crear invitación') }}
                    </button>
                </form>
            </x-form-section>

            @if ($invitations->isNotEmpty())
                <x-form-section :title="__('Invitaciones pendientes')">
                    <div class="space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="flex flex-wrap items-center gap-3">
                                <input
                                    type="text"
                                    readonly
                                    value="{{ route('invitations.show', $invitation->token) }}"
                                    onclick="this.select()"
                                    class="h-10 min-w-0 flex-1 basis-64 rounded-inputs border-0 bg-paper px-4 text-[13px] text-slate"
                                >
                                @if ($invitation->email)
                                    <span class="shrink-0 text-[13px] text-slate">{{ $invitation->email }}</span>
                                @endif
                                <form method="POST" action="{{ route('invitations.destroy', $invitation) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded-small text-[13px] text-slate hover:text-ink">{{ __('Revocar') }}</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </x-form-section>
            @endif
        </div>

        <aside class="col-span-12 lg:col-span-4">
            <x-form-section :title="__('Cuentas')">
                <div class="space-y-3">
                    @foreach ($users as $account)
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-w480 text-ink">
                                    {{ $account->name }}
                                    @if ($account->id === auth()->id())
                                        <span class="text-ash">· {{ __('tú') }}</span>
                                    @endif
                                </p>
                                <p class="truncate text-[13px] text-slate">{{ $account->email }}</p>
                            </div>
                            @if ($account->isAdmin())
                                <span class="shrink-0 rounded-buttons bg-mist px-2.5 py-1 text-[11px] text-ink">{{ __('Dueño') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-form-section>
        </aside>
    </div>
</x-app-layout>
