<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-buttons border border-red-600 bg-transparent px-5 py-2.5 text-body font-normal text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25 active:bg-red-100 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>