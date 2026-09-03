<?php

use App\Livewire\Concerns\InteractsWithTalentPools;
use App\Mail\InterviewInvitationMail;
use App\Models\RecruiterInterview;
use App\Models\TalentPoolMember;
use App\Models\User;
use App\Services\Recruiter\InterviewGeneratorService;
use App\Services\Recruiter\WorkspaceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Interview Builder')] class extends Component
{
    use InteractsWithTalentPools;

    public string $search = '';

    public ?int $candidateId = null;

    public string $role = '';

    public bool $generated = false;

    public string $scheduledAt = '';

    public string $interviewMode = 'video';

    public string $weekStart = '';

    public ?int $activeInterviewId = null;

    /** @var array<int, int> */
    public array $compared = [];

    #[Computed]
    public function searchResults()
    {
        if (strlen(trim($this->search)) < 2 || $this->candidateId) {
            return collect();
        }

        return User::query()
            ->where('public_passport', true)
            ->where(fn ($q) => $q
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('username', 'like', '%'.trim($this->search).'%')
                ->orWhere('headline', 'like', '%'.trim($this->search).'%'))
            ->orderByDesc('reputation_score')
            ->limit(6)
            ->get();
    }

    /**
     * Candidates already saved to the recruiter's talent pools, grouped by pool,
     * so they can be picked for an interview without searching.
     */
    #[Computed]
    public function poolCandidates()
    {
        $members = TalentPoolMember::with(['candidate', 'pool'])
            ->whereIn('talent_pool_id', $this->pools->pluck('id'))
            ->get()
            ->filter(fn ($member) => $member->candidate !== null);

        return $members
            ->groupBy(fn ($member) => $member->pool?->name ?? 'Pools')
            ->map(fn ($group) => [
                'pool' => $group->first()->pool,
                'members' => $group->values(),
            ])
            ->values();
    }

    #[Computed]
    public function candidate()
    {
        return $this->candidateId ? User::find($this->candidateId) : null;
    }

    #[Computed]
    public function guide()
    {
        if (! $this->generated || ! $this->candidateId) {
            return null;
        }

        return app(InterviewGeneratorService::class)->generate(
            User::findOrFail($this->candidateId),
            ['role' => $this->role],
            auth()->user(),
        );
    }

    public function selectCandidate(int $id): void
    {
        $this->candidateId = $id;
        $this->search = '';
        $this->generated = false;
    }

    public function generate(): void
    {
        $this->validate(['candidateId' => ['required']]);

        $this->generated = true;
    }

    public function markCompared(int $id): void
    {
        $candidate = User::find($id);

        if (! $candidate) {
            return;
        }

        TalentPoolMember::whereIn('talent_pool_id', $this->pools->pluck('id'))
            ->where('candidate_id', $id)
            ->update(['status' => 'interviewing']);

        if (! in_array($id, $this->compared, true)) {
            $this->compared[] = $id;
        }

        $this->dispatch('toast', message: $candidate->name.' marked as compared and moved to interviewing.', variant: 'success');
    }

    public function isCompared(int $id): bool
    {
        return in_array($id, $this->compared, true);
    }

    #[Computed]
    public function upcomingInterviews()
    {
        $workspace = app(WorkspaceService::class)->current(auth()->user());

        return RecruiterInterview::with('candidate')
            ->where('recruiter_id', auth()->id())
            ->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id))
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now()->subHour())
            ->orderBy('scheduled_at')
            ->take(10)
            ->get();
    }

    public function scheduleInterview(): void
    {
        $this->validate([
            'candidateId' => ['required'],
            'scheduledAt' => ['required', 'date'],
            'interviewMode' => ['required', 'in:video,phone,onsite'],
        ]);

        $candidate = User::find($this->candidateId);

        if (! $candidate) {
            return;
        }

        $workspace = app(WorkspaceService::class)->current(auth()->user());

        $interview = RecruiterInterview::create([
            'workspace_id' => $workspace?->id,
            'recruiter_id' => auth()->id(),
            'candidate_id' => $candidate->id,
            'status' => 'scheduled',
            'scheduled_at' => Carbon::parse($this->scheduledAt),
            'mode' => $this->interviewMode,
        ]);

        Mail::to($candidate)->send(new InterviewInvitationMail($interview));

        TalentPoolMember::whereIn('talent_pool_id', $this->pools->pluck('id'))
            ->where('candidate_id', $candidate->id)
            ->update(['status' => 'interviewing']);

        $this->scheduledAt = '';
        $this->dispatch('toast', message: 'Interview scheduled, invitation email sent to '.$candidate->name.'.', variant: 'success');
    }

    public function cancelInterview(int $id): void
    {
        $interview = RecruiterInterview::where('recruiter_id', auth()->id())->find($id);

        if (! $interview) {
            return;
        }

        $interview->update(['status' => 'cancelled']);
        $this->dispatch('toast', message: 'Interview cancelled.', variant: 'success');
    }

    #[Computed]
    public function weekStartDate()
    {
        return $this->weekStart
            ? Carbon::parse($this->weekStart)->startOfDay()
            : Carbon::now()->startOfWeek()->startOfDay();
    }

    #[Computed]
    public function weekDays()
    {
        return collect(range(0, 6))
            ->map(fn (int $i) => $this->weekStartDate()->copy()->addDays($i));
    }

    #[Computed]
    public function weekInterviews()
    {
        $workspace = app(WorkspaceService::class)->current(auth()->user());

        return RecruiterInterview::with('candidate')
            ->where('recruiter_id', auth()->id())
            ->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id))
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [
                $this->weekStartDate()->copy()->startOfDay(),
                $this->weekStartDate()->copy()->addDays(6)->endOfDay(),
            ])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn ($interview) => $interview->scheduled_at->toDateString());
    }

    #[Computed]
    public function activeInterview()
    {
        return $this->activeInterviewId
            ? RecruiterInterview::with('candidate')
                ->where('recruiter_id', auth()->id())
                ->find($this->activeInterviewId)
            : null;
    }

    public function selectInterview(int $id): void
    {
        $this->activeInterviewId = $id;
    }

    public function previousWeek(): void
    {
        $this->weekStart = $this->weekStartDate()->copy()->subWeek()->toDateString();
        $this->activeInterviewId = null;
    }

    public function nextWeek(): void
    {
        $this->weekStart = $this->weekStartDate()->copy()->addWeek()->toDateString();
        $this->activeInterviewId = null;
    }

    public function thisWeek(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek()->toDateString();
        $this->activeInterviewId = null;
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Interview Builder</flux:heading>
        <flux:text>Evidence-grounded interview questions. Pick from your saved candidates or search the network, mark them as compared, and save the approved ones back to your pools.</flux:text>
    </div>

    <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
        <flux:heading size="sm">Candidate</flux:heading>

        @if ($this->candidate)
            <div class="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-accent/40 bg-accent/5 p-3">
                <flux:avatar :src="$this->candidate->avatarUrl()" :alt="$this->candidate->name" circle class="size-9" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <div class="truncate font-medium">{{ $this->candidate->name }}</div>
                        <x-verified-badge :user="$this->candidate" compact />
                        @if ($this->isCompared($this->candidate->id))
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/10 px-2 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <flux:icon name="check-badge" variant="micro" /> Compared
                            </span>
                        @endif
                    </div>
                    <div class="truncate text-xs text-zinc-500">{{ $this->candidate->headline }}</div>
                </div>
                <flux:button size="xs" variant="ghost" wire:click="$set('candidateId', null)">Change</flux:button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <flux:button
                    size="sm"
                    variant="{{ $this->isCompared($this->candidate->id) ? 'primary' : 'outline' }}"
                    wire:click="markCompared({{ $this->candidate->id }})"
                    :disabled="$this->isCompared($this->candidate->id)"
                >
                    <flux:icon name="check-badge" variant="micro" />
                    {{ $this->isCompared($this->candidate->id) ? 'Marked as compared' : 'Mark as compared' }}
                </flux:button>

                @if ($this->isCompared($this->candidate->id))
                    <span class="text-sm text-zinc-500">Save the approved candidate:</span>
                    <x-save-to-pool :candidate="$this->candidate" :pools="$this->pools" />
                @endif
            </div>

            <div class="mt-5 border-t border-zinc-100 pt-4 dark:border-white/10">
                <flux:heading size="sm">Schedule interview</flux:heading>
                <form wire:submit="scheduleInterview" class="mt-3 flex flex-wrap items-end gap-3">
                    <flux:field class="w-full sm:w-auto">
                        <flux:label>Date & time</flux:label>
                        <flux:input type="datetime-local" wire:model="scheduledAt" class="w-56" />
                        <flux:error name="scheduledAt" />
                    </flux:field>
                    <flux:field class="w-full sm:w-auto">
                        <flux:label>Mode</flux:label>
                        <flux:select wire:model="interviewMode" class="h-10 w-40">
                            <option value="video">Video call</option>
                            <option value="phone">Phone</option>
                            <option value="onsite">On-site</option>
                        </flux:select>
                    </flux:field>
                    <flux:button type="submit" variant="primary">
                        <flux:icon name="calendar-days" variant="micro" />
                        Schedule interview
                    </flux:button>
                </form>
            </div>
        @else
            <flux:heading size="sm" class="mt-4">Your saved candidates</flux:heading>
            <flux:text class="text-sm">Everyone already in your talent pools, pick one without searching.</flux:text>

            @forelse ($this->poolCandidates as $group)
                <div class="mt-4">
                    <div class="text-xs font-semibold uppercase tracking-widest text-zinc-400">
                        {{ $group['pool']?->name ?? 'Pools' }} ({{ count($group['members']) }})
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        @foreach ($group['members'] as $member)
                            <button type="button" wire:click="selectCandidate({{ $member->candidate->id }})"
                                class="group flex flex-col items-center gap-1.5 rounded-xl bg-zinc-100 p-3 text-center transition hover:bg-zinc-200 dark:bg-white/5 dark:hover:bg-white/10">
                                <span class="relative">
                                    <flux:avatar :src="$member->candidate->avatarUrl()" :alt="$member->candidate->name" circle class="size-14 group-hover:ring-2 group-hover:ring-accent" />
                                    <span @class([
                                        'absolute -bottom-1 -right-1 size-3.5 rounded-full ring-2 ring-white dark:ring-zinc-800',
                                        'bg-zinc-400' => $member->status === 'saved',
                                        'bg-sky-500' => in_array($member->status, ['shortlisted', 'contacted'], true),
                                        'bg-amber-500' => $member->status === 'interviewing',
                                        'bg-emerald-500' => in_array($member->status, ['offered', 'placed'], true),
                                        'bg-rose-500' => $member->status === 'rejected',
                                    ])></span>
                                </span>
                                <span class="w-full truncate text-[11px] font-medium">{{ $member->candidate->name }}</span>
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-900">{{ \Illuminate\Support\Str::title($member->status) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <flux:text class="mt-2 text-sm">No saved candidates yet. Save candidates from evidence search or their reports and they will appear here.</flux:text>
            @endforelse

            <div class="mt-6 border-t border-zinc-100 pt-4 dark:border-white/10">
                <flux:label>…or search the whole network</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search engineers..." class="mt-1" />
                @if ($this->searchResults->isNotEmpty())
                    <div class="mt-2 grid gap-2">
                        @foreach ($this->searchResults as $user)
                            <button type="button" wire:click="selectCandidate({{ $user->id }})" class="flex items-center gap-3 rounded-lg bg-zinc-100 p-3 text-left transition hover:bg-zinc-200 dark:bg-white/5 dark:hover:bg-white/10">
                                <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-8" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <div class="truncate text-sm font-medium">{{ $user->name }}</div>
                                        <x-verified-badge :user="$user" compact />
                                    </div>
                                    <div class="truncate text-xs text-zinc-500">{{ $user->headline }}</div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <flux:field class="mt-6">
            <flux:label>Target role (optional)</flux:label>
            <flux:input wire:model="role" placeholder="e.g. Senior Backend Engineer" />
        </flux:field>

        <div class="mt-4">
            <flux:button variant="primary" wire:click="generate" :disabled="!$this->candidate">
                <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" />
                Generate interview guide
            </flux:button>
        </div>
    </div>

    <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="sm">Weekly calendar</flux:heading>
                <flux:text class="text-sm">
                    {{ $this->weekStartDate()->format('M j') }} - {{ $this->weekStartDate()->copy()->addDays(6)->format('M j, Y') }} · all pools
                </flux:text>
            </div>
            <div class="flex items-center gap-1">
                <flux:button size="xs" variant="ghost" wire:click="previousWeek" title="Previous week"><flux:icon name="chevron-left" variant="micro" /></flux:button>
                <flux:button size="xs" variant="ghost" wire:click="thisWeek">This week</flux:button>
                <flux:button size="xs" variant="ghost" wire:click="nextWeek" title="Next week"><flux:icon name="chevron-right" variant="micro" /></flux:button>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-7 gap-px overflow-hidden rounded-t-lg bg-zinc-200 dark:bg-white/10">
            @foreach ($this->weekDays as $day)
                <div class="bg-zinc-50 px-2 py-2 text-center dark:bg-zinc-900">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ $day->format('D') }}</div>
                    <div @class([
                        'mx-auto mt-0.5 flex size-6 items-center justify-center rounded-full text-xs font-semibold',
                        'bg-accent text-white' => $day->isToday(),
                        'text-zinc-700 dark:text-zinc-300' => ! $day->isToday(),
                    ])>{{ $day->format('j') }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-px grid grid-cols-7 gap-px overflow-hidden rounded-b-lg bg-zinc-200 dark:bg-white/10">
            @foreach ($this->weekDays as $day)
                <div class="relative bg-white dark:bg-zinc-950/80" style="height: {{ 11 * 44 }}px; background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 43px, rgba(120,120,120,.18) 43px, rgba(120,120,120,.18) 44px);">
                    @php $dayKey = $day->toDateString(); @endphp
                    @foreach ($this->weekInterviews[$dayKey] ?? [] as $interview)
                        @php
                            $start = $interview->scheduled_at;
                            $hour = max(8, min(18, (int) $start?->hour));
                            $top = min(10 * 44, ($hour - 8) * 44 + ((int) $start?->minute / 60) * 44);
                            $modeColor = match ($interview->mode) {
                                'video' => 'bg-sky-500/90 hover:bg-sky-500',
                                'phone' => 'bg-violet-500/90 hover:bg-violet-500',
                                'onsite' => 'bg-amber-500/90 hover:bg-amber-500',
                                default => 'bg-accent/90 hover:bg-accent',
                            };
                        @endphp
                        <button type="button" wire:click="selectInterview({{ $interview->id }})"
                            @class([
                                'absolute left-1 right-1 z-10 rounded-md px-1.5 py-0.5 text-left text-[10px] font-medium leading-tight text-white shadow-sm transition',
                                $modeColor,
                                'ring-2 ring-accent ring-offset-1 ring-offset-white dark:ring-offset-zinc-950' => $this->activeInterviewId === $interview->id,
                            ])
                            style="top: {{ $top }}px; min-height: 20px"
                            title="{{ $interview->candidate->name }} · {{ $start?->format('g:i A') }} · {{ Str::title($interview->mode ?? '') }}">
                            <span class="block truncate">{{ $start?->format('g:i') }} {{ $interview->candidate->name }}</span>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-4 text-[11px] text-zinc-500">
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-sky-500"></span> Video</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-violet-500"></span> Phone</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-500"></span> On-site</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-accent"></span> Not set</span>
        </div>

        @if ($this->activeInterview)
            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-lg border border-accent/40 bg-accent/5 p-3">
                <flux:avatar :src="$this->activeInterview->candidate->avatarUrl()" :alt="$this->activeInterview->candidate->name" circle class="size-9" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <div class="truncate text-sm font-medium">{{ $this->activeInterview->candidate->name }}</div>
                        <x-verified-badge :user="$this->activeInterview->candidate" compact />
                    </div>
                    <div class="truncate text-xs text-zinc-500">
                        {{ $this->activeInterview->scheduled_at?->format('D, M j · g:i A') }} · {{ Str::title($this->activeInterview->mode ?? 'not set') }} · {{ $this->activeInterview->status }}
                    </div>
                </div>
                <flux:button size="xs" variant="ghost" href="{{ route('recruiter.candidates.show', $this->activeInterview->candidate_id) }}" wire:navigate>
                    Open report
                </flux:button>
                <flux:button size="xs" variant="ghost" wire:click="cancelInterview({{ $this->activeInterview->id }})">Cancel</flux:button>
            </div>
        @endif
    </div>

    @if ($this->guide)
        <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="sm">Interview guide for {{ $this->guide['candidate']['name'] }}</flux:heading>
                @if ($this->guide['role'])
                    <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-medium text-accent">{{ $this->guide['role'] }}</span>
                @endif
            </div>

            @if (isset($this->guide['sections']['behavioural']))
                <flux:heading size="sm" class="mt-5">Behavioural</flux:heading>
                <div class="mt-2 grid gap-2">
                    @foreach ($this->guide['sections']['behavioural'] as $question)
                        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
                            <div class="text-xs uppercase tracking-wide text-zinc-400">{{ $question['category'] }}</div>
                            <p class="mt-1 text-sm">{{ $question['question'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:heading size="sm" class="mt-5">Technical (evidence-grounded)</flux:heading>
            <div class="mt-2 grid gap-2">
                @foreach ($this->guide['sections']['technical'] as $question)
                    <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-zinc-400">{{ $question['category'] }}</div>
                        <p class="mt-1 text-sm">{{ $question['question'] }}</p>
                    </div>
                @endforeach
            </div>

            <flux:heading size="sm" class="mt-5">Probing & verification</flux:heading>
            <div class="mt-2 grid gap-2">
                @foreach ($this->guide['sections']['probing'] as $question)
                    <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-zinc-400">{{ $question['category'] }}</div>
                        <p class="mt-1 text-sm">{{ $question['question'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mt-4 rounded-lg bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-zinc-900">{{ $this->guide['probe_note'] }}</p>
        </div>
    @endif

    <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="sm">Upcoming interviews</flux:heading>
            @if ($this->upcomingInterviews->isNotEmpty())
                <flux:text class="text-sm">{{ $this->upcomingInterviews->count() }} scheduled</flux:text>
            @endif
        </div>
        <div class="mt-3 grid gap-2">
            @forelse ($this->upcomingInterviews as $interview)
                <div class="flex flex-wrap items-center gap-3 rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
                    <flux:avatar :src="$interview->candidate->avatarUrl()" :alt="$interview->candidate->name" circle class="size-8" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <div class="truncate text-sm font-medium">{{ $interview->candidate->name }}</div>
                            <x-verified-badge :user="$interview->candidate" compact />
                        </div>
                        <div class="truncate text-xs text-zinc-500">
                            {{ $interview->scheduled_at?->format('D, M j · g:i A') }} · {{ Str::title($interview->mode ?? 'not set') }}
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-accent/10 px-2.5 py-1 text-xs font-medium text-accent">{{ $interview->status }}</span>
                    <flux:button size="xs" variant="ghost" wire:click="cancelInterview({{ $interview->id }})">
                        Cancel
                    </flux:button>
                </div>
            @empty
                <flux:text class="text-sm">No interviews scheduled yet. Pick a candidate above and schedule one.</flux:text>
            @endforelse
        </div>
    </div>
</div>
