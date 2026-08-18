@props([
    'candidate' => null,
    'candidateId' => null,
    'pools' => collect(),
])

@php
    $candidateId = $candidateId ?? $candidate?->id;

    $statusLabels = [
        'saved' => 'Saved',
        'shortlisted' => 'Shortlisted',
        'contacted' => 'Contacted',
        'interviewing' => 'Interviewing',
        'offered' => 'Offered',
        'rejected' => 'Rejected',
    ];

    $memberships = $candidateId
        ? \App\Models\TalentPoolMember::query()
            ->whereIn('talent_pool_id', $pools->pluck('id'))
            ->where('candidate_id', $candidateId)
            ->get()
            ->keyBy('talent_pool_id')
        : collect();
@endphp

<flux:dropdown>
    <flux:button size="xs" variant="ghost">
        <flux:icon name="bookmark" variant="micro" />
        Save
    </flux:button>
    <flux:menu>
        @forelse ($pools as $pool)
            @php $member = $memberships->get($pool->id); @endphp
            <div wire:key="sp-{{ $pool->id }}" class="border-b border-zinc-100 px-3 py-2.5 last:border-b-0 dark:border-white/10">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-semibold">{{ $pool->name }}</span>
                    @if ($member)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                            <flux:icon name="check" variant="micro" class="size-2.5" />
                            {{ $statusLabels[$member->status] ?? \Illuminate\Support\Str::title($member->status) }}
                        </span>
                    @endif
                </div>
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach ($statusLabels as $statusValue => $statusLabel)
                        <button
                            type="button"
                            wire:click="setCandidateStatus({{ $candidateId }}, {{ $pool->id }}, '{{ $statusValue }}')"
                            @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-medium transition',
                                'bg-accent text-white' => $member?->status === $statusValue,
                                'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' => $member?->status !== $statusValue,
                            ])
                        >
                            {{ $statusLabel }}
                        </button>
                    @endforeach
                </div>
                @if ($member)
                    <button type="button" wire:click="removeFromPool({{ $candidateId }}, {{ $pool->id }})" class="mt-1.5 text-[10px] font-medium text-zinc-400 transition hover:text-red-500">
                        Remove from pool
                    </button>
                @endif
            </div>
        @empty
            <div class="px-3 py-2 text-xs text-zinc-500">No pools yet — create one below.</div>
        @endforelse
        <flux:menu.separator />
        <div class="grid gap-2 px-3 py-2">
            <flux:input wire:model="newPoolName" size="xs" placeholder="New pool name" class="w-full" x-on:keydown.enter.prevent="$wire.createPool()" />
            <flux:button size="xs" wire:click="createPool" class="w-full justify-center">Create pool</flux:button>
        </div>
    </flux:menu>
</flux:dropdown>
