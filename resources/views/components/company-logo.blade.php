@props(['company', 'size' => 'md', 'rounded' => true])

@php
    $url = $company->logoUrl();
    $box = match ($size) {
        'sm' => 'size-8 text-xs',
        'lg' => 'size-12 text-lg',
        default => 'size-10 text-sm',
    };
    $radius = $rounded ? 'rounded-lg' : 'rounded-none';
@endphp

@if ($url)
    <img
        src="{{ $url }}"
        alt="{{ $company->name }} logo"
        loading="lazy"
        {{ $attributes->merge(['class' => $box.' '.$radius.' shrink-0 border border-zinc-200 object-cover dark:border-white/10']) }}
    />
@else
    <div {{ $attributes->merge(['class' => $box.' '.$radius.' flex shrink-0 items-center justify-center border-2 border-zinc-200 bg-black font-bold text-white dark:border-zinc-800 dark:bg-white dark:text-black']) }}>
        {{ \Illuminate\Support\Str::initials($company->name, true) }}
    </div>
@endif
