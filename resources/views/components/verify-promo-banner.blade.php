@props(['price' => null])

@php
    $price ??= (float) config('billing.developer.verification.price', 8);
    $advantages = [
        'Profile highly vetted by employers & companies',
        'First-priority hiring consideration',
        'Direct chat with verified members',
        'Direct job matching',
        'See who viewed your profile',
        'See when a company views or opens your CV',
    ];
@endphp

{{-- Expanded sidebar: compact promo card. --}}
<div class="in-data-flux-sidebar-collapsed-desktop:hidden mx-3 mb-1">
    <div class="verify-promo overflow-hidden rounded-lg px-3 py-2.5 text-white shadow-md">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5">
                <flux:icon name="check-badge" variant="solid" class="size-4 text-white" />
                <div class="text-xs font-bold leading-tight">Get Verified</div>
            </div>
            <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold backdrop-blur">
                ${{ number_format($price, 0) }} once
            </span>
        </div>

        <p class="mt-1 text-[11px] leading-snug text-white/75">Unlock trust, chats and priority hiring.</p>

        <ul class="mt-2 grid gap-1">
            @foreach ($advantages as $advantage)
                <li class="flex items-start gap-1.5 text-[10px] leading-snug text-white/90">
                    <flux:icon name="check" variant="micro" class="mt-px size-2.5 shrink-0 text-white" />
                    {{ $advantage }}
                </li>
            @endforeach
        </ul>

        <a
            href="{{ route('verify') }}"
            wire:navigate
            class="mt-2 inline-flex h-7 w-full items-center justify-center gap-1 rounded-md bg-white text-xs font-bold text-zinc-900 transition hover:bg-zinc-100"
        >
            Get Verified
            <flux:icon name="arrow-right" variant="micro" class="size-3" />
        </a>
    </div>
</div>

{{-- Collapsed rail: compact verified icon button. --}}
<div class="not-in-data-flux-sidebar-collapsed-desktop:hidden mb-1 flex justify-center px-2">
    <a
        href="{{ route('verify') }}"
        wire:navigate
        title="Get Verified — ${{ number_format($price, 0) }}"
        class="verify-promo flex size-9 items-center justify-center rounded-lg text-white shadow-md"
    >
        <flux:icon name="check-badge" variant="solid" class="size-4" />
    </a>
</div>
