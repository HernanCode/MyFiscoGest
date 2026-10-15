<x-guest-layout>
    <h1 class="font-serif text-[32px] font-normal tracking-tight text-ink lg:text-[40px]">
        {{ __('Verifica tu correo') }}
    </h1>
    <p class="mt-2 text-sm text-slate lg:text-body">
        {{ __('Antes de empezar, verifica tu dirección de correo electrónico pulsando en el enlace que te hemos enviado. Si no lo has recibido, te lo reenviamos.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <p class="mt-4 rounded-inputs bg-mist px-4 py-3 text-sm font-w480 text-ink">
            {{ __('Se ha enviado un nuevo enlace de verificación a tu dirección de correo.') }}
        </p>
    @endif

    <div class="mt-8 space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full justify-center lg:py-3.5 lg:text-body-lg">
                {{ __('Reenviar correo de verificación') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="rounded-small text-sm text-slate hover:text-ink focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ink/40">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
