@props([
    'sidebar' => false,
])

@php
    $alt = config('app.name', 'ProoDev');
@endphp

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('images/logo-black.png') }}" alt="{{ $alt }}" class="app-logo-wordmark-black hidden h-6 w-auto" />
            <img src="{{ asset('images/logo-white.png') }}" alt="{{ $alt }}" class="app-logo-wordmark-white hidden h-6 w-auto" />
            <img src="{{ asset('images/short-black.png') }}" alt="{{ $alt }}" class="app-logo-short-black hidden h-6 w-auto" />
            <img src="{{ asset('images/short-white.png') }}" alt="{{ $alt }}" class="app-logo-short-white hidden h-6 w-auto" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('images/logo-black.png') }}" alt="{{ $alt }}" class="app-logo-wordmark-black hidden h-6 w-auto" />
            <img src="{{ asset('images/logo-white.png') }}" alt="{{ $alt }}" class="app-logo-wordmark-white hidden h-6 w-auto" />
            <img src="{{ asset('images/short-black.png') }}" alt="{{ $alt }}" class="app-logo-short-black hidden h-6 w-auto" />
            <img src="{{ asset('images/short-white.png') }}" alt="{{ $alt }}" class="app-logo-short-white hidden h-6 w-auto" />
        </x-slot>
    </flux:brand>
@endif
