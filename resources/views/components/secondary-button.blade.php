<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-buttons border border-ink bg-transparent px-5 py-2.5 text-body font-normal text-ink hover:bg-mist focus:outline-none focus:ring-2 focus:ring-ink/40 focus:ring-offset-2 disabled:opacity-25 active:bg-mist transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>