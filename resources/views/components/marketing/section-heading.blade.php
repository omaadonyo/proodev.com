@props([
    'eyebrow' => null,
    'title' => null,
    'sub' => null,
    'center' => true,
])

<div @class(['max-w-2xl', 'mx-auto text-center' => $center])>
    @if ($eyebrow)
        <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">{{ $eyebrow }}</p>
    @endif

    <h2 {{ $attributes->class(['mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white']) }}>
        {{ $title }}
    </h2>

    @if ($sub)
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ $sub }}</p>
    @endif

    @isset($slot)
        <div class="mt-4">{{ $slot }}</div>
    @endisset
</div>
