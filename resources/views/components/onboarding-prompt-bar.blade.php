@props(['user'])

@php
    $percent = app(\App\Services\ProfileCompletionService::class)->percentage($user);
    $finished = $user->hasCompletedOnboarding();
    $target = $finished ? route('passport', $user->handle()) : route('onboarding');
    $ctaLabel = $finished ? 'Complete profile' : 'Finish setup';
@endphp

@if ($percent <= 75)
<div {{ $attributes->merge(['class' => 'border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950']) }}>
    <div class="flex items-center gap-3 px-4 py-2 sm:px-6">
        <flux:icon name="sparkles" variant="solid" class="size-4 shrink-0 text-accent" />

        <div class="min-w-0 flex-1">
            <a href="{{ $target }}" wire:navigate class="group flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <span class="text-xs font-bold text-zinc-900 group-hover:text-accent dark:text-zinc-100">
                    Add your project links and GitHub repo
                </span>
                <span class="hidden truncate text-xs text-zinc-500 sm:inline dark:text-zinc-400">
                    to extract your bio, skills and evidence.
                </span>
            </a>

            <div class="mt-1 flex items-center gap-2">
                <div class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-200/80 sm:w-44 dark:bg-zinc-700/70">
                    <div class="h-full rounded-full bg-accent transition-all duration-500" style="width: {{ $percent }}%"></div>
                </div>
                <span class="text-[10px] font-bold tabular-nums text-accent">{{ $percent }}%</span>
            </div>
        </div>

        <a
            href="{{ $target }}"
            wire:navigate
            class="inline-flex h-7 shrink-0 items-center gap-1 rounded-full bg-accent px-3.5 text-xs font-bold text-white shadow-sm transition hover:opacity-90"
        >
            {{ $ctaLabel }}
            <flux:icon name="arrow-right" variant="micro" class="size-3" />
        </a>
    </div>
</div>
@endif
