@props(['method' => null])

@php
    $method = $method instanceof \App\Enums\PaymentMethod
        ? $method
        : \App\Enums\PaymentMethod::tryFrom((string) $method);
@endphp

@if ($method === \App\Enums\PaymentMethod::Flutterwave)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg bg-white px-2.5 py-1.5 ring-1 ring-zinc-200']) }}>
        <img src="{{ asset('images/payments/flutterwave.png') }}" alt="Flutterwave" class="h-6 w-auto max-w-28 object-contain" />
    </span>
@elseif ($method === \App\Enums\PaymentMethod::Pesapal)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg bg-white px-2.5 py-1.5 ring-1 ring-zinc-200']) }}>
        <img src="{{ asset('images/payments/pesapal.png') }}" alt="Pesapal" class="h-6 w-auto max-w-28 object-contain" />
    </span>
@elseif ($method === \App\Enums\PaymentMethod::WorldRemit)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg bg-white px-2.5 py-1.5 ring-1 ring-zinc-200']) }}>
        <img src="{{ asset('images/payments/worldremit.png') }}" alt="WorldRemit" class="h-6 w-auto max-w-28 object-contain" />
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex size-7 items-center justify-center rounded-lg bg-white ring-1 ring-zinc-200']) }}>
        <flux:icon name="{{ $method?->icon() ?? 'banknotes' }}" variant="solid" class="size-4 text-accent" />
    </span>
@endif
