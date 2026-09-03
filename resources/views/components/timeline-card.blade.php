@props(['event', 'compact' => false, 'dense' => false])

@php
    $icons = [
        'project-published' => 'folder',
        'package-released' => 'archive-box',
        'problem-solved' => 'wrench-screwdriver',
        'badge-earned' => 'trophy',
        'vouch-received' => 'shield-check',
        'article-published' => 'document-text',
        'architecture-showcase' => 'building-library',
        'learning-milestone' => 'academic-cap',
        'achievement-verified' => 'check-badge',
        'project-launch' => 'rocket-launch',
        'open-source-contribution' => 'code-bracket',
        'level-up' => 'arrow-trending-up',
        'skill-verified' => 'finger-print',
        'journal-published' => 'book-open',
        'milestone-reached' => 'flag',
        'joined' => 'sparkles',
    ];

    $targetUrl = null;

    if ($event->target) {
        $targetUrl = match (true) {
            $event->target instanceof \App\Models\Project => route('projects.show', $event->target),
            default => null,
        };
    }
@endphp

@php
    $presenceEnabled = \App\Support\FeatureFlags::publicPresenceEnabled();
@endphp

@if ($dense)
    <div
        @click="$flux.modal('open-{{ $event->id }}').show()"
        class="group flex cursor-pointer items-center gap-3 rounded-lg bg-zinc-100 px-3 py-1.5 transition hover:bg-zinc-200 dark:bg-white/5 dark:hover:bg-white/10"
    >
        <a href="{{ route('devid', $event->user->handle()) }}" wire:navigate @click.stop class="relative shrink-0">
            <flux:avatar :src="$event->user->avatarUrl()" :alt="$event->user->name" size="sm" class="rounded-lg transition group-hover:opacity-75" />
            @if ($presenceEnabled && $event->user->isOnline())
                <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
            @endif
        </a>

        <div class="flex min-w-0 flex-1 items-center gap-2">
            <a href="{{ route('devid', $event->user->handle()) }}" wire:navigate @click.stop class="shrink-0 text-sm font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                {{ $event->user->name }}
            </a>
            <x-verified-badge :user="$event->user" compact />
            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">
                <flux:icon :name="$icons[$event->type->value] ?? 'bolt'" variant="mini" class="size-3" />
                {{ $event->type->label() }}
            </span>
            <span class="truncate text-sm text-zinc-600 dark:text-zinc-300">
                {{ $event->title }}
            </span>
        </div>

        <div class="hidden shrink-0 items-center gap-2 text-xs text-zinc-400 md:flex">
            <span class="tabular-nums">Lv {{ $event->user->level() }}</span>
            <span class="tabular-nums">{{ number_format($event->user->experience_points) }} XP</span>
            <span>· {{ $event->occurred_at->diffForHumans() }}</span>
        </div>

        <x-passport-flyout :name="'open-'.$event->id" :user="$event->user" modal-only />
    </div>
@elseif ($compact)
    <div
        @click="$flux.modal('open-{{ $event->id }}').show()"
        class="group flex h-full cursor-pointer flex-col rounded-xl bg-zinc-100 p-4 transition hover:bg-zinc-200 dark:bg-white/5 dark:hover:bg-white/10"
    >
        <div class="flex items-start gap-3">
            <a href="{{ route('devid', $event->user->handle()) }}" wire:navigate @click.stop class="relative shrink-0">
                <flux:avatar :src="$event->user->avatarUrl()" :alt="$event->user->name" size="lg" class="rounded-xl transition group-hover:opacity-75" />
                @if ($presenceEnabled && $event->user->isOnline())
                    <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <a href="{{ route('devid', $event->user->handle()) }}" wire:navigate @click.stop class="text-sm font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                        {{ $event->user->name }}
                    </a>
                    <x-verified-badge :user="$event->user" compact />
                    <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">
                        <flux:icon :name="$icons[$event->type->value] ?? 'bolt'" variant="mini" class="size-3" />
                        {{ $event->type->label() }}
                    </span>
                </div>

                <h3 class="mt-1 line-clamp-2 text-[15px] font-semibold leading-snug text-zinc-900 dark:text-zinc-100">
                    @if ($targetUrl)
                        <a href="{{ $targetUrl }}" wire:navigate @click.stop class="hover:text-accent hover:underline">{{ $event->title }}</a>
                    @else
                        {{ $event->title }}
                    @endif
                </h3>
            </div>
        </div>

        @if ($event->description)
            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $event->description }}</p>
        @endif

        @if ($event->user->skills->isNotEmpty())
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach ($event->user->skills->take(3) as $skill)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                        <x-tech-logo :name="$skill->name" class="size-3.5 shrink-0" />
                        {{ $skill->name }}
                    </span>
                @endforeach
            </div>
        @endif

        @if ($targetUrl && (($event->data['title'] ?? null) || ($event->data['tagline'] ?? null)))
            <div class="mt-3 rounded-xl bg-zinc-100 px-3 py-2 dark:bg-white/5">
                @if (($event->data['title'] ?? null))
                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event->data['title'] }}</div>
                @endif
                @if (($event->data['tagline'] ?? null))
                    <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ \App\Support\Markdown::plain($event->data['tagline']) }}</div>
                @endif
            </div>
        @endif

        <div class="mt-auto pt-4">
            <div class="mb-1.5 flex items-center justify-between text-[11px] text-zinc-400">
                <span>Level progress</span>
                <span class="font-medium text-zinc-500 dark:text-zinc-400">Lv {{ $event->user->level() }} · {{ number_format($event->user->experience_points) }} XP</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                <div class="h-full rounded-full bg-zinc-900 dark:bg-white" style="width: {{ $event->user->levelProgress() }}%"></div>
            </div>
        </div>

        <x-passport-flyout :name="'open-'.$event->id" :user="$event->user" modal-only />
    </div>
@else
    <div
        @click="$flux.modal('open-{{ $event->id }}').show()"
        class="group cursor-pointer rounded-xl bg-zinc-100 p-[calc(var(--spacing)*1)] transition hover:bg-zinc-200 dark:bg-white/5 dark:hover:bg-white/10"
    >
    <div class="flex items-start gap-3">
        <a href="{{ route('devid', $event->user->handle()) }}" wire:navigate @click.stop class="relative shrink-0">
            <flux:avatar :src="$event->user->avatarUrl()" :alt="$event->user->name" size="lg" class="rounded-xl transition group-hover:opacity-75" />
            @if ($presenceEnabled && $event->user->isOnline())
                <span class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800"></span>
            @endif
        </a>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <a href="{{ route('devid', $event->user->handle()) }}" wire:navigate @click.stop class="text-sm font-semibold text-zinc-900 hover:underline dark:text-zinc-100">
                    {{ $event->user->name }}
                </a>
                <x-verified-badge :user="$event->user" compact />
                @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $event->user->isOnline())
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400" title="Online now">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                        Online
                    </span>
                @endif
                <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">
                    <flux:icon :name="$icons[$event->type->value] ?? 'bolt'" variant="mini" class="size-3" />
                    {{ $event->type->label() }}
                </span>
                <span class="text-xs text-zinc-400">· {{ $event->occurred_at->diffForHumans() }}</span>
            </div>

            <h3 class="mt-1 text-[15px] font-semibold leading-snug text-zinc-900 dark:text-zinc-100">
                @if ($targetUrl)
                    <a href="{{ $targetUrl }}" wire:navigate @click.stop class="hover:text-accent hover:underline">{{ $event->title }}</a>
                @else
                    {{ $event->title }}
                @endif
            </h3>

            @if ($event->description)
                <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $event->description }}</p>
            @endif

            @if ($event->user->skills->isNotEmpty())
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    @foreach ($event->user->skills->take(3) as $skill)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                            <x-tech-logo :name="$skill->name" class="size-3.5 shrink-0" />
                            {{ $skill->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="shrink-0 text-right">
            <div class="inline-flex items-center gap-1 text-xs font-medium text-zinc-600 dark:text-zinc-300" title="Level">
                <flux:icon name="arrow-trending-up" variant="mini" class="size-3.5 text-accent" />
                Lv {{ $event->user->level() }} · {{ $event->user->levelTitle() }}
            </div>
            <div class="mt-0.5 inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400" title="Experience points">
                <flux:icon name="sparkles" variant="mini" class="size-3.5" />
                {{ number_format($event->user->experience_points) }} XP
            </div>
            @if ($event->user->reputation_score > 0)
                <div class="mt-0.5 inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400" title="Reputation">
                    <flux:icon name="shield-check" variant="mini" class="size-3.5" />
                    {{ $event->user->reputation_score }}
                </div>
            @endif
            @if ($event->user->streak_count > 0)
                <div class="mt-0.5 inline-flex items-center gap-1 text-xs text-orange-500" title="Streak">
                    <flux:icon name="fire" variant="mini" class="size-3.5" />
                    {{ $event->user->streak_count }}
                </div>
            @endif
        </div>
    </div>

    @if ($targetUrl)
        <div class="mt-3 grid gap-3">
            <div class="min-w-0 pl-[3.75rem]">
                @if (($event->data['title'] ?? null))
                    <div class="mt-2.5 rounded-xl bg-zinc-100 px-3 py-2 dark:bg-white/5">
                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event->data['title'] }}</div>
                        @if (($event->data['tagline'] ?? null))
                            <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ \App\Support\Markdown::plain($event->data['tagline']) }}</div>
                        @endif
                    </div>
                @endif

                <div class="mt-2.5 flex items-center gap-4">
                    <a href="{{ $targetUrl }}" wire:navigate @click.stop class="inline-flex items-center gap-1 text-xs font-medium text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        Open details
                        <flux:icon name="arrow-right" variant="micro" class="size-3" />
                    </a>
                </div>
            </div>
        </div>
    @endif

    <x-passport-flyout :name="'open-'.$event->id" :user="$event->user" modal-only />
    </div>
@endif
