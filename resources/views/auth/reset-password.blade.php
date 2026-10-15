<x-guest-layout>
    <h1 class="font-serif text-[32px] font-normal tracking-tight text-ink lg:text-[40px]">
        {{ __('Nueva contraseña') }}
    </h1>
    <p class="mt-2 text-sm text-slate lg:text-body">{{ __('Elige una contraseña nueva para tu cuenta.') }}</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5 lg:space-y-6">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-form-label for="email">{{ __('Correo electrónico') }}</x-form-label>
            <x-form-input
                id="email"
                class="mt-2 lg:py-3.5 lg:text-body"
                tone="mist"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                :invalid="$errors->has('email')"
                required
                autofocus
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
                :invalid="$errors->has('password_confirmation')"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center lg:py-3.5 lg:text-body-lg">
            {{ __('Restablecer contraseña') }}
        </x-primary-button>
    </form>
</x-guest-layout>
