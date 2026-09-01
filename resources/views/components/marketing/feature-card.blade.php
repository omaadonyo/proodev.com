@props(['title', 'icon' => null, 'emoji' => null])

<div class="group relative rounded-lg border border-zinc-200 bg-white p-5 transition duration-300 hover:-translate-y-1 hover:border-[#3750eb]/50 hover:shadow-xl hover:shadow-[#3750eb]/10 dark:border-white/10 dark:bg-zinc-950/60 dark:hover:border-[#3750eb]/30 dark:hover:bg-white/[0.04]">
    @if ($emoji)
        <span class="inline-flex size-10 items-center justify-center rounded-lg bg-[#3750eb]/10 text-xl" aria-hidden="true">{{ $emoji }}</span>
    @elseif ($icon)
        <span class="inline-flex size-10 items-center justify-center rounded-lg bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $icon }}" clip-rule="evenodd"/></svg>
        </span>
    @endif

    <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>

    <div class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $slot }}</div>
</div>
