<x-guest-layout>
    <h1 class="font-serif text-[32px] font-normal tracking-tight text-ink lg:text-[40px]">
        {{ __('Inicia sesión') }}
    </h1>
    <p class="mt-2 text-sm text-slate lg:text-body">{{ __('Accede con tu cuenta para ver tu panel.') }}</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5 lg:space-y-6">
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
                placeholder="tu@email.com"
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
                placeholder="••••••••"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-hairline text-ink shadow-none focus:ring-ink/40" name="remember">
                <span class="ms-2 text-sm text-slate">{{ __('Recuérdame') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="rounded-small text-sm text-slate hover:text-ink focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ink/40" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center lg:py-3.5 lg:text-body-lg">
            {{ __('Iniciar sesión') }}
        </x-primary-button>
    </form>
</x-guest-layout>
