@php
    $lifetime = (float) config('billing.developer.verification.lifetime_price', 17);
    $monthly = (float) config('billing.developer.verification.monthly_price', 8);
    $advantages = [
        'Priority hiring consideration',
        'Direct chat with verified members',
        'See who views your profile',
    ];
@endphp

{{-- Expanded sidebar: verification upsell card. --}}
<div class="in-data-flux-sidebar-collapsed-desktop:hidden mx-3 mb-2">
    {{-- Gradient border: 3px blue → teal ring wraps the whole card. --}}
    <div class="rounded-xl bg-gradient-to-br from-blue-600 via-sky-500 to-teal-400 p-[3px] shadow-md">
        <div class="overflow-hidden rounded-[9px] bg-white dark:bg-zinc-900/95">
            <div class="flex items-center gap-2.5 px-3 pt-3 pb-2.5">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-teal-500 text-white shadow-sm">
                    <flux:icon name="check-badge" variant="solid" class="size-4" />
                </span>
                <div class="min-w-0">
                    <div class="truncate text-[13px] font-bold leading-tight text-zinc-900 dark:text-white">Get Verified</div>
                    <div class="text-[11px] leading-tight text-zinc-500 dark:text-zinc-400">Earn trust. Get hired faster.</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-px overflow-hidden border-y border-zinc-100 bg-zinc-100 text-center dark:border-white/5 dark:bg-white/5">
                <div class="bg-zinc-50/80 px-2 py-2 dark:bg-white/[3%]">
                    <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">${{ number_format($lifetime, 0) }}</div>
                    <div class="mt-px text-[10px] font-medium uppercase tracking-wide text-zinc-400 dark:text-zinc-500">once · forever</div>
                </div>
                <div class="bg-white px-2 py-2 dark:bg-transparent">
                    <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">${{ number_format($monthly, 0) }}<span class="text-[10px] font-semibold text-zinc-400">/mo</span></div>
                    <div class="mt-px text-[10px] font-medium uppercase tracking-wide text-zinc-400 dark:text-zinc-500">monthly</div>
                </div>
            </div>

            <ul class="grid gap-1.5 px-3 py-2.5">
                @foreach ($advantages as $advantage)
                    <li class="flex items-start gap-1.5 text-[11px] leading-snug text-zinc-600 dark:text-zinc-300">
                        <flux:icon name="check" variant="micro" class="mt-px size-3 shrink-0 text-teal-500 dark:text-teal-400" />
                        {{ $advantage }}
                    </li>
                @endforeach
            </ul>

            <div class="px-3 pb-3">
                <a
                    href="{{ route('verify') }}"
                    wire:navigate
                    class="flex h-9 w-full items-center justify-center gap-1.5 rounded-lg bg-gradient-to-r from-blue-600 to-teal-500 text-xs font-bold text-white shadow-sm transition hover:from-blue-700 hover:to-teal-600"
                >
                    Get Verified
                    <flux:icon name="arrow-right" variant="micro" class="size-3" />
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Collapsed rail: compact verified icon button. --}}
<div class="not-in-data-flux-sidebar-collapsed-desktop:hidden mb-2 flex justify-center px-2">
    <a
        href="{{ route('verify') }}"
        wire:navigate
        title="Get Verified · ${{ number_format($lifetime, 0) }} once or ${{ number_format($monthly, 0) }}/month"
        class="relative flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 via-sky-500 to-teal-400 text-white shadow-md transition hover:brightness-110"
    >
        <flux:icon name="check-badge" variant="solid" class="size-4" />
        <span class="absolute -right-0.5 -top-0.5 size-2 rounded-full bg-white ring-1 ring-blue-600/40"></span>
    </a>
</div>
