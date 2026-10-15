<x-guest-layout>
    <h1 class="font-serif text-[32px] font-normal tracking-tight text-ink lg:text-[40px]">
        {{ __('Confirmar contraseña') }}
    </h1>
    <p class="mt-2 text-sm text-slate lg:text-body">
        {{ __('Esta es una zona segura de la aplicación. Confirma tu contraseña antes de continuar.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5 lg:space-y-6">
        @csrf

        <div>
            <x-form-label for="password">{{ __('Contraseña') }}</x-form-label>
            <x-form-input
                id="password"
                class="mt-2 lg:py-3.5 lg:text-body"
                tone="mist"
                type="password"
                name="password"
                :invalid="$errors->has('password')"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center lg:py-3.5 lg:text-body-lg">
            {{ __('Confirmar') }}
        </x-primary-button>
    </form>
</x-guest-layout>
