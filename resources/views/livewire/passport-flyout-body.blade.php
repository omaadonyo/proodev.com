<?php

use App\Models\User;
use App\Models\UserAchievement;
use App\Services\EngineeringMagnitudeService;
use App\Services\DevIDViewService;
use App\Services\ProfileCompletionService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

new #[Lazy] class extends Component
{
    public int $userId;

    public function mount(): void
    {
        app(DevIDViewService::class)->record(
            User::findOrFail($this->userId),
            auth()->user(),
            request()->ip(),
        );
    }

    #[Computed]
    public function user(): User
    {
        return User::with('skills')->findOrFail($this->userId);
    }

    #[Computed]
    public function isOwn(): bool
    {
        return auth()->check() && auth()->id() === $this->userId;
    }

    public function connect(): void
    {
        $viewer = auth()->user();

        if (! $viewer?->isVerified()) {
            abort(403);
        }

        $conversation = $viewer->createConversationWith($this->user);

        if (! $conversation) {
            Flux::toast(variant: 'danger', text: 'Could not start a conversation right now.');

            return;
        }

        $this->redirectRoute('wirechat.chats.chat', $conversation, navigate: true);
    }

    #[Computed]
    public function projects()
    {
        return $this->user->projects()
            ->where('status', 'published')
            ->withCount('recognitions')
            ->latest('published_at')
            ->take(3)
            ->get();
    }

    #[Computed]
    public function vouches()
    {
        return $this->user->vouchesReceived()
            ->where('status', 'approved')
            ->with(['voucher', 'skill'])
            ->latest()
            ->take(3)
            ->get();
    }

    #[Computed]
    public function verifications()
    {
        return $this->user->verificationRequests()
            ->where('status', 'approved')
            ->latest('reviewed_at')
            ->get();
    }

    #[Computed]
    public function timeline()
    {
        return $this->user->timelineEvents()
            ->public()
            ->orderByDesc('occurred_at')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function achievements()
    {
        return UserAchievement::where('user_id', $this->userId)
            ->whereNotNull('awarded_at')
            ->with('achievement')
            ->orderByDesc('awarded_at')
            ->take(8)
            ->get();
    }

    #[Computed]
    public function journal()
    {
        return $this->user->journalEntries()
            ->publiclyVisible()
            ->orderByDesc('published_at')
            ->take(2)
            ->get();
    }

    #[Computed]
    public function magnitude(): array
    {
        return app(EngineeringMagnitudeService::class)->breakdown($this->user);
    }

    #[Computed]
    public function profileScore(): int
    {
        return app(ProfileCompletionService::class)->percentage($this->user);
    }

    public function placeholder(): string
    {
        return view('livewire.placeholder-passport-flyout-body')->render();
    }
};
?>

<div class="grid min-w-0 max-w-full gap-5">
    <div>
        <div class="flex items-start gap-4">
            <div class="relative shrink-0">
                <img
                    src="{{ $this->user->avatarUrl() }}"
                    alt="{{ $this->user->name }}"
                    class="size-14 rounded-xl object-cover ring-1 ring-zinc-200 dark:ring-white/10"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                />
                <div class="hidden size-14 items-center justify-center rounded-xl bg-black text-sm font-bold text-white ring-2 ring-zinc-200 dark:bg-white dark:text-black dark:ring-zinc-800">
                    {{ $this->user->initials() }}
                </div>
                @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $this->user->isOnline())
                    <span class="absolute -top-0.5 -right-0.5 size-3 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-800" title="Online now"></span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <div class="truncate text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->user->name }}</div>
                    <x-verified-badge :user="$this->user" />
                </div>
                @if ($this->user->handle() || $this->user->location)
                    <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                        @if ($this->user->handle()){{ '@'.$this->user->handle() }}@endif
                        @if ($this->user->handle() && $this->user->location) · @endif
                        @if ($this->user->location){{ $this->user->location }}@endif
                    </div>
                @endif
                @if ($this->user->headline)
                    <div class="mt-1 truncate text-sm text-zinc-700 dark:text-zinc-300">{{ $this->user->headline }}</div>
                @endif
                @if ($this->verifications->isNotEmpty())
                    <div class="mt-1.5 flex max-w-full flex-wrap gap-1">
                        @foreach ($this->verifications as $verification)
                            <span class="inline-flex max-w-full items-center gap-1 break-words rounded-full bg-emerald-400/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                <flux:icon name="check-badge" variant="micro" class="shrink-0" />
                                {{ $verification->label ?: $verification->type->label() }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if ($this->user->bio)
            <p class="mt-4 break-words text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($this->user->bio, 220) }}</p>
        @endif
    </div>

    @if ($this->user->skills->isNotEmpty())
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Capabilities</div>
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($this->user->skills->take(6) as $skill)
                    <span class="inline-flex max-w-full items-center gap-1.5 break-words rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">
                        <x-tech-logo :name="$skill->name" class="size-3.5 shrink-0" />
                        {{ $skill->name }}
                        @if ($skill->pivot->verified_at)
                            <flux:icon name="shield-check" variant="micro" class="shrink-0 text-emerald-500" />
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-zinc-200 dark:bg-white/10">
        <div class="bg-zinc-50 px-2 py-3 text-center dark:bg-zinc-900">
            <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ $this->user->level() }}</div>
            <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Level</div>
        </div>
        <div class="bg-zinc-50 px-2 py-3 text-center dark:bg-zinc-900">
            <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->user->reputation_score) }}</div>
            <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Reputation</div>
        </div>
        <div class="bg-zinc-50 px-2 py-3 text-center dark:bg-zinc-900">
            <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ $this->user->streak_count }}</div>
            <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Streak</div>
        </div>
        <div class="bg-zinc-50 px-2 py-3 text-center dark:bg-zinc-900">
            <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ $this->projects->count() }}</div>
            <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Projects</div>
        </div>
    </div>

    @if ($this->projects->isNotEmpty())
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Projects</div>
            <div class="mt-2 grid gap-2">
                @foreach ($this->projects as $project)
                    <a href="{{ route('projects.show', $project) }}" wire:navigate class="flex items-center gap-2.5 rounded-lg bg-zinc-100 px-3 py-2 transition hover:border-accent dark:bg-white/5">
                        @if ($project->tech_stack)
                            <x-tech-logo :name="$project->tech_stack[0]" class="size-4 shrink-0" />
                        @else
                            <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400 dark:text-zinc-500" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->title }}</div>
                            @if ($project->tagline)
                                <div class="truncate text-xs text-zinc-500">{{ \App\Support\Markdown::plain($project->tagline) }}</div>
                            @endif
                        </div>
                        @if ($project->tech_stack)
                            <span class="hidden shrink-0 items-center gap-1 sm:flex">
                                @foreach (array_slice($project->tech_stack, 0, 3) as $tech)
                                    <x-tech-logo :name="$tech" class="size-3.5" />
                                @endforeach
                            </span>
                        @endif
                        <span class="inline-flex shrink-0 items-center gap-1 text-xs text-zinc-400">
                            <flux:icon name="hand-thumb-up" variant="mini" />
                            {{ $project->recognition_count }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->vouches->isNotEmpty())
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Vouches</div>
            <div class="mt-2 grid gap-2">
                @foreach ($this->vouches as $vouch)
                    <div class="flex items-start gap-2.5">
                        <flux:icon name="shield-check" variant="micro" class="mt-0.5 shrink-0 text-emerald-500" />
                        <div class="min-w-0">
                            <div class="text-sm font-medium">{{ $vouch->type->label() }}</div>
                            <div class="text-xs text-zinc-500">from {{ $vouch->voucher->name }}</div>
                            @if ($vouch->message)
                                <p class="mt-1 break-words text-xs text-zinc-600 dark:text-zinc-300">{{ $vouch->message }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->achievements->isNotEmpty())
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Badges</div>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($this->achievements as $ua)
                    <flux:tooltip :content="$ua->achievement->name" position="top">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-50 text-accent dark:bg-zinc-900">
                            <flux:icon :name="$ua->achievement->icon" variant="micro" />
                        </div>
                    </flux:tooltip>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->isOwn)
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Engineering Magnitude</div>
            <div class="mt-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->magnitude['total']) }}</span>
                    <span class="text-xs font-semibold text-accent">{{ app(EngineeringMagnitudeService::class)->labelFor($this->magnitude['total']) }}</span>
                    <span class="text-[11px] text-zinc-400">/ 1000 · explainable</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($this->magnitude['factors'] as $factor)
                        <flux:tooltip
                            :content="$factor['label'].' · '.$factor['points'].'/'.$factor['max'].', '.$factor['description']"
                            position="top"
                        >
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                                {{ $factor['label'] }}
                                <span class="tabular-nums">{{ $factor['points'] }}</span>
                            </span>
                        </flux:tooltip>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($this->timeline->isNotEmpty())
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Engineering Timeline</div>
            <div class="mt-2 grid gap-2.5">
                @foreach ($this->timeline as $event)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-accent"></span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">{{ $event->title }}</div>
                            <div class="text-xs text-zinc-500">{{ $event->occurred_at->toFormattedDayDateString() }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->journal->isNotEmpty())
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Journal Highlights</div>
            <div class="mt-2 grid gap-2">
                @foreach ($this->journal as $entry)
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                        <div class="truncate text-sm font-medium">{{ $entry->title ?: 'Untitled entry' }}</div>
                        <p class="mt-1 line-clamp-2 text-xs text-zinc-500">{{ \App\Support\Markdown::plain($entry->structured_content['summary'] ?? \Illuminate\Support\Str::limit($entry->content, 120)) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <a href="{{ route('devid', $this->user->handle()) }}" wire:navigate class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:bg-white/5 dark:text-zinc-200">
            View full DevID
            <flux:icon name="arrow-up-right" variant="micro" />
        </a>
    </div>

    @auth
        @if (! $this->isOwn)
            @if (auth()->user()->isVerified())
                <div class="grid grid-cols-2 gap-2">
                    <flux:button
                        size="sm"
                        wire:click="connect"
                        wire:loading.attr="disabled"
                        class="w-full justify-center bg-zinc-900 text-white! transition hover:bg-zinc-700 dark:bg-white! dark:text-zinc-900! dark:hover:bg-zinc-200"
                    >
                        <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" />
                        Connect
                    </flux:button>
                    @if (auth()->user()->vouch_credits > 0)
                        <livewire:vouch-dialog :key="'vouch-flyout-'.$this->userId" :userId="$this->userId" />
                    @endif
                </div>
            @else
                <a
                    href="{{ route('verify') }}"
                    wire:navigate
                    class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-400/10 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-400/20 dark:text-amber-400"
                    title="Get verified to connect and vouch"
                >
                    <flux:icon name="lock-closed" variant="micro" />
                    Needs verification to connect & vouch
                </a>
            @endif
        @endif
    @endauth
</div>
