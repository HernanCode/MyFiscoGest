<x-app-layout>
    <x-slot name="title">{{ __('Movimientos') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2
                    @if (! $movement)
                        x-data="{ heading: @js($defaultType === 'expense' ? __('Nuevo gasto') : __('Nuevo ingreso')) }"
                        @form-type.window="heading = $event.detail"
                        x-text="heading"
                    @endif
                    class="font-serif text-4xl font-normal tracking-tight text-ink"
                >{{ $movement ? __('Editar movimiento') : ($defaultType === 'expense' ? __('Nuevo gasto') : __('Nuevo ingreso')) }}</h2>
                <a href="{{ route('movements.index') }}" class="mt-3 inline-block text-body text-slate hover:text-ink">
                    ← {{ __('Volver a movimientos') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $formData = [
            'type' => old('type', $defaultType),
            'categoryId' => old('category_id', $movement?->category_id ?? ''),
            'vatRate' => old('vat_rate', $movement?->vat_rate ?? 21),
            'irpfRate' => old('irpf_rate', $movement?->irpf_rate ?? 15),
            'deductiblePct' => old('deductible_pct', $movement?->deductible_pct ?? 100),
            'base' => old('base_amount', $movement?->base_amount ?? ''),
            'paymentStatus' => old('payment_status', $movement?->payment_status ?? 'pending'),
            'recurring' => old('recurring', false),
            'recurringDay' => old('recurring_day', $movement?->date?->format('j') ?? today()->format('j')),
        ];
        $deductibles = $categories->where('type', 'expense')
            ->mapWithKeys(fn ($c) => [(string) $c->id => (int) $c->default_deductible_pct])
            ->all();
        $counterparties = $counterparties ?? ['income' => [], 'expense' => []];
    @endphp

    <div
        class="grid grid-cols-12 gap-6"
        x-data="{
            type: @js($formData['type']),
            categoryId: @js($formData['categoryId']),
            vatRate: @js($formData['vatRate']),
            irpfRate: @js($formData['irpfRate']),
            deductiblePct: @js($formData['deductiblePct']),
            base: @js($formData['base']),
            paymentStatus: @js($formData['paymentStatus']),
            recurring: @js($formData['recurring']),
            recurringDay: @js($formData['recurringDay']),
            deductibles: @js($deductibles),

            get vat() { return (Number(this.base) || 0) * (Number(this.vatRate) || 0) / 100; },
            get irpf() { return this.type === 'income' ? (Number(this.base) || 0) * (Number(this.irpfRate) || 0) / 100 : 0; },
            get total() { return (Number(this.base) || 0) + this.vat - this.irpf; },
            money(value) { return (Number(value) || 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; },
            isOption(val, opts) { return val !== '' && val !== null && typeof val !== 'undefined' && opts.map(Number).includes(Number(val)); },
            pickType(t) {
                this.type = t;
                this.categoryId = '';
                if (t === 'expense') {
                    this.irpfRate = 0;
                    this.deductiblePct = 100;
                } else if (! this.irpfRate) {
                    this.irpfRate = 15;
                }
            },
            pickCategory(id) {
                this.categoryId = id;
                if (this.type === 'expense' && this.deductibles[id]) {
                    this.deductiblePct = this.deductibles[id];
                }
            },
        }"
    >
        {{-- Previsualización: encima en móvil, fija a la derecha en escritorio --}}
        <aside class="order-first col-span-12 lg:order-last lg:col-span-5 xl:col-span-4">
            <div class="lg:sticky lg:top-20">
                <x-form-preview />
            </div>
        </aside>

        {{-- Formulario --}}
        <div class="col-span-12 lg:col-span-7 xl:col-span-8">
            <form
                method="POST"
                action="{{ $movement ? route('movements.update', $movement) : route('movements.store') }}"
                enctype="multipart/form-data"
                class="space-y-4"
            >
                @csrf
                @if ($movement)
                    @method('PUT')
                @else
                    <input type="hidden" name="type" :value="type">
                @endif

                {{-- Tipo: píldora como la de trimestres del Panel --}}
                <div>
                    <x-form-label>{{ __('Tipo') }}</x-form-label>
                    @if ($movement)
                        <div class="mt-2 inline-flex">
                            <span class="rounded-buttons px-5 py-2 text-[15px] font-w480 text-paper {{ $movement->type === 'income' ? 'bg-navy' : 'bg-sienna' }}">
                                {{ $movement->type === 'income' ? __('Ingreso') : __('Gasto') }}
                            </span>
                        </div>
                    @else
                        <div class="mt-2 inline-flex items-center gap-1 rounded-buttons bg-mist p-1">
                            <button
                                type="button"
                                @click="pickType('income'); $dispatch('form-type', @js(__('Nuevo ingreso')))"
                                :class="type === 'income' ? 'bg-navy text-paper font-w480' : 'text-mid hover:bg-paper'"
                                class="rounded-buttons px-5 py-2 text-[15px] transition-colors"
                            >{{ __('Ingreso') }}</button>
                            <button
                                type="button"
                                @click="pickType('expense'); $dispatch('form-type', @js(__('Nuevo gasto')))"
                                :class="type === 'expense' ? 'bg-sienna text-paper font-w480' : 'text-mid hover:bg-paper'"
                                class="rounded-buttons px-5 py-2 text-[15px] transition-colors"
                            >{{ __('Gasto') }}</button>
                        </div>
                    @endif
                </div>

                {{-- Datos de la factura --}}
                <x-form-section :title="__('Datos de la factura')">
                    <div class="space-y-4">
                        <div>
                            <x-form-label for="concept">{{ __('Concepto') }}</x-form-label>
                            <x-form-input
                                id="concept"
                                class="mt-2"
                                type="text"
                                name="concept"
                                :value="old('concept', $movement?->concept)"
                                :invalid="$errors->has('concept')"
                                required
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('concept')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <x-form-label for="date">{{ __('Fecha') }}</x-form-label>
                                <x-form-input
                                    id="date"
                                    class="mt-2"
                                    type="date"
                                    name="date"
                                    :value="old('date', $movement?->date?->format('Y-m-d'))"
                                    :invalid="$errors->has('date')"
                                    required
                                />
                                <x-input-error :messages="$errors->get('date')" class="mt-2" />
                            </div>

                            <div>
                                <x-form-label for="invoice_number">{{ __('Nº de factura') }}</x-form-label>
                                <x-form-input
                                    id="invoice_number"
                                    class="mt-2"
                                    type="text"
                                    name="invoice_number"
                                    :value="old('invoice_number', $movement?->invoice_number)"
                                    placeholder="F-2026-001"
                                />
                                <x-input-error :messages="$errors->get('invoice_number')" class="mt-2" />
                            </div>

                            <div>
                                <x-form-label for="payment_status">{{ __('Estado') }}</x-form-label>
                                <div class="relative mt-2">
                                    <x-form-select
                                        id="payment_status"
                                        name="payment_status"
                                        x-model="paymentStatus"
                                        :invalid="$errors->has('payment_status')"
                                    >
                                        <option value="pending">{{ __('Pendiente') }}</option>
                                        <option value="paid">{{ __('Cobrado / Pagado') }}</option>
                                    </x-form-select>
                                    <svg class="pointer-events-none absolute top-1/2 right-4 h-4 w-4 -translate-y-1/2 text-mid" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <x-input-error :messages="$errors->get('payment_status')" class="mt-2" />
                            </div>
                        </div>

                        <p class="text-[13px] text-slate" x-show="paymentStatus === 'paid'" x-cloak>
                            {{ __('Se registrará con fecha de hoy') }}@if ($movement?->paid_at) ({{ $movement->paid_at->format('d/m/Y') }})@endif.
                        </p>
                    </div>
                </x-form-section>

                {{-- Cliente y categoría --}}
                <x-form-section :title="__('Cliente y categoría')">
                    <div class="space-y-4">
                        <div>
                            <x-form-label for="counterparty">{{ __('Cliente o proveedor') }}</x-form-label>
                            <div class="mt-2">
                                <x-counterparty-combobox
                                    :value="old('counterparty', $movement?->counterparty)"
                                    :income="$counterparties['income']"
                                    :expense="$counterparties['expense']"
                                    :type="$formData['type']"
                                    placeholder="{{ $defaultType === 'expense' ? 'Ej.: Proveedor S.L.' : 'Ej.: Empresa Cliente S.L.' }}"
                                />
                            </div>
                            <x-input-error :messages="$errors->get('counterparty')" class="mt-2" />
                        </div>

                        <div>
                            <x-form-label>{{ __('Categoría') }}</x-form-label>
                            <div class="mt-2">
                                <x-category-chips :categories="$categories" />
                            </div>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                        </div>
                    </div>
                </x-form-section>

                {{-- Importes --}}
                <x-form-section :title="__('Importes')">
                    <div class="space-y-4">
                        <div>
                            <x-form-label for="base_amount">{{ __('Base imponible') }}</x-form-label>
                            <div class="relative mt-2">
                                <input
                                    id="base_amount"
                                    name="base_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    x-model="base"
                                    placeholder="0,00"
                                    required
                                    class="w-full rounded-inputs border-0 bg-paper py-3.5 ps-4 pe-12 text-[28px] font-w450 tabular-nums text-ink placeholder:text-smoke/70 shadow-none transition-colors focus:outline-none focus:ring-2 focus:ring-ink/15 @error('base_amount') ring-2 ring-red-400 @enderror"
                                >
                                <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-[20px] text-smoke">€</span>
                            </div>
                            <x-input-error :messages="$errors->get('base_amount')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-rate-chips
                                name="vat_rate"
                                model="vatRate"
                                id="vat_rate"
                                :label="__('% IVA')"
                                :options="[21, 10, 4, 0]"
                            />

                            <x-rate-chips
                                x-show="type === 'income'"
                                x-cloak
                                name="irpf_rate"
                                model="irpfRate"
                                id="irpf_rate"
                                :label="__('% retención IRPF')"
                                :options="[15, 7, 0]"
                            />

                            <x-rate-chips
                                x-show="type === 'expense'"
                                x-cloak
                                name="deductible_pct"
                                model="deductiblePct"
                                id="deductible_pct"
                                :label="__('% deducible')"
                                :options="[100, 50, 0]"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('vat_rate')" class="mt-2" />
                        <x-input-error :messages="$errors->get('irpf_rate')" class="mt-2" />
                        <x-input-error :messages="$errors->get('deductible_pct')" class="mt-2" />
                    </div>
                </x-form-section>

                {{-- Repetición --}}
                @if (! $recurringMovement)
                    <x-form-section :title="__('Repetición')">
                        <label for="recurring" class="flex cursor-pointer items-start gap-3">
                            <input
                                id="recurring"
                                type="checkbox"
                                x-model="recurring"
                                class="mt-1 h-4 w-4 rounded border-hairline text-ink focus:ring-ink"
                            />
                            <span>
                                <span class="text-body font-w480 text-ink">{{ __('Repetir cada mes') }}</span>
                                <span class="block text-sm text-slate">{{ __('Se creará un movimiento igual cada mes. Podrás adjuntarle la factura cuando te llegue.') }}</span>
                            </span>
                        </label>

                        {{-- Alpine no serializa bien el checkbox, así que va por un hidden explícito --}}
                        <input type="hidden" name="recurring" :value="recurring ? '1' : '0'">

                        <div x-show="recurring" x-cloak class="mt-4 max-w-[180px]">
                            <x-form-label for="recurring_day">{{ __('Día del mes') }}</x-form-label>
                            <x-form-input
                                id="recurring_day"
                                class="mt-2"
                                type="number"
                                name="recurring_day"
                                min="1"
                                max="31"
                                x-model="recurringDay"
                                :value="$formData['recurringDay']"
                            />
                            <p class="mt-2 text-[13px] text-slate">{{ __('Si el mes no tiene ese día, se usará el último.') }}</p>
                            <x-input-error :messages="$errors->get('recurring_day')" class="mt-2" />
                        </div>
                    </x-form-section>
                @else
                    <x-form-section :title="__('Repetición')">
                        <p class="text-body font-w480 text-ink">{{ __('Se repite cada mes') }}</p>
                        <p class="mt-1 text-sm text-slate">{{ __('Este movimiento se genera desde una plantilla. Para cambiar el importe o el día, edita la recurrencia.') }}</p>
                        <a
                            href="{{ route('movements.recurring.edit', $recurringMovement) }}"
                            class="mt-2 inline-block text-sm text-ink underline decoration-hairline underline-offset-4 hover:decoration-ink"
                        >{{ __('Editar la recurrencia') }} →</a>
                    </x-form-section>
                @endif

                {{-- Factura y notas --}}
                <x-form-section :title="__('Factura y notas')">
                    <div class="space-y-4">
                        <div>
                            <x-form-label for="invoice">{{ __('Factura (PDF, JPG o PNG · máx. 10 MB)') }}</x-form-label>
                            <input
                                id="invoice"
                                name="invoice"
                                type="file"
                                accept="application/pdf,image/jpeg,image/png"
                                class="mt-2 block w-full cursor-pointer rounded-inputs bg-paper text-sm text-slate file:me-3 file:cursor-pointer file:rounded-buttons file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:text-paper hover:file:bg-slate-800"
                            />

                            @if ($movement?->invoice_path)
                                <p class="mt-3 text-sm text-slate">
                                    {{ __('Adjunto actual:') }}
                                    <a href="{{ route('movements.invoice', $movement) }}" target="_blank" class="font-medium text-ink underline decoration-hairline underline-offset-4 hover:decoration-ink">
                                        {{ basename($movement->invoice_path) }}
                                    </a>
                                    — {{ __('si subes uno nuevo, reemplazará al actual.') }}
                                </p>
                            @endif

                            <x-input-error :messages="$errors->get('invoice')" class="mt-2" />
                        </div>

                        <div>
                            <x-form-label for="notes">{{ __('Notas (opcional)') }}</x-form-label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="mt-2 w-full rounded-inputs border-0 bg-paper px-4 py-3 text-base text-ink placeholder:text-smoke shadow-none transition-colors focus:outline-none focus:ring-2 focus:ring-ink/15"
                            >{{ old('notes', $movement?->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </x-form-section>

                {{-- Barra de acciones fija --}}
                <div class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-buttons border border-hairline bg-paper/95 px-3 py-2 backdrop-blur">
                    <a href="{{ route('movements.index') }}" class="inline-flex h-10 items-center rounded-buttons px-4 text-[16px] text-slate transition-colors hover:text-ink">
                        {{ __('Cancelar') }}
                    </a>
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800"
                    >
                        @if ($movement)
                            {{ __('Guardar cambios') }}
                        @else
                            <span x-text="type === 'expense' ? @js(__('Guardar gasto')) : @js(__('Guardar ingreso'))"></span>
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
