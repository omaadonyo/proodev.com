@props([
    'placeholder' => 'Select…',
    'searchPlaceholder' => 'Search…',
    'empty' => 'No options found',
    'size' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $live = $attributes->wire('model')->hasModifier('live');
    $size = $size ?: 'md';
    $containerClass = $attributes->get('class');

    // All dropdown selects share the same height, regardless of the size prop.
    $sizeClasses = match ($size) {
        'sm' => 'h-10 rounded-lg text-sm',
        'lg' => 'h-10 rounded-lg text-sm',
        default => 'h-10 rounded-lg text-sm',
    };
@endphp

<div
    x-data="{
        open: false,
        query: '',
        active: 0,
        init() {
            this.parseOptions();
        },
        value: {{ $wireModel ? '' : "''" }}@if ($wireModel)@entangle($wireModel){{ $live ? '.live' : '' }}@endif,
        placeholder: @js($placeholder),
        searchPlaceholder: @js($searchPlaceholder),
        emptyText: @js($empty),
        options: [],
        get selected() {
            return this.options.find((o) => o.value === String(this.value)) ?? null;
        },
        get display() {
            if (this.value !== '' && this.value !== null && this.selected) {
                return this.selected.label;
            }
            return this.placeholder;
        },
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) {
                return this.options;
            }
            return this.options.filter((o) => o.label.toLowerCase().includes(q) || String(o.value).toLowerCase().includes(q));
        },
        parseOptions() {
            const select = this.$el.querySelector('select[data-searchable-select]');
            if (!select) {
                return;
            }
            this.options = Array.from(select.options)
                .filter((o) => !(o.value === '' && o.hasAttribute('disabled')))
                .map((o) => ({ value: o.value, label: o.textContent.trim() }));
        },
        pick(option) {
            this.value = option.value;
            const select = this.$el.querySelector('select[data-searchable-select]');
            if (select) {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            this.open = false;
            this.query = '';
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.parseOptions();
                this.active = 0;
                this.query = '';
                this.$nextTick(() => this.$refs.search?.focus());
            }
        },
        selectActive() {
            const list = this.filtered;
            if (list[this.active] ?? null) {
                this.pick(list[this.active]);
            }
        },
        onKeydown(e) {
            const list = this.filtered;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.active = Math.min(this.active + 1, list.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.active = Math.max(this.active - 1, 0);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.selectActive();
            } else if (e.key === 'Escape') {
                this.open = false;
            }
        },
    }"
    class="relative {{ $containerClass }}"
>
    <select
        {{ $attributes->whereDoesntStartWith('wire:model')->except('class')->merge(['class' => 'sr-only']) }}
        @if ($wireModel) name="{{ $wireModel }}" @endif
        data-searchable-select
        tabindex="-1"
        aria-hidden="true"
    >
        {{ $slot }}
    </select>

    <div wire:ignore>
        <button
            type="button"
            @click="toggle()"
            :aria-expanded="open"
            class="flex w-full items-center justify-between gap-2 border border-zinc-200 bg-white px-3 text-left text-zinc-700 shadow-xs transition-colors hover:border-zinc-300 dark:border-white/10 dark:bg-white/10 dark:text-zinc-300 dark:hover:border-white/20 {{ $sizeClasses }}"
        >
            <span class="truncate" :class="{ 'text-zinc-400 dark:text-zinc-400': value === '' || value === null }" x-text="display"></span>
            <flux:icon name="chevron-down" variant="micro" class="size-4 shrink-0 text-zinc-400 transition-transform" :class="{ 'rotate-180': open }" />
        </button>

        <div
            x-show="open"
            @click.outside="open = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-800"
        >
            <div class="border-b border-zinc-100 p-2 dark:border-white/10">
                <input
                    x-ref="search"
                    type="text"
                    x-model="query"
                    @keydown="onKeydown($event)"
                    :placeholder="searchPlaceholder"
                    class="w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-sm text-zinc-700 outline-none transition-colors focus:border-accent dark:border-white/10 dark:bg-white/5 dark:text-zinc-200"
                />
            </div>
            <ul class="max-h-56 overflow-auto p-1" @keydown="onKeydown($event)">
                <template x-for="(option, i) in filtered" :key="option.value">
                    <li>
                        <button
                            type="button"
                            @click="pick(option)"
                            @mouseenter="active = i"
                            class="flex w-full items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-left text-sm transition-colors"
                            :class="i === active ? 'bg-accent/10 text-accent' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-white/5'"
                        >
                            <span class="truncate" x-text="option.label"></span>
                            <flux:icon x-show="option.value === String(value)" name="check" variant="micro" class="size-3.5 shrink-0" />
                        </button>
                    </li>
                </template>
                <li x-show="filtered.length === 0" class="px-2.5 py-2 text-sm text-zinc-400" x-text="emptyText"></li>
            </ul>
        </div>
    </div>
</div>
