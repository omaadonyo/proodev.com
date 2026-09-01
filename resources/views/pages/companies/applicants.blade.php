<?php

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\Job;
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

    public function mount(Company $company): void
    {
        abort_unless($company->isMember(auth()->user()), 403);

        $this->company = $company;
        $this->activeJobId ??= $this->jobs->first()?->id;
    }

    public function selectJob(int $jobId): void
    {
        $this->activeJobId = $jobId;
        $this->note = '';
    }

    public function setApplicationStatus(int $applicationId, string $status): void
    {
        $application = $this->company->jobs()
            ->findOrFail($this->activeJobId)
            ->applications()
            ->findOrFail($applicationId);

        $application->update([
            'status' => ApplicationStatus::from($status),
            'reviewed_at' => now(),
        ]);

        unset($this->applications);

        Flux::toast(variant: 'success', text: 'Application updated.');
    }

    public function saveNote(int $applicationId): void
    {
        $this->company->jobs()
            ->findOrFail($this->activeJobId)
            ->applications()
            ->findOrFail($applicationId)
            ->update(['notes' => $this->note ?: null]);

        unset($this->applications);

        Flux::toast(variant: 'success', text: 'Note saved.');
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
        return $this->activeJob?->applications()->with('user')->latest()->get() ?? collect();
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:heading size="xl">Applicants</flux:heading>
                <flux:badge inset="top bottom" color="zinc">{{ $this->applications->count() }} total</flux:badge>
            </div>
            <flux:text class="mt-1">Developers who have applied to your openings. Pick a job to review its applicants.</flux:text>
        </div>

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
                        <div class="mb-3 text-sm font-medium">Applicants</div>

                        <div class="grid gap-3">
                            @forelse ($this->applications as $application)
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <flux:avatar :src="$application->user->avatarUrl()" :alt="$application->user->name" circle class="size-9" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-sm font-medium">
                                                <span>{{ $application->user->name }}</span>
                                                <x-verified-badge :user="$application->user" compact />
                                                <flux:badge size="sm" inset="top bottom" :color="match ($application->status->value) {
                                                    'shortlisted' => 'green',
                                                    'rejected' => 'red',
                                                    'hired' => 'sky',
                                                    default => 'zinc',
                                                }">{{ $application->status->label() }}</flux:badge>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('devid', $application->user->handle()) }}" wire:navigate class="text-xs text-accent hover:underline">{{ $application->user->handle() }}</a>
                                                @if ($application->resume_path)
                                                    <a href="{{ route('applications.resume', $application) }}" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                                        <flux:icon name="document-arrow-down" variant="micro" />
                                                        Resume PDF
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <flux:button size="sm" variant="subtle" wire:click="setApplicationStatus({{ $application->id }}, 'shortlisted')">Shortlist</flux:button>
                                            <flux:button size="sm" variant="subtle" wire:click="setApplicationStatus({{ $application->id }}, 'rejected')">Reject</flux:button>
                                            <flux:button size="sm" variant="primary" wire:click="setApplicationStatus({{ $application->id }}, 'hired')">Hire</flux:button>
                                        </div>
                                    </div>

                                    @if ($application->cover_letter)
                                        <p class="mt-3 rounded-md bg-white p-3 text-xs leading-relaxed text-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-300">{{ $application->cover_letter }}</p>
                                    @endif

                                    <div class="mt-3 flex items-center gap-2">
                                        <flux:input wire:model="note" :placeholder="$application->notes ?: 'Add a recruiter note…'" size="sm" class="flex-1" />
                                        <flux:button size="sm" variant="subtle" wire:click="saveNote({{ $application->id }})">Save note</flux:button>
                                    </div>
                                    @if ($application->notes)
                                        <div class="mt-2 text-xs text-zinc-500">Note: {{ $application->notes }}</div>
                                    @endif
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
</div>
