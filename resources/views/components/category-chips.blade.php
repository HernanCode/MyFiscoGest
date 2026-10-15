{{-- Categoría como chips según el tipo (ingreso/gasto). El valor se envía por un
     hidden con el mismo nombre que el select original, así la validación no cambia. --}}
@props([
    'categories',
    'model' => 'categoryId',
    'name' => 'category_id',
])

@php
    $income = $categories->where('type', 'income')->values();
    $expense = $categories->where('type', 'expense')->values();
    $on = 'bg-ink text-paper font-w480';
    $off = 'bg-paper text-mid hover:text-ink';
    $chip = 'h-9 rounded-buttons px-4 text-[14px] transition-colors';
@endphp

<input type="hidden" name="{{ $name }}" :value="{{ $model }}">

<div x-show="type === 'income'" x-cloak class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Categoría') }}">
    <button type="button" @click="pickCategory('')" :class="! {{ $model }} ? '{{ $on }}' : '{{ $off }}'" class="{{ $chip }}">
        {{ __('Sin categoría') }}
    </button>
    @foreach ($income as $category)
        <button
            type="button"
            @click="pickCategory('{{ $category->id }}')"
            :class="String({{ $model }}) === '{{ $category->id }}' ? '{{ $on }}' : '{{ $off }}'"
            class="{{ $chip }}"
        >{{ $category->name }}</button>
    @endforeach
</div>

<div x-show="type === 'expense'" x-cloak class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Categoría') }}">
    <button type="button" @click="pickCategory('')" :class="! {{ $model }} ? '{{ $on }}' : '{{ $off }}'" class="{{ $chip }}">
        {{ __('Sin categoría') }}
    </button>
    @foreach ($expense as $category)
        <button
            type="button"
            @click="pickCategory('{{ $category->id }}')"
            :class="String({{ $model }}) === '{{ $category->id }}' ? '{{ $on }}' : '{{ $off }}'"
            class="{{ $chip }}"
        >{{ $category->name }}</button>
    @endforeach
</div>
