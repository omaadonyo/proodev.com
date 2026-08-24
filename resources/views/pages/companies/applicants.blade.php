<?php

use App\Enums\ApplicationStatus;
use App\Enums\FeedbackCategory;
use App\Enums\HiringStage;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Services\HiringTransparencyService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Applicants')]
class extends Component
{
    public Company $company;

    public ?int $activeJobId = null;

    public string $note = '';

    /** @var array<int, string> Per-application stage selections from the custom dropdowns. */
    public array $stages = [];

    // Stage-change / rejection state.
    public ?int $rejectingId = null;

    public bool $provideFeedback = false;

    public string $feedbackCategory = '';

    public string $feedbackNote = '';

    // Transparency settings form.
    public bool $editingSettings = false;

    public array $settings = [];

    public function mount(Company $company): void
    {
        abort_unless($company->isMember(auth()->user()), 403);

        $this->company = $company;
        $this->activeJobId ??= $this->jobs->first()?->id;
        $this->settings = $company->hiringSettings();
    }

    public function selectJob(int $jobId): void
    {
        $this->activeJobId = $jobId;
        $this->note = '';
        $this->rejectingId = null;
    }

    #[Computed]
    public function pipeline(): array
    {
        if (! $this->activeJob) {
            return [];
        }

        $applications = $this->activeJob->applications()->get();

        return collect([
            HiringStage::ApplicationReceived,
            HiringStage::Reviewing,
            HiringStage::Reviewed,
            HiringStage::Shortlisted,
            HiringStage::Assessment,
            HiringStage::Interview,
            HiringStage::Offer,
        ])->map(fn (HiringStage $stage) => [
            'stage' => $stage,
            'label' => match ($stage) {
                HiringStage::ApplicationReceived => 'New',
                HiringStage::Reviewing => 'Reviewing',
                default => $stage->label(),
            },
            'count' => $applications->filter(fn (Application $application) => $application->latestStage() === $stage)->count(),
        ])->all();
    }

    /**
     * Applications waiting on the hiring team for too long.
     */
    #[Computed]
    public function staleCount(): int
    {
        return app(HiringTransparencyService::class)->staleForCompany($this->company)->where('job_id', $this->activeJobId)->count();
    }

    public function setStage(int $applicationId, string $stage): void
    {
        $application = $this->company->jobs()
            ->findOrFail($this->activeJobId)
            ->applications()
            ->findOrFail($applicationId);

        app(HiringTransparencyService::class)->recordStage(
            $application,
            HiringStage::from($stage),
            actor: auth()->user(),
        );

        unset($this->applications);

        Flux::toast(variant: 'success', text: 'Candidate moved to "'.$stage.'" — we kept them posted.');
    }

    public function updatedStages(string $value, string $key): void
    {
        if (filled($value)) {
            $this->setStage((int) $key, $value);
        }
    }

    public function openReject(int $applicationId): void
    {
        $this->rejectingId = $applicationId;
        $this->provideFeedback = false;
        $this->feedbackCategory = '';
        $this->feedbackNote = '';
    }

    public function confirmReject(int $applicationId): void
    {
        $validated = $this->validate([
            'feedbackCategory' => [$this->provideFeedback ? 'required' : 'nullable', 'string'],
            'feedbackNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $application = $this->company->jobs()
            ->findOrFail($this->activeJobId)
            ->applications()
            ->findOrFail($applicationId);

        app(HiringTransparencyService::class)->recordStage(
            $application,
            HiringStage::NotSelected,
            actor: auth()->user(),
            feedbackCategory: $this->provideFeedback ? FeedbackCategory::from($validated['feedbackCategory']) : null,
            feedbackNote: $this->provideFeedback ? ($validated['feedbackNote'] ?: null) : null,
            metadata: ['internal' => false],
        );

        $this->rejectingId = null;

        unset($this->applications);

        Flux::toast(variant: 'success', text: 'Application closed — the candidate has been notified.');
    }

    public function saveNote(int $applicationId): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $this->company->jobs()
            ->findOrFail($this->activeJobId)
            ->applications()
            ->findOrFail($applicationId)
            ->update(['notes' => $this->note ?: null]);

        unset($this->applications);

        Flux::toast(variant: 'success', text: 'Internal note saved.');
    }

    public function saveSettings(): void
    {
        $this->company->update(['hiring_settings' => [
            'visibility' => in_array(($this->settings['visibility'] ?? null), ['minimal', 'standard', 'detailed'], true)
                ? $this->settings['visibility']
                : 'standard',
            'notify_received' => true, // platform-required confirmation
            'notify_reviewed' => (bool) ($this->settings['notify_reviewed'] ?? false),
            'notify_shortlisted' => (bool) ($this->settings['notify_shortlisted'] ?? false),
            'notify_assessment' => (bool) ($this->settings['notify_assessment'] ?? false),
            'notify_interview' => (bool) ($this->settings['notify_interview'] ?? false),
            'notify_paused' => (bool) ($this->settings['notify_paused'] ?? false),
            'notify_closed' => (bool) ($this->settings['notify_closed'] ?? false),
            'rejection_feedback' => (bool) ($this->settings['rejection_feedback'] ?? false),
        ]]);

        $this->settings = $this->company->fresh()->hiringSettings();
        $this->editingSettings = false;

        Flux::toast(variant: 'success', text: 'Hiring transparency settings saved.');
    }

    #[Computed]
    public function jobs()
    {
        return $this->company
            ->jobs()
            ->withCount('applications')
            ->latest()
            ->get();
    }

    #[Computed]
    public function activeJob(): ?Job
    {
        return $this->activeJobId
            ? $this->company->jobs()->withCount('applications')->find($this->activeJobId)
            : null;
    }

    #[Computed]
    public function applications()
    {
        return $this->activeJob?->applications()->with(['user', 'events'])->latest()->get() ?? collect();
    }

    #[Computed]
    public function feedbackCategories(): array
    {
        return collect(FeedbackCategory::cases())
            ->map(fn (FeedbackCategory $category) => ['value' => $category->value, 'label' => $category->label()])
            ->all();
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl">Applicants</flux:heading>
                    <flux:badge inset="top bottom" color="zinc">{{ $this->applications->count() }} total</flux:badge>
                </div>
                <flux:text class="mt-1">Keep candidates informed without extra work — ProoDev communicates stage updates for you.</flux:text>
            </div>
            <flux:button variant="subtle" wire:click="$set('editingSettings', true)">
                <flux:icon name="cog-6-tooth" variant="micro" />
                Transparency settings
            </flux:button>
        </div>

        @if ($this->staleCount > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300/50 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-400/5">
                <div>
                    <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">Candidate updates needed</div>
                    <p class="mt-0.5 text-xs text-amber-700/80 dark:text-amber-300/80">
                        {{ $this->staleCount }} candidate{{ $this->staleCount === 1 ? ' has' : 's have' }} been waiting for an update. Review, shortlist, reject or pause the role.
                    </p>
                </div>
                <span class="text-xs font-medium text-amber-700 dark:text-amber-400">{{ $this->activeJob?->title }}</span>
            </div>
        @endif

        @forelse ($this->jobs as $job)
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="button" wire:click="selectJob({{ $job->id }})" class="flex min-w-0 items-center gap-3 text-start">
                        <x-company-logo :company="$company" size="sm" />
                        <div class="min-w-0">
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $job->title }}</div>
                            <div class="mt-0.5 text-xs text-zinc-500">{{ $job->applications_count }} applicant{{ $job->applications_count === 1 ? '' : 's' }}</div>
                        </div>
                    </button>
                    <flux:badge size="sm" inset="top bottom" :color="$job->status === \App\Enums\JobStatus::Open ? 'green' : 'zinc'">{{ $job->status->label() }}</flux:badge>
                </div>

                @if ($this->activeJobId === $job->id)
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        {{-- Pipeline counts --}}
                        @if ($this->pipeline !== [])
                            <div class="mb-4 grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-zinc-200 sm:grid-cols-7 dark:bg-zinc-700">
                @foreach ($this->pipeline as $column)
                    <div class="bg-zinc-50 px-1 py-2 text-center dark:bg-zinc-900" title="{{ $column['stage']->label() }}">
                        <div class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($column['count']) }}</div>
                        <div class="mt-0.5 truncate text-[9px] uppercase tracking-wide text-zinc-500">{{ $column['label'] }}</div>
                    </div>
                @endforeach
                            </div>
                        @endif

                        <div class="mb-3 text-sm font-medium">Applicants</div>

                        <div class="grid gap-3">
                            @forelse ($this->applications as $application)
                                @php($stage = $application->latestStage())
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <flux:avatar :src="$application->user->avatarUrl()" :alt="$application->user->name" circle class="size-9" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-sm font-medium">
                                                <span>{{ $application->user->name }}</span>
                                                <x-verified-badge :user="$application->user" compact />
                                                @if ($stage)
                                                    <flux:badge size="sm" inset="top bottom" :color="match ($stage->value) {
                                                        'shortlisted', 'assessment', 'interview' => 'green',
                                                        'offer' => 'sky',
                                                        'not_selected' => 'red',
                                                        'role_paused', 'role_closed' => 'amber',
                                                        default => 'zinc',
                                                    }">{{ $stage->label() }}</flux:badge>
                                                @else
                                                    <flux:badge size="sm" inset="top bottom" color="zinc">{{ $application->status->label() }}</flux:badge>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('devid', $application->user->handle()) }}" wire:navigate class="text-xs text-accent hover:underline">{{ $application->user->handle() }}</a>
                                                @if ($application->resume_path)
                                                    <a href="{{ route('applications.resume', $application) }}" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                                        <flux:icon name="document-arrow-down" variant="micro" />
                                                        Resume PDF
                                                    </a>
                                                @endif
                                                <span class="text-[11px] text-zinc-400">last activity {{ app(HiringTransparencyService::class)->lastActivity($application)->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-searchable-select
                                                wire:model="stages.{{ $application->id }}"
                                                size="sm"
                                                placeholder="Move to…"
                                                searchPlaceholder="Search stages…"
                                                empty="No matching stage"
                                                class="w-40"
                                                wire:key="stage-select-{{ $application->id }}"
                                            >
                                                @foreach ([HiringStage::Reviewing, HiringStage::Reviewed, HiringStage::Shortlisted, HiringStage::Assessment, HiringStage::Interview, HiringStage::Offer, HiringStage::RolePaused, HiringStage::RoleClosed] as $option)
                                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                                @endforeach
                                            </x-searchable-select>
                                            <flux:button size="sm" variant="danger" wire:click="openReject({{ $application->id }})">Reject</flux:button>
                                        </div>
                                    </div>

                                    @if ($this->rejectingId === $application->id)
                                        <form wire:submit="confirmReject({{ $application->id }})" class="mt-3 rounded-md border border-red-200 bg-red-50/60 p-3 dark:border-red-500/20 dark:bg-red-500/5">
                                            <div class="text-xs font-semibold text-red-700 dark:text-red-400">Application decision — not selected</div>
                                            <label class="mt-2 flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300">
                                                <input type="checkbox" wire:model="provideFeedback" class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                                                Yes, provide optional structured feedback
                                            </label>
                                            @if ($provideFeedback)
                                                <div class="mt-2 grid gap-2">
                                                    <flux:select size="sm" wire:model="feedbackCategory">
                                                        <option value="">Select a reason…</option>
                                                        @foreach ($this->feedbackCategories as $category)
                                                            <option value="{{ $category['value'] }}">{{ $category['label'] }}</option>
                                                        @endforeach
                                                    </flux:select>
                                                    @error('feedbackCategory') <flux:error size="xs" name="feedbackCategory" /> @enderror
                                                    <flux:textarea size="sm" rows="2" wire:model="feedbackNote" placeholder="Optional short note — potential development areas…" />
                                                    <p class="text-[10px] text-zinc-400">Keep feedback professional, job-related and focused on role requirements. The candidate sees this as employer feedback.</p>
                                                </div>
                                            @endif
                                            <div class="mt-3 flex justify-end gap-2">
                                                <flux:button size="xs" variant="subtle" type="button" wire:click="$set('rejectingId', null)">Cancel</flux:button>
                                                <flux:button size="xs" variant="danger" type="submit">Close application</flux:button>
                                            </div>
                                        </form>
                                    @endif

                                    @if ($application->cover_letter)
                                        <p class="mt-3 rounded-md bg-white p-3 text-xs leading-relaxed text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-300">{{ $application->cover_letter }}</p>
                                    @endif

                                    <div class="mt-3 flex items-center gap-2">
                                        <flux:input wire:model="note" :placeholder="$application->notes ?: 'Add an internal note (never shown to candidates)…'" size="sm" class="flex-1" />
                                        <flux:button size="sm" variant="subtle" wire:click="saveNote({{ $application->id }})">Save</flux:button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500">No applicants yet for this role.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-600">
                <flux:icon name="users" class="mx-auto text-zinc-400" />
                <flux:heading class="mt-3">No openings yet</flux:heading>
                <flux:text class="mt-2">Post your first role to start receiving applications.</flux:text>
                <flux:button class="mt-4" variant="primary" :href="route('companies.jobs.create', $company)" wire:navigate>Post a job</flux:button>
            </div>
        @endforelse
    </div>

    {{-- Transparency settings modal --}}
    <flux:modal name="hiring-settings" wire:model="editingSettings" class="max-w-lg">
        <form wire:submit="saveSettings" class="grid gap-4">
            <div>
                <flux:heading size="lg">Hiring transparency</flux:heading>
                <flux:text>Choose what candidates can see and when ProoDev notifies them automatically. Transparency settings apply to new applications unless overridden by platform requirements.</flux:text>
            </div>

            <flux:field>
                <flux:label>Application status visibility</flux:label>
                <flux:select wire:model="settings.visibility">
                    <option value="minimal">Minimal — decisions only</option>
                    <option value="standard">Standard — milestones & decisions</option>
                    <option value="detailed">Detailed — all candidate-visible events</option>
                </flux:select>
            </flux:field>

            <div class="grid gap-3">
                @foreach ([
                    'notify_received' => 'Notify candidates when applications are received',
                    'notify_reviewed' => 'Notify candidates when applications are reviewed',
                    'notify_shortlisted' => 'Notify candidates when shortlisted',
                    'notify_assessment' => 'Notify candidates when assessment is requested',
                    'notify_interview' => 'Notify candidates when interview is requested',
                    'notify_paused' => 'Notify candidates when the position is paused',
                    'notify_closed' => 'Notify candidates when the position is closed',
                    'rejection_feedback' => 'Allow optional rejection feedback to be shared',
                ] as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="settings.{{ $key }}" @if ($key === 'notify_received') checked disabled title="Required by the platform" @endif class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        {{ $label }}
                        @if ($key === 'notify_received')<span class="text-[10px] text-zinc-400">(required)</span>@endif
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="subtle" type="button" wire:click="$set('editingSettings', false')">Cancel</flux:button>
                <flux:button variant="primary" type="submit">Save settings</flux:button>
            </div>
        </form>
    </flux:modal>
</div>