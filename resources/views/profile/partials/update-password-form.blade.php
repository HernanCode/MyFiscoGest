<x-form-section
    :title="__('Actualizar contraseña')"
    :description="__('Usa una contraseña larga y aleatoria para mantener tu cuenta segura.')"
>
    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div>
            <x-form-label for="update_password_current_password">{{ __('Contraseña actual') }}</x-form-label>
            <x-form-input
                id="update_password_current_password"
                class="mt-2"
                name="current_password"
                type="password"
                :invalid="$errors->updatePassword->has('current_password')"
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-form-label for="update_password_password">{{ __('Nueva contraseña') }}</x-form-label>
            <x-form-input
                id="update_password_password"
                class="mt-2"
                name="password"
                type="password"
                :invalid="$errors->updatePassword->has('password')"
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-form-label for="update_password_password_confirmation">{{ __('Confirmar contraseña') }}</x-form-label>
            <x-form-input
                id="update_password_password_confirmation"
                class="mt-2"
                name="password_confirmation"
                type="password"
                :invalid="$errors->updatePassword->has('password_confirmation')"
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 pt-1">
            <button
                type="submit"
                class="inline-flex h-10 items-center gap-2 rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800"
            >
                {{ __('Guardar') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</x-form-section>
