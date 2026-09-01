<?php

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Services\NotificationService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Apply')] class extends Component {
    use WithFileUploads;

    public Company $company;

    public Job $job;

    public $resume = null;

    public function mount(Company $company, Job $job): void
    {
        abort_unless($job->company_id === $company->id, 404);
        abort_unless($job->status === JobStatus::Open && $company->isApproved(), 404);
        abort_if($job->applications()->where('user_id', auth()->id())->exists(), 409);

        $this->company = $company;
        $this->job = $job;
    }

    public function submit(): void
    {
        $resumePath = null;

        if ($this->resume) {
            $this->validate(['resume' => ['file', 'mimes:pdf', 'max:5120']]);
            $resumePath = $this->resume->store('resumes', 'local');
        }

        $application = Application::create([
            'job_id' => $this->job->id,
            'user_id' => auth()->id(),
            'resume_path' => $resumePath,
        ]);

        app(NotificationService::class)->jobApplicationSubmitted($application);

        Flux::toast(variant: 'success', text: 'Application submitted.');

        $this->redirectRoute('applications.index', navigate: true);
    }

    public function confirmSubmit(): void
    {
        Flux::modal('confirm-apply')->show();
    }

    #[Computed]
    public function passportUrl(): string
    {
        return route('devid', auth()->user()->handle());
    }

}
?>

<div class="mx-auto w-full max-w-2xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Apply to {{ $job->title }}</flux:heading>
            <flux:text>{{ $company->name }} · your DevID travels with the application as proof.</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <flux:avatar :src="auth()->user()->avatarUrl()" :alt="auth()->user()->name" circle class="size-10" />
                <div>
                    <div class="text-sm font-semibold">{{ auth()->user()->name }}</div>
                    <a href="{{ $this->passportUrl }}" wire:navigate class="text-xs text-accent hover:underline">
                        {{ auth()->user()->handle() }} · public DevID
                        @if (auth()->user()->isVerified())
                            <flux:badge size="sm" color="emerald" inset="top bottom">Verified</flux:badge>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                    <flux:icon name="paper-airplane" variant="solid" class="size-4" />
                </div>
                <div class="grid min-w-0 flex-1 gap-4">
                    <div>
                        <div class="text-sm font-semibold">How would you like to be reviewed?</div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                            Submit with your evidence-backed DevID, attach a PDF resume, or both. {{ $company->name }} will receive whatever you provide.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-accent/30 bg-accent/5 p-3">
                            <div class="flex items-center gap-2 text-sm font-medium text-accent">
                                <flux:icon name="check-badge" variant="micro" />
                                DevID
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">Your evidence, projects, vouches and magnitude, always included.</p>
                        </div>

                        <label class="flex cursor-pointer flex-col justify-center rounded-lg border border-dashed border-zinc-300 p-3 transition hover:border-accent dark:border-zinc-600">
                            <span class="flex items-center gap-2 text-sm font-medium">
                                <flux:icon name="document-arrow-down" variant="micro" class="text-accent" />
                                <span wire:loading.remove wire:target="resume">PDF resume</span>
                                <span wire:loading wire:target="resume">Uploading…</span>
                            </span>
                            <span class="mt-1 truncate text-xs text-zinc-500">
                                @if ($resume)
                                    {{ $resume->getClientOriginalName() }}
                                @else
                                    Optional · click to attach a .pdf (max 5 MB)
                                @endif
                            </span>
                            <input type="file" wire:model="resume" accept="application/pdf,.pdf" class="sr-only" />
                        </label>
                    </div>

                    <flux:error name="resume" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button type="button" variant="ghost" :href="route('jobs.show', ['company' => $company, 'job' => $job])" wire:navigate>Cancel</flux:button>
            <flux:button variant="primary" wire:click="confirmSubmit">Submit application</flux:button>
        </div>

        <flux:modal name="confirm-apply" variant="flyout">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Apply to {{ $job->title }}</flux:heading>
                    <flux:text>Your DevID will be sent to {{ $company->name }}. Ready to apply?</flux:text>
                </div>

                <div class="flex justify-end gap-3">
                    <flux:modal.close variant="ghost">Cancel</flux:modal.close>
                    <flux:button variant="primary" wire:click="submit" data-test="confirm-apply-button">
                        Confirm application
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</div>