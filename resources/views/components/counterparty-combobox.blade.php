{{-- Combobox de cliente/proveedor: sugiere los valores ya usados y autocompleta.
     El texto escrito se envía como `counterparty`. Se apoya en el scope Alpine
     padre para saber el tipo (ingreso/gasto) y elegir la lista de sugerencias. --}}
@props([
    'name' => 'counterparty',
    'id' => 'counterparty',
    'value' => '',
    'income' => [],
    'expense' => [],
    'placeholder' => '',
    'type' => 'income',
])

<div
    x-data="{
        currentType: @js($type),
        optionsIncome: @js($income),
        optionsExpense: @js($expense),
        open: false,
        query: @js((string) $value),
        hi: -1,
        get pool() { return this.currentType === 'expense' ? this.optionsExpense : this.optionsIncome; },
        get filtered() {
            const q = (this.query || '').toLowerCase().trim();
            const pool = this.pool || [];
            const list = q ? pool.filter((o) => o.toLowerCase().includes(q)) : pool;
            return list.slice(0, 8);
        },
        choose(o) { this.query = o; this.open = false; this.hi = -1; },
        onInput() { this.open = true; this.hi = -1; },
        move(step) {
            if (! this.open) { this.open = true; return; }
            const n = this.filtered.length;
            if (! n) { return; }
            this.hi = (this.hi + step + n) % n;
        },
        enter() {
            if (this.open && this.hi >= 0 && this.filtered[this.hi]) { this.choose(this.filtered[this.hi]); }
            else { this.open = false; }
        },
    }"
    x-effect="currentType = (typeof type === 'undefined' ? currentType : type)"
    @click.outside="open = false"
    class="relative"
>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="text"
        x-model="query"
        @focus="open = true"
        @input="onInput()"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.enter.prevent="enter()"
        @keydown.escape="open = false"
        autocomplete="off"
        role="combobox"
        aria-autocomplete="list"
        :aria-expanded="open ? 'true' : 'false'"
        aria-controls="{{ $id }}-list"
        placeholder="{{ $placeholder }}"
        class="w-full rounded-inputs border-0 bg-paper px-4 py-3 text-base text-ink placeholder:text-smoke shadow-none transition-colors focus:outline-none focus:ring-2 focus:ring-ink/15"
    >

    <div
        x-show="open && filtered.length"
        x-cloak
        id="{{ $id }}-list"
        role="listbox"
        class="absolute z-30 mt-1 max-h-60 w-full overflow-auto rounded-small border border-hairline bg-paper p-1 shadow-subtle-2"
        style="display: none;"
    >
        <template x-for="(option, i) in filtered" :key="option">
            <button
                type="button"
                role="option"
                :aria-selected="hi === i"
                @click="choose(option)"
                @mouseenter="hi = i"
                :class="hi === i ? 'bg-mist font-w480 text-ink' : 'text-mid hover:bg-mist hover:text-ink'"
                class="block w-full rounded-small px-3 py-2 text-left text-sm"
                x-text="option"
            ></button>
        </template>
    </div>
</div>
