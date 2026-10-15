<x-guest-layout>
    <h1 class="font-serif text-[32px] font-normal tracking-tight text-ink lg:text-[40px]">
        {{ __('Crea tu cuenta') }}
    </h1>
    <p class="mt-2 text-sm text-slate lg:text-body">
        {{ __('Te han invitado a esta instancia. Elige tu nombre y tu contraseña para empezar.') }}
    </p>

    <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-8 space-y-5 lg:space-y-6">
        @csrf

        <div>
            <x-form-label for="name">{{ __('Nombre') }}</x-form-label>
            <x-form-input
                id="name"
                class="mt-2 lg:py-3.5 lg:text-body"
                tone="mist"
                type="text"
                name="name"
                :value="old('name')"
                :invalid="$errors->has('name')"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-form-label for="email">{{ __('Correo electrónico') }}</x-form-label>
            <x-form-input
                id="email"
                class="mt-2 lg:py-3.5 lg:text-body"
                tone="mist"
                type="email"
                name="email"
                :value="old('email', $invitation->email)"
                :invalid="$errors->has('email')"
                required
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

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
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-form-label for="password_confirmation">{{ __('Confirmar contraseña') }}</x-form-label>
            <x-form-input
                id="password_confirmation"
                class="mt-2 lg:py-3.5 lg:text-body"
                tone="mist"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
        </div>

        <x-primary-button class="w-full justify-center lg:py-3.5 lg:text-body-lg">
            {{ __('Crear cuenta') }}
        </x-primary-button>
    </form>
</x-guest-layout>
