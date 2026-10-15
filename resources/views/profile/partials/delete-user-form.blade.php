<x-card variant="white" class="p-6">
    <h3 class="text-sm font-w480 text-ash">{{ __('Eliminar cuenta') }}</h3>

    <p class="mt-1.5 text-[13px] text-slate">
        {{ __('Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán de forma permanente. Antes de eliminarla, descarga cualquier dato o información que quieras conservar.') }}
    </p>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="mt-4"
    >{{ __('Eliminar cuenta') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-subheading font-medium text-ink">
                {{ __('¿Seguro que quieres eliminar tu cuenta?') }}
            </h2>

            <p class="mt-2 text-sm text-slate">
                {{ __('Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán de forma permanente. Introduce tu contraseña para confirmar que quieres eliminar tu cuenta definitivamente.') }}
            </p>

            <div class="mt-6">
                <x-form-label for="password" class="sr-only">{{ __('Contraseña') }}</x-form-label>
                <x-form-input
                    id="password"
                    class="mt-2"
                    name="password"
                    type="password"
                    placeholder="{{ __('Contraseña') }}"
                    :invalid="$errors->userDeletion->has('password')"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Eliminar cuenta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</x-card>
