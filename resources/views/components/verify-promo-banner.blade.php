@props(['price' => null])

@php
    $price ??= (float) config('billing.developer.verification.price', 17);
    $monthlyPrice ??= (float) config('billing.developer.verification.monthly_price', 8);
    $advantages = [
        'Verified badge on your DevID',
        'Direct chat with verified members',
        'Priority hiring & job matching',
        'See who viewed your profile',
    ];
@endphp

{{-- Expanded sidebar: professional promo card with 3px blue→teal gradient border. --}}
<div class="in-data-flux-sidebar-collapsed-desktop:hidden mx-3 mb-1">
    <div class="rounded-xl bg-gradient-to-br from-blue-600 via-sky-500 to-teal-400 p-[3px] shadow-md">
        <div class="overflow-hidden rounded-[9px] bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-2 px-3 pt-3">
                <div class="flex items-center gap-1.5">
                    <span class="flex size-5 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-teal-500 text-white">
                        <flux:icon name="check-badge" variant="solid" class="size-3" />
                    </span>
                    <div class="text-xs font-bold leading-tight text-zinc-900 dark:text-white">Get Verified</div>
                </div>
                <a href="{{ route('verify') }}" wire:navigate class="text-[10px] font-semibold text-blue-600 hover:underline dark:text-teal-400">Learn more</a>
            </div>

            <p class="mt-1.5 px-3 text-[11px] leading-snug text-zinc-500 dark:text-zinc-400">
                Unlock chats, trust and priority hiring.
            </p>

            <div class="mt-2 grid grid-cols-2 gap-2 px-3">
                <div class="rounded-lg border border-zinc-200 p-2 text-center dark:border-zinc-700">
                    <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">${{ number_format($price, 0) }}</div>
                    <div class="text-[9px] font-medium uppercase tracking-wide text-zinc-400">once · forever</div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-2 text-center dark:border-zinc-700">
                    <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">${{ number_format($monthlyPrice, 0) }}</div>
                    <div class="text-[9px] font-medium uppercase tracking-wide text-zinc-400">per month</div>
                </div>
            </div>

            <ul class="mt-2.5 grid gap-1 px-3">
                @foreach ($advantages as $advantage)
                    <li class="flex items-start gap-1.5 text-[10px] leading-snug text-zinc-600 dark:text-zinc-300">
                        <flux:icon name="check" variant="micro" class="mt-px size-2.5 shrink-0 text-teal-500" />
                        {{ $advantage }}
                    </li>
                @endforeach
            </ul>

            <div class="p-3">
                <a
                    href="{{ route('verify') }}"
                    wire:navigate
                    class="inline-flex h-7 w-full items-center justify-center gap-1 rounded-lg bg-gradient-to-r from-blue-600 to-teal-500 text-xs font-bold text-white transition hover:opacity-90"
                >
                    Get Verified
                    <flux:icon name="arrow-right" variant="micro" class="size-3" />
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Collapsed rail: compact verified icon with the same 3px gradient ring. --}}
<div class="not-in-data-flux-sidebar-collapsed-desktop:hidden mb-1 flex justify-center px-2">
    <a
        href="{{ route('verify') }}"
        wire:navigate
        title="Get Verified — ${{ number_format($price, 0) }} once or ${{ number_format($monthlyPrice, 0) }}/mo"
        class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-teal-400 p-[3px] shadow-md transition hover:scale-105"
    >
        <span class="flex size-full items-center justify-center rounded-[9px] bg-white dark:bg-zinc-900">
            <flux:icon name="check-badge" variant="solid" class="size-4 text-blue-600 dark:text-teal-400" />
        </span>
    </a>
</div>