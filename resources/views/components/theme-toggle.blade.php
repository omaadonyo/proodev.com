<button
    type="button"
    class="flex size-8 items-center justify-center rounded-full border border-zinc-200 bg-white/70 text-zinc-500 shadow-sm backdrop-blur transition-colors duration-200 hover:bg-white hover:text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
    x-data
    x-on:click="$flux.dark = ! $flux.dark"
    :aria-label="$flux.dark ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="$flux.dark ? 'Switch to light mode' : 'Switch to dark mode'"
>
    <flux:icon
        name="sun"
        variant="mini"
        x-show="!$flux.dark"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -rotate-90 scale-50"
        x-transition:enter-end="opacity-100 rotate-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 rotate-0 scale-100"
        x-transition:leave-end="opacity-0 rotate-90 scale-50"
    />

    <flux:icon
        name="moon"
        variant="mini"
        x-show="$flux.dark"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 rotate-90 scale-50"
        x-transition:enter-end="opacity-100 rotate-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 rotate-0 scale-100"
        x-transition:leave-end="opacity-0 -rotate-90 scale-50"
    />
</button>
