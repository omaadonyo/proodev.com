@props(['user', 'name' => null, 'modalOnly' => false])

@php
    $modalName = $name ?: 'passport-'.$user->id;
@endphp

<div class="contents">
    @unless ($modalOnly)
        @if ($slot->isNotEmpty())
            <button
                type="button"
                @click="$flux.modal('{{ $modalName }}').show()"
                class="block w-full text-left"
            >
                {{ $slot }}
            </button>
        @else
            <button
                type="button"
                @click="$flux.modal('{{ $modalName }}').show()"
                class="inline-flex items-center gap-1 text-xs font-medium text-accent hover:underline"
            >
                View passport
                <flux:icon name="arrow-up-right" variant="micro" class="size-3" />
            </button>
        @endif
    @endunless

    <flux:modal variant="flyout" :name="$modalName" class="w-full max-w-md overflow-hidden">
        <livewire:passport-flyout-body :userId="$user->id" :key="'pfb-'.$modalName" />
    </flux:modal>
</div>
