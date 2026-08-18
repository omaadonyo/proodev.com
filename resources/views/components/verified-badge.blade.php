@props([
    'user' => null,
    'compact' => false,
])

@if ($user && $user->isVerified())
    <span
        title="Verified"
        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#3750eb]/10 px-2 py-0.5 text-[11px] font-semibold text-[#3750eb] dark:text-[#8f9dff]"
    >
        <flux:icon name="shield-check" variant="micro" />
        @if (! $compact)
            Verified
        @endif
    </span>
@endif
