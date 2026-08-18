<div class="grid min-w-0 max-w-full gap-5" aria-hidden="true">
    <div class="flex items-start gap-4">
        <flux:skeleton class="size-14 shrink-0 rounded-xl" animate="shimmer" />
        <div class="min-w-0 flex-1 space-y-2.5">
            <flux:skeleton.line size="lg" class="max-w-[55%]" animate="shimmer" />
            <flux:skeleton.line size="base" class="max-w-[75%]" animate="shimmer" />
            <flux:skeleton.line size="base" class="max-w-[40%]" animate="shimmer" />
        </div>
    </div>

    <div class="space-y-2.5">
        <flux:skeleton.line size="base" animate="shimmer" />
        <flux:skeleton.line size="base" class="max-w-[92%]" animate="shimmer" />
    </div>

    <div class="grid grid-cols-4 gap-2">
        @for ($i = 0; $i < 4; $i++)
            <flux:skeleton class="h-16 rounded-lg" animate="shimmer" />
        @endfor
    </div>

    <div class="space-y-2.5">
        <div class="flex items-center gap-2">
            <flux:skeleton class="size-6 shrink-0 rounded-md" animate="shimmer" />
            <flux:skeleton.line size="base" class="max-w-[70%]" animate="shimmer" />
        </div>
        <div class="flex items-center gap-2">
            <flux:skeleton class="size-6 shrink-0 rounded-md" animate="shimmer" />
            <flux:skeleton.line size="base" class="max-w-[55%]" animate="shimmer" />
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:skeleton class="size-8 rounded-lg" animate="shimmer" />
        <flux:skeleton class="size-8 rounded-lg" animate="shimmer" />
        <flux:skeleton class="size-8 rounded-lg" animate="shimmer" />
    </div>
</div>
