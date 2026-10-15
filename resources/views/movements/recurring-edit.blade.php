<x-app-layout>
    <x-slot name="title">{{ __('Editar recurrencia') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-serif text-4xl font-normal tracking-tight text-ink">
                    {{ __('Editar recurrencia') }}
                </h2>
                <a href="{{ route('movements.recurring.index') }}" class="mt-3 inline-block text-body text-slate hover:text-ink">
                    ← {{ __('Volver a recurrentes') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $formData = [
            'base' => old('base_amount', $recurringMovement->base_amount),
            'vatRate' => old('vat_rate', $recurringMovement->vat_rate),
            'irpfRate' => old('irpf_rate', $recurringMovement->irpf_rate),
            'deductiblePct' => old('deductible_pct', $recurringMovement->deductible_pct),
            'active' => old('active', $recurringMovement->active),
        ];
        $deductibles = $categories->where('type', 'expense')
            ->mapWithKeys(fn ($c) => [(string) $c->id => (int) $c->default_deductible_pct])
            ->all();
        $isIncome = $recurringMovement->type === 'income';
        $counterparties = $counterparties ?? ['income' => [], 'expense' => []];
    @endphp

    <div
        class="grid grid-cols-12 gap-6"
        x-data="{
            type: @js($recurringMovement->type),
            categoryId: @js(old('category_id', $recurringMovement->category_id ?? '')),
            vatRate: @js($formData['vatRate']),
            irpfRate: @js($formData['irpfRate']),
            deductiblePct: @js($formData['deductiblePct']),
            base: @js($formData['base']),
            active: @js($formData['active']),
            deductibles: @js($deductibles),

            get vat() { return (Number(this.base) || 0) * (Number(this.vatRate) || 0) / 100; },
            get irpf() { return this.type === 'income' ? (Number(this.base) || 0) * (Number(this.irpfRate) || 0) / 100 : 0; },
            get total() { return (Number(this.base) || 0) + this.vat - this.irpf; },
            money(value) { return (Number(value) || 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; },
            isOption(val, opts) { return val !== '' && val !== null && typeof val !== 'undefined' && opts.map(Number).includes(Number(val)); },
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
                <x-form-preview :income-label="__('Total al mes')" :expense-label="__('Total al mes')" />
            </div>
        </aside>

        {{-- Formulario --}}
        <div class="col-span-12 lg:col-span-7 xl:col-span-8">
            <form
                method="POST"
                action="{{ route('movements.recurring.update', $recurringMovement) }}"
                class="space-y-4"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="active" :value="active ? '1' : '0'">

                {{-- Tipo (inmutable) y estado --}}
                <x-form-section :title="__('Tipo y estado')">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="rounded-buttons px-5 py-2 text-[15px] font-w480 text-paper {{ $isIncome ? 'bg-navy' : 'bg-sienna' }}">
                            {{ $isIncome ? __('Ingreso') : __('Gasto') }}
                        </span>

                        <div class="inline-flex items-center gap-1 rounded-buttons bg-paper p-1">
                            <button
                                type="button"
                                @click="active = true"
                                :class="active ? 'bg-ink text-paper font-w480' : 'text-mid hover:text-ink'"
                                class="rounded-buttons px-5 py-2 text-[15px] transition-colors"
                            >{{ __('Activa') }}</button>
                            <button
                                type="button"
                                @click="active = false"
                                :class="! active ? 'bg-ink text-paper font-w480' : 'text-mid hover:text-ink'"
                                class="rounded-buttons px-5 py-2 text-[15px] transition-colors"
                            >{{ __('Pausada') }}</button>
                        </div>
                    </div>
                    <p class="mt-3 text-[13px] text-slate">{{ __('Pausada no se generan movimientos nuevos; al reactivarla continúa desde el próximo mes.') }}</p>
                </x-form-section>

                {{-- Concepto, cliente y categoría --}}
                <x-form-section :title="__('Concepto, cliente y categoría')">
                    <div class="space-y-4">
                        <div>
                            <x-form-label for="concept">{{ __('Concepto') }}</x-form-label>
                            <x-form-input
                                id="concept"
                                class="mt-2"
                                type="text"
                                name="concept"
                                :value="old('concept', $recurringMovement->concept)"
                                :invalid="$errors->has('concept')"
                                required
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('concept')" class="mt-2" />
                        </div>

                        <div>
                            <x-form-label for="counterparty">{{ __('Cliente o proveedor') }}</x-form-label>
                            <div class="mt-2">
                                <x-counterparty-combobox
                                    :value="old('counterparty', $recurringMovement->counterparty)"
                                    :income="$counterparties['income']"
                                    :expense="$counterparties['expense']"
                                    :type="$recurringMovement->type"
                                    placeholder="{{ $isIncome ? 'Ej.: Empresa Cliente S.L.' : 'Ej.: Proveedor S.L.' }}"
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

                            @if ($isIncome)
                                <x-rate-chips
                                    name="irpf_rate"
                                    model="irpfRate"
                                    id="irpf_rate"
                                    :label="__('% retención IRPF')"
                                    :options="[15, 7, 0]"
                                />
                            @else
                                <x-rate-chips
                                    name="deductible_pct"
                                    model="deductiblePct"
                                    id="deductible_pct"
                                    :label="__('% deducible')"
                                    :options="[100, 50, 0]"
                                />
                            @endif
                        </div>
                        <x-input-error :messages="$errors->get('vat_rate')" class="mt-2" />
                        <x-input-error :messages="$errors->get('irpf_rate')" class="mt-2" />
                        <x-input-error :messages="$errors->get('deductible_pct')" class="mt-2" />
                    </div>
                </x-form-section>

                {{-- Calendario --}}
                <x-form-section :title="__('Calendario')">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-form-label for="day_of_month">{{ __('Día del mes') }}</x-form-label>
                            <x-form-input
                                id="day_of_month"
                                class="mt-2"
                                type="number"
                                name="day_of_month"
                                min="1"
                                max="31"
                                :value="old('day_of_month', $recurringMovement->day_of_month)"
                                :invalid="$errors->has('day_of_month')"
                                required
                            />
                            <p class="mt-2 text-[13px] text-slate">{{ __('Si el mes no tiene ese día, se usará el último.') }}</p>
                            <x-input-error :messages="$errors->get('day_of_month')" class="mt-2" />
                        </div>

                        <div>
                            <x-form-label for="ends_on">{{ __('Dejar de generar a partir de') }}</x-form-label>
                            <x-form-input
                                id="ends_on"
                                class="mt-2"
                                type="date"
                                name="ends_on"
                                :value="old('ends_on', $recurringMovement->ends_on?->format('Y-m-d'))"
                                :invalid="$errors->has('ends_on')"
                            />
                            <p class="mt-2 text-[13px] text-slate">{{ __('Opcional. Si la fecha ya pasó, la recurrencia queda finalizada.') }}</p>
                            <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
                        </div>
                    </div>
                </x-form-section>

                {{-- Notas --}}
                <x-form-section :title="__('Notas')">
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="w-full rounded-inputs border-0 bg-paper px-4 py-3 text-base text-ink placeholder:text-smoke shadow-none transition-colors focus:outline-none focus:ring-2 focus:ring-ink/15"
                    >{{ old('notes', $recurringMovement->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </x-form-section>

                {{-- Barra de acciones fija --}}
                <div class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-buttons border border-hairline bg-paper/95 px-3 py-2 backdrop-blur">
                    <a href="{{ route('movements.recurring.index') }}" class="inline-flex h-10 items-center rounded-buttons px-4 text-[16px] text-slate transition-colors hover:text-ink">
                        {{ __('Cancelar') }}
                    </a>
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-buttons bg-ink px-5 text-[16px] text-paper transition-colors hover:bg-slate-800"
                    >
                        {{ __('Guardar cambios') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
