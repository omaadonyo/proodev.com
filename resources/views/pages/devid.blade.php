<?php use App\Actions\Evidence\AddEvidenceAction;
use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Jobs\AnalyzeEvidenceJob;
use App\Models\PlagiarismStrike;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\EngineeringMagnitudeService;
use App\Services\InsufficientCreditsException;
use App\Services\LevelService;
use App\Services\DevIDViewService;
use App\Services\PlagiarismDetectedException;
use App\Services\ProfileCompletionService;
use App\Services\ReputationService;
use App\Services\SubmissionLimitService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('DevID')] class extends Component
{
    use WithPagination;

    public User $user;

    public string $url = '';

    public ?string $typeFilter = null;

    public ?string $statusFilter = null;

    public function mount(User $user): void
    {
        if ($user->isAdmin() && auth()->id() !== $user->id) {
            abort(404);
        } abort_unless($user->public_passport || auth()->id() === $user->id, 404);
        $this->user = $user;
        app(DevIDViewService::class)->record($user, auth()->user(), request()->ip());
    }

    #[Computed]
    public function viewCount(): int
    {
        return app(DevIDViewService::class)->count($this->user);
    }

    #[Computed]
    public function recentViewers()
    {
        return app(DevIDViewService::class)->recentViewers($this->user, 12);
    }

    public function addEvidence(): void
    {
        $this->validate(['url' => ['required', 'string', 'url', 'max:2048']]);
        if (! app(SubmissionLimitService::class)->canSubmit(auth()->user())) {
            Flux::toast(variant: 'warning', text: 'Daily free analyses used up — spend 1 credit to continue or try again tomorrow.');

            return;
        } try {
            $evidence = app(AddEvidenceAction::class)->handle(auth()->user(), $this->url);
        } catch (InsufficientCreditsException) {
            Flux::toast(variant: 'warning', text: 'You need 1 credit to submit beyond the free daily allowance.');

            return;
        } catch (PlagiarismDetectedException $e) {
            $this->url = '';
            if ($e->strike->isBan()) {
                Flux::toast(variant: 'danger', text: 'Your account has been banned for repeated plagiarism.');
            } else {
                Flux::toast(variant: 'warning', text: 'That repository is not yours — the claim was removed and the owner has been notified.');
            }

            return;
        } $this->url = '';
        Flux::toast(variant: 'success', text: 'Evidence added — analysis queued.');
        $this->redirectRoute('evidence.show', $evidence, navigate: true);
    }

    public function reanalyze(int $id): void
    {
        $evidence = auth()->user()->evidence()->findOrFail($id);
        $evidence->update(['status' => EvidenceStatus::Pending, 'error' => null, 'ai_score' => null, 'analyzed_at' => null]);
        AnalyzeEvidenceJob::dispatch($evidence->fresh());
        Flux::toast(variant: 'success', text: 'Re-analysis queued.');
    }

    #[Computed]
    public function isOwn(): bool
    {
        return auth()->check() && auth()->id() === $this->user->id;
    }

    #[Computed]
    public function isBannedForPlagiarism(): bool
    {
        return PlagiarismStrike::where('offender_id', $this->user->id)
            ->where('action', 'banned')
            ->whereNull('overturned_at')
            ->exists();
    }

    /**
     * GitHub handle behind the current user's linked GitHub URL, or null
     * when no account is linked.
     */
    #[Computed]
    public function githubHandle(): ?string
    {
        $url = auth()->user()->github_url;

        if (! $url) {
            return null;
        }

        $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

        return $parts[0] ?? null;
    }

    /**
     * Whether the current user already carries repository evidence without
     * a linked GitHub account — the plagiarism guard cannot verify ownership.
     */
    #[Computed]
    public function hasRepoEvidence(): bool
    {
        return auth()->user()->evidence()
            ->whereIn('source', ['github', 'gitlab', 'bitbucket'])
            ->exists();
    }

    /**
     * Whether the URL currently typed into the evidence form points at a
     * repository page (github.com/owner/name or a GitLab/Bitbucket equivalent).
     */
    #[Computed]
    public function urlLooksLikeRepo(): bool
    {
        $url = trim($this->url);

        if ($url === '' || ! str_contains($url, '://')) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        if (! Str::contains($host, ['github.', 'gitlab.', 'bitbucket.'])) {
            return false;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return count(array_filter(explode('/', $path))) >= 2;
    }

    public function connect(): void
    {
        $viewer = auth()->user();
        if (! $viewer?->isVerified()) {
            Flux::toast(variant: 'warning', text: 'Get verified to chat with other developers.');

            return;
        } $conversation = $viewer->createConversationWith($this->user);
        if (! $conversation) {
            Flux::toast(variant: 'danger', text: 'Could not start a conversation right now.');

            return;
        } $this->redirectRoute('wirechat.chats.chat', $conversation, navigate: true);
    }

    #[Computed]
    public function messagesReceived(): int
    {
        $morph = $this->user->getMorphClass();

        return (int) $this->user->conversations()
            ->withCount(['messages' => fn ($query) => $query->whereHas('participant', fn ($participant) => $participant
                ->where('participantable_type', $morph)
                ->where('participantable_id', '!=', $this->user->id))])
            ->get()
            ->sum('messages_count');
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->check() ? auth()->user()->unreadMessageCount() : 0;
    }

    #[Computed]
    public function profileScore(): int
    {
        return app(ProfileCompletionService::class)->percentage($this->user);
    }

    #[Computed]
    public function stats(): array
    {
        return [['label' => 'Projects', 'value' => (string) $this->projects->count()], ['label' => 'Vouches', 'value' => (string) $this->user->approvedVouchesReceived()->count()], ['label' => 'Reputation', 'value' => number_format($this->reputation['total'])], ['label' => 'Level', 'value' => 'Lv '.$this->user->level()]];
    }

    #[Computed]
    public function projects()
    {
        return $this->user->projects()->where('status', 'published')->withCount('recognitions')->orderByDesc('published_at')->take(6)->get();
    }

    #[Computed]
    public function achievements()
    {
        return UserAchievement::where('user_id', $this->user->id)->whereNotNull('awarded_at')->with('achievement')->orderByDesc('awarded_at')->take(12)->get();
    }

    #[Computed]
    public function skills()
    {
        return $this->user->skills()->orderByPivot('level', 'desc')->take(12)->get();
    }

    #[Computed]
    public function vouches()
    {
        return $this->user->vouchesReceived()->where('status', 'approved')->with(['voucher', 'skill'])->latest()->take(10)->get();
    }

    #[Computed]
    public function timeline()
    {
        return $this->user->timelineEvents()->public()->orderByDesc('occurred_at')->take(20)->get();
    }

    #[Computed]
    public function journalHighlights()
    {
        return $this->user->journalEntries()->publiclyVisible()->orderByDesc('published_at')->take(3)->get();
    }

    #[Computed]
    public function verifications()
    {
        return $this->user->verificationRequests()->where('status', 'approved')->orderByDesc('reviewed_at')->get();
    }

    #[Computed]
    public function reputation()
    {
        return app(ReputationService::class)->breakdown($this->user);
    }

    #[Computed]
    public function magnitude()
    {
        return app(EngineeringMagnitudeService::class)->breakdown($this->user);
    }

    #[Computed]
    public function allowance(): array
    {
        $limit = app(SubmissionLimitService::class);

        return ['enabled' => $limit->enabled(), 'free' => (int) config('billing.developer.daily_free_submissions', 3), 'remaining' => $limit->remainingFree(auth()->user()), 'canSubmit' => $limit->canSubmit(auth()->user()), 'cost' => (int) config('billing.developer.submission_credit_cost', 1)];
    }

    #[Computed]
    public function library()
    {
        return auth()->user()->evidence()->with('analysis')->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))->orderByDesc('created_at')->paginate(12);
    }

    #[Computed]
    public function types(): array
    {
        return collect(EvidenceType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()])->all();
    }

    #[Computed]
    public function statuses(): array
    {
        return collect(EvidenceStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()])->all();
    }

    #[On('echo:feed,evidence-added')] #[On('echo:feed,evidence-analyzed')]
    public function refreshAfterAnalysis(): void
    {
        unset($this->library, $this->magnitude);
    }

    #[Computed]
    public function level(): array
    {
        return app(LevelService::class)->snapshot($this->user->experience_points);
    }

    #[Computed]
    public function growthStats(): array
    {
        return ['projects' => $this->user->projects()->where('status', 'published')->count(), 'achievements' => $this->achievements->count(), 'vouches' => $this->user->vouchesReceived()->where('status', 'approved')->count(), 'reputation' => $this->reputation['total'], 'xp' => $this->user->experience_points];
    }

    #[Computed]
    public function latestReport()
    {
        return $this->user->weeklyReports()->latest('week_started')->first();
    }

    #[Computed]
    public function weeklySeries(): array
    {
        return $this->user->weeklyReports()->orderBy('week_started')->get()->map(fn ($report) => $report->data['xp_gained'] ?? 0)->values()->all();
    }
}
?>

<div class="mx-auto grid max-w-5xl gap-6"> <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-950/80"> <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-white/10"> <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300"> <flux:icon name="check-badge" variant="micro" class="text-emerald-500 dark:text-emerald-400" /> Public DevID </span> <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300" title="DevID views"> <flux:icon name="eye" variant="micro" /> {{ number_format($this->viewCount) }} </span> @if ($this->messagesReceived > 0) <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300" title="Messages received"> <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" /> {{ number_format($this->messagesReceived) }} </span> @endif @if (auth()->user()->isVerified()) <flux:button size="xs" wire:click="connect" class="shrink-0 bg-zinc-900! text-white! hover:bg-zinc-700! dark:bg-white! dark:text-zinc-900! dark:hover:bg-zinc-200!">  <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" />  Connect' @if ($this->unreadCount > 0) <span class="'inline-flex min-w-4 items-center justify-center rounded-full bg-emerald-500 px-1 text-[10px] font-bold tabular-nums text-white'" title="{{ $this->unreadCount }} unread message{{ $this->unreadCount === 1 ? '' : 's'}}">{{ min(99, $this->unreadCount) }}</span> @endif </flux:button> @else <a href="{{ route('verify') }}" wire:navigate wire:click.prevent title="Needs verification � get verified to read your messages and chat with other developers" class="relative inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-zinc-900 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">  <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" />  Connect  @if ($this->unreadCount > 0) <span class="absolute -end-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-emerald-500 px-1 text-[10px] font-bold tabular-nums text-white ring-2 ring-white dark:ring-zinc-950">{{ min(99, $this->unreadCount) }}</span> @endif  <flux:icon name="lock-closed" variant="micro" class="size-3 opacity-60" /> </a> @endif @if ($this->isOwn && $this->recentViewers->isNotEmpty()) <span class="inline-flex items-center gap-1.5" title="Who viewed your profile"> <span class="flex -space-x-1.5"> @foreach ($this->recentViewers->take(5) as $view) @if ($view->viewer) @if (auth()->user()->isVerified()) <a href="{{ route('devid', $view->viewer->handle()) }}" wire:navigate class="block rounded-full"> <flux:avatar :src="$view->viewer->avatarUrl()" :alt="$view->viewer->name" class="size-6 rounded-full ring-2 ring-white dark:ring-zinc-950" /> </a> @else <span class="block size-6 rounded-full bg-zinc-200 blur-[2px] ring-2 ring-white dark:bg-zinc-700 dark:ring-zinc-950"></span> @endif @endif @endforeach </span> @if (auth()->user()->isVerified()) <span class="text-[11px] font-semibold tabular-nums text-zinc-500 dark:text-zinc-400">{{ $this->recentViewers->count() }} {{ \Illuminate\Support\Str::plural('viewer', $this->recentViewers->count()) }}</span> @else <a href="{{ route('verify') }}" wire:navigate class="text-[11px] font-semibold text-accent hover:underline">Verify to reveal</a> @endif </span> @endif <span x-data="{ shown: 0 }" x-init="let t = setInterval(() => { if (shown < {{ $this->profileScore }}) { shown++; } else { clearInterval(t); } }, 30)" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold tabular-nums text-emerald-600 dark:text-emerald-300" > <flux:icon name="sparkles" variant="micro" /> <span x-text="shown"></span>/100 </span> </div> <div class="grid gap-5 p-5"> @if ($this->isBannedForPlagiarism) <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-500/20 dark:bg-rose-500/10"> <flux:icon name="hand-raised" class="mt-0.5 size-4 shrink-0 text-rose-500" /> <div> <div class="text-sm font-semibold text-rose-700 dark:text-rose-300">This account has been banned for plagiarism</div> <p class="mt-0.5 text-xs leading-relaxed text-rose-600/80 dark:text-rose-300/70">Repeatedly claiming other developers' repositories as your own proof violates our community guidelines.</p> </div> </div> @endif <div class="flex flex-wrap items-start gap-4"> <div class="relative shrink-0"> <img src="{{ $this->user->avatarUrl() }}" alt="{{ $this->user->name }}" class="size-16 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-white/10" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" /> <div class="hidden size-16 items-center justify-center rounded-full bg-black text-lg font-bold text-white ring-2 ring-zinc-200 dark:bg-white dark:text-black dark:ring-zinc-800"> {{ $this->user->initials() }} </div> @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $this->user->isOnline()) <span class="absolute bottom-0.5 right-0.5 size-3 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-950"></span> @endif </div> <div class="min-w-0 flex-1"> <div class="flex flex-wrap items-center gap-2"> <div class="truncate text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->user->name }}</div> @if (! $this->isOwn && \App\Support\FeatureFlags::publicPresenceEnabled() && $this->user->isOnline()) <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"> <span class="size-1.5 rounded-full bg-emerald-500"></span> Online now </span> @endif @foreach ($this->verifications as $verification) <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"> <flux:icon name="check-badge" variant="micro" /> {{ $verification->label ?: $verification->type->label() }} </span> @endforeach @if ($this->user->isVerified()) <span class="inline-flex items-center gap-1 rounded-full bg-[#3750eb]/10 px-2.5 py-0.5 text-xs font-semibold text-[#3750eb] dark:text-[#8f9dff]"> <flux:icon name="shield-check" variant="micro" /> Verified </span> @endif @if ($this->isBannedForPlagiarism) <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2.5 py-0.5 text-xs font-semibold text-rose-600 dark:text-rose-400"> <flux:icon name="hand-raised" variant="micro" /> Banned </span> @endif </div> @if ($this->user->handle() || $this->user->location) <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400"> @if ($this->user->handle()){{ '@'.$this->user->handle() }}@endif @if ($this->user->handle() && $this->user->location) · @endif @if ($this->user->location){{ $this->user->location }}@endif </div> @endif @if ($this->user->headline) <div class="mt-1 truncate text-sm text-zinc-700 dark:text-zinc-300">{{ $this->user->headline }}</div> @endif <x-social-links :user="$this->user" class="mt-2" /> </div> <div class="flex shrink-0 items-center gap-2"> @auth @if (! $this->isOwn && auth()->user()->isVerified()) <flux:button size="sm" wire:click="connect" wire:loading.attr="disabled" class="bg-zinc-900 text-white! transition hover:bg-zinc-700 dark:bg-white! dark:text-zinc-900! dark:hover:bg-zinc-200"> <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" /> Connect </flux:button> @endif @if (! $this->isOwn && auth()->user()->vouch_credits > 0) <livewire:vouch-dialog :key="'vouch-'.$this->user->id" :userId="$this->user->id" /> @endif @if ($this->isOwn) <flux:button variant="ghost" href="{{ route('profile.edit') }}" wire:navigate> <flux:icon name="pencil" variant="micro" /> Edit </flux:button> @endif @endauth </div> </div> @if ($this->user->bio) <div> <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Summary</div> <p class="mt-1.5 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $this->user->bio }}</p> </div> @endif @if ($this->skills->isNotEmpty()) <div> <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Capabilities</div> <div class="mt-2 flex flex-wrap gap-1.5"> @foreach ($this->skills->take(6) as $skill) <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10"> <x-tech-logo :name="$skill->name" class="size-3.5 shrink-0" /> {{ $skill->name }} @if ($skill->pivot->verified_at) <flux:icon name="shield-check" variant="micro" class="text-emerald-500" /> @endif </span> @endforeach </div> </div> @endif <div class="grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-zinc-200 dark:bg-white/10"> @foreach ($this->stats as $stat) <div class="bg-zinc-50 px-2 py-3 text-center dark:bg-zinc-900"> <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ $stat['value'] }}</div> <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">{{ $stat['label'] }}</div> </div> @endforeach </div> @if ($this->projects->isNotEmpty()) <div> <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Projects</div> <div class="mt-2 grid gap-2"> @foreach ($this->projects->take(3) as $project) <a href="{{ route('projects.show', $project) }}" wire:navigate class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 transition hover:border-accent dark:border-white/10 dark:bg-zinc-900/70"> @if ($project->tech_stack) <x-tech-logo :name="$project->tech_stack[0]" class="size-4 shrink-0" /> @else <flux:icon name="folder-git-2" variant="micro" class="shrink-0 text-zinc-400 dark:text-zinc-500" /> @endif <div class="min-w-0 flex-1"> <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->title }}</div> @if ($project->tagline) <div class="truncate text-xs text-zinc-500">{{ \App\Support\Markdown::plain($project->tagline) }}</div> @endif </div> @if ($project->tech_stack) <span class="hidden shrink-0 items-center gap-1 sm:flex"> @foreach (array_slice($project->tech_stack, 0, 3) as $tech) <x-tech-logo :name="$tech" class="size-3.5" /> @endforeach </span> @endif <span class="inline-flex shrink-0 items-center gap-1 text-xs text-zinc-400"> <flux:icon name="hand-thumb-up" variant="mini" /> {{ $project->recognition_count }} </span> </a> @endforeach </div> </div> @endif </div> </div> @if ($this->isOwn) <div class="grid gap-4 md:grid-cols-3"> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <div class="flex items-center justify-between"> <flux:heading size="sm">Current Level</flux:heading> <flux:icon name="arrow-trending-up" class="text-accent" /> </div> <div class="mt-2 flex items-end justify-between gap-2"> <div> <div class="text-2xl font-bold">{{ $this->level['title'] }}</div> <div class="text-xs text-zinc-500">Level {{ $this->level['current'] }}</div> </div> <div class="text-end text-sm text-zinc-500"> <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($this->level['xp']) }} XP</div> <div>{{ $this->level['xp_to_next'] }} XP to {{ $this->level['next_title'] }}</div> </div> </div> <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900"> <div class="h-full rounded-full bg-zinc-900 transition-all dark:bg-white" style="width: {{ $this->level['progress'] }}%"></div> </div> </div> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <div class="flex items-center justify-between"> <flux:heading size="sm">Engineering Streak</flux:heading> <flux:icon name="calendar-days" class="text-orange-400" /> </div> <div class="mt-2 flex items-end justify-between"> <div class="text-2xl font-bold">{{ auth()->user()->streak_count }} days</div> <div class="text-sm text-zinc-500">Best: {{ auth()->user()->longest_streak }}</div> </div> <p class="mt-3 text-xs text-zinc-500"> {{ auth()->user()->last_activity_at ? 'Last activity '.auth()->user()->last_activity_at->diffForHumans() : 'Ship something today to start your streak.' }} </p> </div> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <div class="flex items-center justify-between"> <flux:heading size="sm">Reputation</flux:heading> <flux:icon name="shield-check" class="text-emerald-500" /> </div> <div class="mt-2 text-2xl font-bold">{{ number_format($this->growthStats['reputation']) }} <span class="text-sm font-normal text-zinc-500">/ 1000</span></div> <p class="mt-3 text-xs text-zinc-500">Evidence-based score from projects, vouches, and verification.</p> </div> </div> <div class="overflow-hidden rounded-xl border border-accent/20 bg-white p-4 dark:bg-zinc-800"> <div class="flex flex-wrap items-center justify-between gap-3"> <div> <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Engineering Magnitude</div> <div class="mt-1 flex items-baseline gap-2"> <span class="text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->magnitude['total']) }}</span> <span class="text-sm font-semibold text-accent">{{ app(\App\Services\EngineeringMagnitudeService::class)->labelFor($this->magnitude['total']) }}</span> <span class="text-xs text-zinc-400">/ 1000 · explainable</span> </div> </div> <div class="flex flex-wrap gap-1.5"> @foreach ($this->magnitude['factors'] as $factor) <flux:tooltip :content="$factor['label'].' · '.$factor['points'].'/'.$factor['max'].' — '.$factor['description']" position="top" > <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"> {{ $factor['label'] }} <span class="tabular-nums">{{ $factor['points'] }}</span> </span> </flux:tooltip> @endforeach </div> </div> <div class="mt-4 grid gap-2"> @foreach ($this->magnitude['factors'] as $factor) <div class="flex items-center gap-3"> <div class="w-44 shrink-0 text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $factor['label'] }}</div> <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900"> <div class="h-full rounded-full bg-zinc-900 dark:bg-white" style="width: {{ ($factor['points'] / max(1, $factor['max'])) * 100 }}%"></div> </div> <div class="w-12 shrink-0 text-right text-xs tabular-nums text-zinc-500">{{ $factor['points'] }}/{{ $factor['max'] }}</div> </div> @endforeach </div> </div> <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800"> <form wire:submit="addEvidence" class="flex flex-col gap-3 sm:flex-row sm:items-center"> <div class="flex-1"> <flux:input wire:model.live.debounce.500ms="url" type="url" placeholder="Paste a GitHub repo, package, article, demo or documentation URL…" class="w-full" /> <flux:error name="url" /> </div> <flux:button type="submit" variant="primary" class="shrink-0"> <flux:icon name="plus" variant="micro" /> Add evidence </flux:button> </form> @if ($this->isOwn && ! $this->githubHandle && $this->hasRepoEvidence) <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-500/20 dark:bg-amber-500/10"> <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 dark:text-amber-300"> <flux:icon name="exclamation-triangle" variant="micro" class="text-amber-500" /> Link your GitHub account so we can verify your repositories are yours. </span> <flux:button size="sm" variant="ghost" href="{{ route('profile.edit') }}" wire:navigate class="bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-200 dark:hover:bg-amber-500/30"> Link GitHub </flux:button> </div> @endif @if ($this->isOwn && ! $this->githubHandle && $this->urlLooksLikeRepo) <div class="mt-3 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-500/20 dark:bg-amber-500/10"> <flux:icon name="exclamation-triangle" variant="micro" class="mt-0.5 size-4 shrink-0 text-amber-500" /> <div class="text-xs leading-relaxed text-amber-700 dark:text-amber-300"> <span class="font-semibold">Heads up — you haven't linked a GitHub account.</span> Link your GitHub URL so the plagiarism guard can verify this repository is yours before it's added. <a href="{{ route('profile.edit') }}" wire:navigate class="font-semibold underline hover:no-underline">Link GitHub</a> </div> </div> @endif <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-500"> <span class="inline-flex items-center gap-1.5"> <flux:icon name="shield-check" variant="micro" class="text-emerald-500" /> One-time analysis, never continuously re-scraped. </span> <span class="inline-flex items-center gap-1.5"> <flux:icon name="document-text" variant="micro" class="text-accent" /> Every insight includes references. </span> @if (($this->allowance['enabled'] ?? false)) <span class="inline-flex items-center gap-1.5"> <flux:icon name="sparkles" variant="micro" class="text-amber-500" /> {{ $this->allowance['remaining'] }}/{{ $this->allowance['free'] }} free analyses left today @if (($this->allowance['remaining'] ?? 0) === 0) · {{ $this->allowance['cost'] }} credit each after that @endif </span> @endif </div> </div> <div class="flex flex-wrap items-center gap-2"> <flux:badge inset="left" size="sm" color="zinc" icon="funnel">Filter</flux:badge> <x-searchable-select wire:model.live="typeFilter" size="sm" class="w-40"> <option value="">All types</option> @foreach ($this->types as $type) <option value="{{ $type['value'] }}">{{ $type['label'] }}</option> @endforeach </x-searchable-select> <x-searchable-select wire:model.live="statusFilter" size="sm" class="w-40"> <option value="">All statuses</option> @foreach ($this->statuses as $status) <option value="{{ $status['value'] }}">{{ $status['label'] }}</option> @endforeach </x-searchable-select> </div> <div class="grid gap-3"> @forelse ($this->library as $item) <a href="{{ route('evidence.show', $item) }}" wire:navigate class="group grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-accent/50 hover:shadow-lg hover:shadow-zinc-900/5 dark:border-zinc-700 dark:bg-zinc-800 sm:grid-cols-[auto_minmax(0,1fr)_auto]" > <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent"> <flux:icon name="{{ match ($item->type->value) { 'github-repository', 'gitlab-repository', 'bitbucket-repository' => 'folder-git-2', 'package' => 'archive-box', 'technical-article', 'blog-post', 'documentation' => 'document-text', 'technical-video' => 'video-camera', default => 'link', } }}" /> </div> <div class="min-w-0"> <div class="flex flex-wrap items-center gap-2"> <span class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $item->title }}</span> <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $item->type->label() }}</span> </div> <div class="mt-0.5 truncate text-xs text-zinc-500">{{ $item->url }}</div> @if ($item->analysis?->summary) <p class="mt-1.5 line-clamp-2 text-xs text-zinc-600 dark:text-zinc-300">{{ \App\Support\Markdown::plain($item->analysis->summary) }}</p> @endif @if ($item->analysis?->technologies) <div class="mt-2 flex flex-wrap gap-1.5"> @foreach (array_slice($item->analysis->technologies, 0, 5) as $tech) <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[11px] text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ $tech }}</span> @endforeach </div> @endif </div> <div class="flex shrink-0 flex-col items-end justify-between gap-2 sm:items-end"> @if ($item->ai_score !== null) <span class="text-sm font-bold tabular-nums text-accent">{{ $item->ai_score }}</span> <span class="text-[10px] uppercase tracking-wide text-zinc-400">score</span> @endif <span @class([ 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium', 'bg-emerald-400/10 text-emerald-600 dark:text-emerald-400' => $item->status->value === 'ready', 'bg-amber-400/10 text-amber-600 dark:text-amber-400' => in_array($item->status->value, ['pending', 'extracting', 'analyzing'], true), 'bg-rose-400/10 text-rose-600 dark:text-rose-400' => $item->status->value === 'failed', ]) > <flux:icon name="{{ match ($item->status->value) { 'ready' => 'check-circle', 'failed' => 'x-circle', default => 'clock', } }}" variant="micro" /> {{ $item->status->label() }} </span> </div> </a> @empty <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600"> <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900"> <flux:icon name="document-plus" /> </div> <flux:heading>Your evidence library is empty</flux:heading> <flux:text class="mt-2">Add a repository, article, package or demo URL above to begin building your proof.</flux:text> </div> @endforelse </div> @if ($this->library->hasPages()) <div>{{ $this->library->links() }}</div> @endif <div> <flux:heading size="sm">Score breakdown</flux:heading> <div class="mt-5 grid gap-5"> @foreach ($this->reputation['components'] as $key => $component) @if ($component['weight'] > 0) <div class="flex items-center gap-4"> <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-accent dark:bg-zinc-900"> <flux:icon :name="$component['icon']" variant="solid" class="size-5" /> </div> <div class="min-w-0 flex-1"> <div class="flex items-center justify-between"> <span class="text-sm font-medium">{{ $component['label'] }}</span> <span class="text-sm font-semibold">{{ $component['points'] }} pts</span> </div> <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900"> <div class="h-full rounded-full bg-accent" style="width: {{ min(100, ($component['points'] / 300) * 100) }}%"></div> </div> </div> </div> @endif @endforeach </div> </div> <div class="grid gap-6 lg:grid-cols-2"> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <flux:heading size="sm">Weekly XP Growth</flux:heading> @if ($this->weeklySeries !== []) <div class="mt-4 flex h-28 items-end gap-2"> @foreach ($this->weeklySeries as $i => $xp) @php $max = max(1, max($this->weeklySeries)); @endphp <div class="flex-1 rounded-t bg-accent/20 dark:bg-accent/10" style="height: {{ max(6, ($xp / $max) * 100) }}%"> <div class="mx-auto flex h-full items-end justify-center text-[10px] font-semibold text-zinc-500"> {{ $xp }} </div> </div> @endforeach </div> @else <p class="mt-4 text-sm text-zinc-500">Weekly reports are generated every Monday. Publish work to populate your growth chart.</p> @endif </div> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <flux:heading size="sm">Latest Weekly Report</flux:heading> @if ($this->latestReport) @php $data = $this->latestReport->data; @endphp <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3"> <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900"> <div class="text-lg font-bold">{{ $data['projects_published'] ?? 0 }}</div> <div class="text-xs text-zinc-500">Projects published</div> </div> <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900"> <div class="text-lg font-bold">{{ $data['xp_gained'] ?? 0 }} <span class="text-xs font-normal text-emerald-500">+{{ $data['growth_percentage'] ?? 0 }}%</span></div> <div class="text-xs text-zinc-500">XP gained</div> </div> <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900"> <div class="text-lg font-bold">{{ $data['activity_count'] ?? 0 }}</div> <div class="text-xs text-zinc-500">Activities</div> </div> </div> @if (filled($data['insights'] ?? null)) <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-300"> {{ is_string($data['insights']) ? $data['insights'] : ($data['insights']['summary'] ?? '') }} </p> @endif @else <p class="mt-4 text-sm text-zinc-500">Your first weekly report arrives after a week of activity.</p> @endif </div> </div> @endif <div class="grid gap-6 lg:grid-cols-3"> <div class="grid gap-6 lg:col-span-2"> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <flux:heading size="sm">Engineering Timeline</flux:heading> <div class="mt-4 grid gap-3"> @forelse ($this->timeline as $event) <div class="flex items-start gap-3"> <span class="mt-1.5 size-2 shrink-0 rounded-full bg-accent"></span> <div class="min-w-0"> <div class="text-sm font-medium">{{ $event->title }}</div> <div class="text-xs text-zinc-500">{{ $event->occurred_at->toFormattedDayDateString() }}</div> </div> </div> @empty <p class="text-sm text-zinc-500">No public engineering history yet.</p> @endforelse </div> </div> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <flux:heading size="sm">Journal Highlights</flux:heading> <div class="mt-4 grid gap-3"> @forelse ($this->journalHighlights as $entry) <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900"> <div class="text-sm font-medium">{{ $entry->title ?: 'Untitled entry' }}</div> <p class="mt-1 line-clamp-2 text-xs text-zinc-500">{{ \App\Support\Markdown::plain($entry->structured_content['summary'] ?? Str::limit($entry->content, 160)) }}</p> </div> @empty <p class="text-sm text-zinc-500">No public journal entries.</p> @endforelse </div> </div> </div> <div class="grid gap-6"> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <flux:heading size="sm">Badges</flux:heading> <div class="mt-4 grid grid-cols-4 gap-2"> @forelse ($this->achievements as $ua) <flux:tooltip :content="$ua->achievement->name" position="top"> <div class="flex aspect-square items-center justify-center rounded-lg bg-zinc-50 text-accent dark:bg-zinc-900"> <flux:icon :name="$ua->achievement->icon" /> </div> </flux:tooltip> @empty <p class="col-span-4 text-sm text-zinc-500">No badges yet.</p> @endforelse </div> </div> <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"> <flux:heading size="sm">Vouches</flux:heading> <div class="mt-4 grid gap-3"> @forelse ($this->vouches as $vouch) <div class="flex items-start gap-2.5"> <flux:icon name="shield-check" variant="micro" class="mt-0.5 text-emerald-500" /> <div class="min-w-0"> <div class="text-sm font-medium">{{ $vouch->type->label() }}</div> <div class="text-xs text-zinc-500">from {{ $vouch->voucher->name }}</div> @if ($vouch->message) <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">{{ $vouch->message }}</p> @endif </div> </div> @empty <p class="text-sm text-zinc-500">No vouches yet.</p> @endforelse </div> </div> </div> </div>
</div>