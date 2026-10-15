<x-app-layout>
    <x-slot name="title">{{ __('Usuarios') }}</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="font-serif text-4xl font-normal tracking-tight text-ink">{{ __('Usuarios') }}</h2>
            <p class="mt-3 text-body text-slate">
                {{ __('Invita a quien quieras dar acceso y gestiona sus cuentas. Cada usuario ve únicamente sus propios datos.') }}
            </p>
        </div>
    </x-slot>

    @php
        $describeActivity = function (\App\Models\AdminAuditLog $activity): string {
            $actor = $activity->actor?->name ?? __('Administrador');
            $target = $activity->target_label ?? $activity->target?->email ?? __('usuario eliminado');

            $text = match ($activity->action) {
                'user.updated' => __(':actor actualizó la cuenta de :target', ['actor' => $actor, 'target' => $target]),
                'user.deactivated' => __(':actor desactivó la cuenta de :target', ['actor' => $actor, 'target' => $target]),
                'user.reactivated' => __(':actor reactivó la cuenta de :target', ['actor' => $actor, 'target' => $target]),
                'user.reset_link' => __(':actor generó un enlace de restablecimiento para :target', ['actor' => $actor, 'target' => $target]),
                'user.deleted' => __(':actor eliminó la cuenta de :target', ['actor' => $actor, 'target' => $target]),
                default => __(':actor realizó una acción sobre :target', ['actor' => $actor, 'target' => $target]),
            };

            if ($activity->action === 'user.deleted' && is_array($activity->meta)) {
                $text .= ' '.__('(:movements movimientos, :years años)', [
                    'movements' => $activity->meta['movements'] ?? 0,
                    'years' => $activity->meta['fiscal_years'] ?? 0,
                ]);
            }

            return $text;
        };
    @endphp

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 space-y-4 lg:col-span-8">
            @if (session('status'))
                <p class="rounded-inputs bg-mist px-4 py-3 text-sm font-w480 text-ink">{{ session('status') }}</p>
            @endif

            @if (session('reset_link'))
                <x-form-section
                    :title="__('Enlace de restablecimiento')"
                    :description="__('Cópialo y compártelo con el usuario. Caduca pronto y solo sirve una vez.')"
                >
                    <input
                        type="text"
                        readonly
                        value="{{ session('reset_link') }}"
                        onclick="this.select()"
                        class="h-10 w-full rounded-inputs border-0 bg-paper px-4 text-[13px] text-slate"
                    >
                </x-form-section>
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

            <x-form-section
                :title="__('Actividad reciente')"
                :description="__('Últimas acciones del administrador sobre las cuentas.')"
            >
                @if ($activities->isEmpty())
                    <p class="text-sm text-slate">{{ __('Todavía no hay actividad.') }}</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($activities as $activity)
                            <li class="flex items-start justify-between gap-4">
                                <span class="text-[13px] text-slate">{{ $describeActivity($activity) }}</span>
                                <time class="shrink-0 text-[12px] text-ash">{{ $activity->created_at?->diffForHumans() }}</time>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-form-section>
        </div>

        <aside class="col-span-12 lg:col-span-4">
            <x-form-section :title="__('Cuentas')">
                <div class="space-y-4">
                    @foreach ($users as $account)
                        @php
                            $isSelf = $account->id === auth()->id();
                            $isEditing = (int) old('_user') === $account->id;
                            $isDeleting = (int) session('delete_user_id') === $account->id;
                        @endphp

                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-w480 text-ink">
                                    {{ $account->name }}
                                    @if ($isSelf)
                                        <span class="text-ash">· {{ __('tú') }}</span>
                                    @endif
                                </p>
                                <p class="truncate text-[13px] text-slate">{{ $account->email }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    @if ($account->isAdmin())
                                        <span class="rounded-buttons bg-paper px-2 py-0.5 text-[11px] text-ink">{{ __('Dueño') }}</span>
                                    @endif
                                    @if ($account->isActive())
                                        <span class="rounded-buttons bg-paper px-2 py-0.5 text-[11px] text-slate">{{ __('Activa') }}</span>
                                    @else
                                        <span class="rounded-buttons bg-ink px-2 py-0.5 text-[11px] text-paper">{{ __('Desactivada') }}</span>
                                    @endif
                                </div>
                            </div>

                            @unless ($isSelf)
                                <x-dropdown align="right" width="w-64">
                                    <x-slot name="trigger">
                                        <button type="button" class="rounded-small p-1.5 text-slate transition-colors hover:bg-paper hover:text-ink" aria-label="{{ __('Acciones de :name', ['name' => $account->name]) }}">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                            </svg>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <button
                                            type="button"
                                            x-on:click="$dispatch('open-modal', 'edit-user-{{ $account->id }}')"
                                            class="block w-full rounded-small px-4 py-2 text-start text-sm text-slate transition-colors hover:bg-mist hover:text-ink"
                                        >{{ __('Editar datos') }}</button>

                                        <form method="POST" action="{{ route('users.reset-password', $account) }}">
                                            @csrf
                                            <button type="submit" class="block w-full rounded-small px-4 py-2 text-start text-sm text-slate transition-colors hover:bg-mist hover:text-ink">{{ __('Generar enlace de contraseña') }}</button>
                                        </form>

                                        <form method="POST" action="{{ route('users.toggle-status', $account) }}">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" class="block w-full rounded-small px-4 py-2 text-start text-sm text-slate transition-colors hover:bg-mist hover:text-ink">
                                                {{ $account->isActive() ? __('Desactivar cuenta') : __('Reactivar cuenta') }}
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            x-on:click="$dispatch('open-modal', 'delete-user-{{ $account->id }}')"
                                            class="block w-full rounded-small px-4 py-2 text-start text-sm text-red-600 transition-colors hover:bg-red-50"
                                        >{{ __('Eliminar cuenta') }}</button>
                                    </x-slot>
                                </x-dropdown>
                            @endunless
                        </div>

                        @unless ($isSelf)
                            {{-- Editar nombre y correo --}}
                            <x-modal name="edit-user-{{ $account->id }}" :show="$isEditing" focusable>
                                <form method="POST" action="{{ route('users.update', $account) }}" class="p-6">
                                    @csrf
                                    @method('patch')
                                    <input type="hidden" name="_user" value="{{ $account->id }}">

                                    <h2 class="text-subheading font-medium text-ink">{{ __('Editar cuenta') }}</h2>
                                    <p class="mt-2 text-sm text-slate">{{ __('Corrige el nombre o el correo de :name.', ['name' => $account->name]) }}</p>

                                    <div class="mt-5 space-y-4">
                                        <div>
                                            <x-form-label for="name-{{ $account->id }}">{{ __('Nombre') }}</x-form-label>
                                            <x-form-input
                                                id="name-{{ $account->id }}"
                                                class="mt-2"
                                                name="name"
                                                type="text"
                                                :value="$isEditing ? old('name', $account->name) : $account->name"
                                                :invalid="$isEditing && $errors->has('name')"
                                            />
                                            @if ($isEditing)
                                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                            @endif
                                        </div>

                                        <div>
                                            <x-form-label for="email-{{ $account->id }}">{{ __('Correo electrónico') }}</x-form-label>
                                            <x-form-input
                                                id="email-{{ $account->id }}"
                                                class="mt-2"
                                                name="email"
                                                type="email"
                                                :value="$isEditing ? old('email', $account->email) : $account->email"
                                                :invalid="$isEditing && $errors->has('email')"
                                            />
                                            @if ($isEditing)
                                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-6 flex justify-end">
                                        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancelar') }}</x-secondary-button>
                                        <x-primary-button class="ms-3">{{ __('Guardar') }}</x-primary-button>
                                    </div>
                                </form>
                            </x-modal>

                            {{-- Eliminar cuenta --}}
                            <x-modal name="delete-user-{{ $account->id }}" :show="$isDeleting" focusable>
                                <form method="POST" action="{{ route('users.destroy', $account) }}" class="p-6">
                                    @csrf
                                    @method('delete')

                                    <h2 class="text-subheading font-medium text-ink">{{ __('Eliminar la cuenta de :name', ['name' => $account->name]) }}</h2>
                                    <p class="mt-2 text-sm text-slate">
                                        {{ __('Se borrarán de forma permanente sus datos y facturas. Si necesitas conservarlos por obligación fiscal, desactiva la cuenta en su lugar.') }}
                                    </p>

                                    <div class="mt-5">
                                        <x-form-label for="confirm-email-{{ $account->id }}">{{ __('Escribe :email para confirmar', ['email' => $account->email]) }}</x-form-label>
                                        <x-form-input
                                            id="confirm-email-{{ $account->id }}"
                                            class="mt-2"
                                            name="confirm_email"
                                            type="email"
                                            autocomplete="off"
                                            :invalid="$isDeleting && $errors->userDeletion->has('confirm_email')"
                                        />
                                        @if ($isDeleting)
                                            <x-input-error :messages="$errors->userDeletion->get('confirm_email')" class="mt-2" />
                                        @endif
                                    </div>

                                    <div class="mt-6 flex justify-end">
                                        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancelar') }}</x-secondary-button>
                                        <x-danger-button class="ms-3">{{ __('Eliminar definitivamente') }}</x-danger-button>
                                    </div>
                                </form>
                            </x-modal>
                        @endunless
                    @endforeach
                </div>
            </x-form-section>
        </aside>
    </div>
</x-app-layout>
