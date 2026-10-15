<x-guest-layout>
    <h1 class="font-serif text-[32px] font-normal tracking-tight text-ink lg:text-[40px]">
        {{ __('Restablecer contraseña') }}
    </h1>
    <p class="mt-2 text-sm text-slate lg:text-body">
        {{ __('Indícanos tu correo electrónico y te enviaremos un enlace para que puedas elegir una nueva.') }}
    </p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5 lg:space-y-6">
        @csrf

        <div>
            <x-form-label for="email">{{ __('Correo electrónico') }}</x-form-label>
            <x-form-input
                id="email"
                class="mt-2 lg:py-3.5 lg:text-body"
                tone="mist"
                type="email"
                name="email"
                :value="old('email')"
                :invalid="$errors->has('email')"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center lg:py-3.5 lg:text-body-lg">
            {{ __('Enviar enlace') }}
        </x-primary-button>
    </form>
</x-guest-layout>
