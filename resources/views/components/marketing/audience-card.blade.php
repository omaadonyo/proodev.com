@props([
    'title',
    'audience',
    'items' => [],
    'cta',
    'ctaHref',
    'accent' => false,
])

<div @class([
    'flex flex-col rounded-xl border p-6',
    'border-[#3750eb]/30 bg-gradient-to-br from-[#f1f4ff] to-white shadow-lg shadow-[#3750eb]/10 dark:border-[#3750eb]/30 dark:from-[#3750eb]/15 dark:to-zinc-950/60' => $accent,
    'border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-950/60' => ! $accent,
])>
    <div class="text-xs font-bold uppercase tracking-widest {{ $accent ? 'text-[#3750eb] dark:text-[#8f9dff]' : 'text-zinc-400' }}">{{ $audience }}</div>

    <h3 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</h3>

    <ul class="mt-5 grid gap-2 text-sm text-zinc-600 dark:text-zinc-300">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                <span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                {{ $item }}
            </li>
        @endforeach
    </ul>

    <a href="{{ $ctaHref }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90">
        {{ $cta }}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
    </a>
</div>
