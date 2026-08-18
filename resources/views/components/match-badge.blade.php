@props(['pct' => 0, 'metric' => 'skills'])

@if ($pct >= 0)
    @php
        $isPerfect = $pct === 100;
        $metricLabel = $metric === 'tech' ? 'skills & technologies' : 'skills';
        $tone = $isPerfect
            ? 'text-white'
            : ($pct >= 70
                ? 'bg-emerald-500 text-white'
                : ($pct >= 40
                    ? 'bg-amber-500 text-white'
                    : 'bg-zinc-600 text-white dark:bg-zinc-700'));
    @endphp
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-tight tabular-nums '.$tone]) }}
        title="{{ $isPerfect ? 'Perfect match — covers all selected '.$metricLabel : $pct.'% of the posting\'s '.$metricLabel }}"
        style="{{ $isPerfect ? 'background: linear-gradient(135deg, #10b981, #14b8a6)' : '' }}">
        @if ($isPerfect)
            <flux:icon name="check-badge" variant="micro" class="size-2.5" />
        @endif
        {{ $pct }}%
    </span>
@endif
