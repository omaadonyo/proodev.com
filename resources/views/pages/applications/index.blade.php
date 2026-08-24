<?php

use App\Enums\HiringStage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Applications')] class extends Component {
    public int $userId;

    public function mount(): void
    {
        $this->userId = auth()->id();
    }

    #[Computed]
    public function isVerified(): bool
    {
        return auth()->user()->isVerified();
    }

    #[Computed]
    public function applications()
    {
        return auth()->user()
            ->applications()
            ->with(['job.company'])
            ->when($this->isVerified, fn ($query) => $query->with(['events' => fn ($q) => $q->where('candidate_visible', true)->orderBy('created_at')->orderBy('id')]))
            ->latest()
            ->get();
    }

    #[On('echo-private:App.Models.User.{userId},.application-stage-changed')]
    public function refreshTimeline(): void
    {
        unset($this->applications);
    }
}
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">My applications</flux:heading>
            <flux:text>Track every role you've applied to with your DevID — and know where you stand.</flux:text>
        </div>

        {{-- Verification gate for Hiring Transparency --}}
        @unless ($this->isVerified)
            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm">Unlock Hiring Transparency</flux:heading>
                            <flux:icon name="lock-closed" variant="micro" class="text-amber-500" />
                        </div>
                        <flux:text class="max-w-md">
                            Verify your DevID to see meaningful updates about your applications, including review stages,
                            shortlisting, interviews and employer updates.
                        </flux:text>
                    </div>
                    <flux:button variant="primary" href="{{ route('verify') }}" wire:navigate>
                        Verify My DevID
                    </flux:button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 opacity-60">
                    @foreach (['Application received', 'Application reviewed', 'Shortlisted', 'Interview', 'Decision'] as $milestone)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-600 blur-[1px] dark:bg-zinc-900 dark:text-zinc-400">
                            {{ $milestone }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endunless

        <div class="grid gap-3">
            @forelse ($this->applications as $application)
                <?php
                    $stage = $this->isVerified ? $application->latestStage() : null;
                    $decision = $stage?->isDecision();
                ?>
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $application->job->title }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-zinc-500">
                                <a href="{{ route('companies.show', $application->job->company) }}" wire:navigate class="text-accent hover:underline">{{ $application->job->company->name }}</a>
                                <span>{{ $application->job->is_remote ? 'Remote' : ($application->job->location ?: 'On-site') }}</span>
                                <span>Applied {{ $application->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($stage && $this->isVerified)
                                <flux:badge size="sm" inset="top bottom" :color="match ($stage->value) {
                                    'shortlisted', 'assessment', 'interview' => 'green',
                                    'offer' => 'sky',
                                    'not_selected' => 'red',
                                    'role_paused', 'role_closed' => 'amber',
                                    default => 'zinc',
                                }">{{ $stage->label() }}</flux:badge>
                            @else
                                <flux:badge size="sm" inset="top bottom" :color="match ($application->status->value) {
                                    'shortlisted' => 'green',
                                    'rejected' => 'red',
                                    'hired' => 'sky',
                                    default => 'zinc',
                                }">{{ $application->status->label() }}</flux:badge>
                            @endif
                            <flux:button size="sm" variant="subtle" :href="route('jobs.show', ['company' => $application->job->company, 'job' => $application->job])" wire:navigate>
                                View role
                            </flux:button>
                        </div>
                    </div>

                    {{-- Candidate-facing hiring timeline --}}
                    @if ($this->isVerified)
                        <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-700/70">
                            <div class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Application timeline</div>

                            @if ($stage?->isClosedState())
                                <div class="rounded-lg border {{ $stage === HiringStage::RolePaused ? 'border-amber-300/50 bg-amber-50 dark:border-amber-400/20 dark:bg-amber-400/5' : 'border-zinc-300/70 bg-zinc-50 dark:border-zinc-600/40 dark:bg-zinc-900/60' }} p-3 text-xs leading-relaxed {{ $stage === HiringStage::RolePaused ? 'text-amber-800 dark:text-amber-300' : 'text-zinc-600 dark:text-zinc-300' }}">
                                    @if ($stage === HiringStage::RolePaused)
                                        <strong>Hiring is currently paused.</strong> The company has temporarily paused this position.
                                        You have not been rejected.
                                    @elseif ($stage === HiringStage::NotSelected && ! $application->timeline()->contains(fn ($e) => filled($e->feedback_category)))
                                        <strong>Application closed.</strong> The company has decided to proceed with other candidates.
                                    @endif
                                </div>
                            @endif

                            <ol class="mt-3 space-y-0">
                                @php
                                    $track = collect(HiringStage::milestoneTrack());
                                    // Merge real events into the milestone track so closed states render too.
                                    $timeline = $application->timeline();
                                    $currentIndex = $stage ? $track->search(fn ($s) => $s === $stage) : null;
                                @endphp

                                @if ($timeline->isEmpty())
                                    <li class="flex items-center gap-2 py-1 text-xs text-zinc-400 italic">
                                        Awaiting first update from the hiring team…
                                    </li>
                                @else
                                    @foreach ($timeline as $event)
                                        @php
                                            $eventStage = $event->stage();
                                            $dotClass = $eventStage === HiringStage::NotSelected
                                                ? 'bg-zinc-400 text-white'
                                                : 'bg-emerald-500 text-white';
                                        @endphp
                                        <li class="flex items-start gap-3 py-1">
                                            <span class="{{ 'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full '.$dotClass }}">
                                                <flux:icon name="check" variant="micro" />
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                                    <span class="text-sm font-medium {{ $decision ? 'text-zinc-500' : 'text-zinc-900 dark:text-white' }}">{{ $eventStage->label() }}</span>
                                                    <span class="text-[11px] tabular-nums text-zinc-400">{{ $event->created_at->format('M j, Y') }}</span>
                                                </div>
                                                @if ($event->feedback_category)
                                                    <div class="mt-1 rounded-lg bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-900/70 dark:text-zinc-300">
                                                        <span class="font-semibold">Employer feedback:</span>
                                                        {{ \App\Enums\FeedbackCategory::from($event->feedback_category)->label() }}
                                                        @if (filled($event->feedback_note))
                                                            <div class="mt-1">Potential development areas:<br />→ {{ nl2br(e($event->feedback_note)) }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach

                                    {{-- Pending next milestone --}}
                                    @if ($stage && ! $stage->isDecision())
                                        <li class="flex items-start gap-3 py-1 opacity-60">
                                            <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border border-dashed border-zinc-300 text-transparent dark:border-zinc-600"><flux:icon name="check" variant="micro" /></span>
                                            <span class="py-0.5 text-sm text-zinc-400">Next step pending…</span>
                                        </li>
                                    @endif

                                    @if (! $stage || ! $stage->isDecision())
                                        <li class="flex items-center gap-3 py-1 opacity-50">
                                            <span class="size-2.5 rounded-full border border-zinc-300 dark:border-zinc-600"></span>
                                            <span class="text-xs text-zinc-400">Final decision</span>
                                        </li>
                                    @endif
                                @endif
                            </ol>
                        </div>
                    @else
                        <div class="mt-3 flex items-center gap-2 text-xs text-zinc-400">
                            <flux:icon name="lock-closed" variant="micro" />
                            Application timeline unlocks with verification —
                            <a href="{{ route('verify') }}" wire:navigate class="font-medium text-accent hover:underline">verify your DevID</a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-600">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <flux:icon name="document-text" />
                    </div>
                    <flux:heading>No applications yet</flux:heading>
                    <flux:text class="mt-2">Browse open roles and apply with your evidence-backed DevID.</flux:text>
                    <flux:button class="mt-4" variant="primary" :href="route('jobs.index')" wire:navigate>Browse jobs</flux:button>
                </div>
            @endforelse
        </div>
    </div>
</div>