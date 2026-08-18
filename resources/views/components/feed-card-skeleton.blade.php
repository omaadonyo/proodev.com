@props(['compact' => false, 'dense' => false])

@if ($dense)
    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:skeleton class="size-8 shrink-0 rounded-lg" animate="shimmer" />
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <flux:skeleton.line size="base" class="max-w-[20%]" animate="shimmer" />
                <flux:skeleton class="h-5 w-20 shrink-0 rounded-full" animate="shimmer" />
                <flux:skeleton.line size="base" class="max-w-[35%]" animate="shimmer" />
            </div>
        </div>
        <flux:skeleton.line size="base" class="hidden w-40 shrink-0 md:block" animate="shimmer" />
    </div>
@else
<div class="rounded-xl border border-zinc-200 bg-white p-[calc(var(--spacing)*1)] dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex items-center gap-3">
        <flux:skeleton class="size-12 shrink-0 rounded-xl" animate="shimmer" />
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <flux:skeleton.line size="base" class="max-w-[30%]" animate="shimmer" />
                <flux:skeleton class="h-5 w-24 rounded-full" animate="shimmer" />
                <flux:skeleton.line size="base" class="max-w-[15%]" animate="shimmer" />
            </div>
            <div class="mt-1.5 flex items-center gap-x-3">
                <flux:skeleton.line size="base" class="max-w-[18%]" animate="shimmer" />
                <flux:skeleton.line size="base" class="max-w-[22%]" animate="shimmer" />
                <flux:skeleton.line size="base" class="max-w-[12%]" animate="shimmer" />
            </div>
        </div>
    </div>

    <div class="{{ $compact ? 'mt-3 grid gap-3' : 'mt-3 grid gap-3 sm:grid-cols-[minmax(0,1fr)_230px] sm:items-start' }}">
        <div class="min-w-0 pl-[3.75rem]">
            <flux:skeleton.line size="base" class="max-w-[80%]" animate="shimmer" />
            <div class="mt-1.5 space-y-1.5">
                <flux:skeleton.line size="base" class="max-w-[95%]" animate="shimmer" />
            </div>
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                <flux:skeleton class="h-5 w-16 rounded-full" animate="shimmer" />
                <flux:skeleton class="h-5 w-16 rounded-full" animate="shimmer" />
                <flux:skeleton class="h-5 w-14 rounded-full" animate="shimmer" />
            </div>
        </div>

        <div class="{{ $compact ? 'flex flex-col gap-3' : 'flex flex-col gap-3 sm:border-l sm:border-zinc-100 sm:pl-3 dark:sm:border-zinc-700/60' }}">
            <flux:skeleton.line size="base" class="max-w-[45%]" animate="shimmer" />
            <flux:skeleton class="mt-1 h-1.5 rounded-full" animate="shimmer" />
        </div>
    </div>
</div>
@endif
