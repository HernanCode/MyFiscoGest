<x-form-section
    :title="__('Información del perfil')"
    :description="__('Actualiza la información y la dirección de correo de tu cuenta.')"
>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <div>
            <x-form-label for="name">{{ __('Nombre') }}</x-form-label>
            <x-form-input
                id="name"
                class="mt-2"
                name="name"
                type="text"
                :value="old('name', $user->name)"
                :invalid="$errors->has('name')"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-form-label for="email">{{ __('Correo electrónico') }}</x-form-label>
            <x-form-input
                id="email"
                class="mt-2"
                name="email"
                type="email"
                :value="old('email', $user->email)"
                :invalid="$errors->has('email')"
                required
                autocomplete="username"
            />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-[13px] text-slate">
                        {{ __('Tu dirección de correo electrónico no está verificada.') }}

                        <button form="send-verification" class="rounded-small text-[13px] text-ink underline decoration-hairline underline-offset-4 hover:decoration-ink focus:outline-none">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-[13px] font-w480 text-ink">
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu dirección de correo.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-1">
            <button
                type="submit"
                class="inline-flex h-10 items-center gap-2 rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800"
            >
                {{ __('Guardar') }}
            </button>

            @if (session('status') === 'profile-updated')
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
