@props([
    'iconVariant' => 'mini',
    'size' => null,
])

@php
$attributes = $attributes->merge([
    'variant' => 'subtle',
    'class' => '-me-1',
    'square' => true,
    'size' => null,
]);
@endphp

<flux:button
    :$attributes
    :size="$size === 'sm' || $size === 'xs' ? 'xs' : 'sm'"
    x-data="{ open: false }"
    x-on:click="open = ! open; $el.closest('[data-flux-input]').querySelector('input').setAttribute('type', open ? 'text' : 'password')"
    aria-label="{{ __('Toggle password visibility') }}"
>
    <flux:icon.eye-slash :variant="$iconVariant" x-show="open" x-cloak class="shrink-0 [:where(&)]:size-5" />
    <flux:icon.eye :variant="$iconVariant" x-show="! open" class="shrink-0 [:where(&)]:size-5" />
</flux:button>
