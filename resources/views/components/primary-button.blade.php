<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-buttons bg-ink px-5 py-2.5 text-body font-normal text-paper hover:bg-slate-800 active:bg-ink focus:outline-none focus:ring-2 focus:ring-ink/40 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>