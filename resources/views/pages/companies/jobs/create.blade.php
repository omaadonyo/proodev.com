<?php

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Services\Ai\AiService;
use App\Services\NotificationService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Post a Job')] class extends Component
{
    public Company $company;

    public string $title = '';

    public string $description = '';

    public string $requirements = '';

    public string $location = '';

    public bool $is_remote = true;

    public string $employment_type = 'full-time';

    public ?int $salary_min = null;

    public ?int $salary_max = null;

    public string $currency = 'USD';

    public ?string $deadline = null;

    public string $jobBrief = '';

    public bool $drafting = false;

    public string $draftError = '';

    public function mount(Company $company): void
    {
        abort_unless($company->isMember(auth()->user()), 403);

        if (! $company->isApproved()) {
            Flux::toast(variant: 'warning', text: 'Your company is pending review. You can draft a job now and publish once approved.');
        }

        $this->company = $company;
    }

    public function create(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:8000'],
            'requirements' => ['nullable', 'string', 'max:4000'],
            'location' => ['nullable', 'string', 'max:120'],
            'is_remote' => ['boolean'],
            'employment_type' => ['required', 'string', 'in:full-time,part-time,contract,internship'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'currency' => ['required', 'string', 'max:3'],
            'deadline' => ['nullable', 'date'],
        ]);

        $status = $this->company->canPostJobs() ? JobStatus::Open : JobStatus::Draft;

        $job = Job::create([
            'company_id' => $this->company->id,
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'requirements' => $this->requirementsList($validated['requirements'] ?? ''),
            'location' => $validated['location'],
            'is_remote' => $validated['is_remote'],
            'employment_type' => $validated['employment_type'] ?: null,
            'salary_min' => $validated['salary_min'],
            'salary_max' => $validated['salary_max'],
            'currency' => $validated['currency'],
            'status' => $status,
            'published_at' => $status === JobStatus::Open ? now() : null,
            'deadline' => $validated['deadline'] ?: null,
        ]);

        if ($status === JobStatus::Open) {
            app(NotificationService::class)->jobPublished($job);
        }

        Flux::toast(
            variant: 'success',
            text: $status === JobStatus::Open ? 'Job published.' : 'Job saved as draft — job post credit limit reached or company pending.',
        );

        $this->redirectRoute('companies.manage', $this->company, navigate: true);
    }

    public function draftWithAi(): void
    {
        $this->resetErrorBag();
        $this->draftError = '';

        $this->validate(['jobBrief' => ['required', 'string', 'min:10', 'max:4000']]);

        $this->drafting = true;

        try {
            $draft = app(AiService::class)->draftJobPosting($this->jobBrief, [
                'company' => [
                    'name' => $this->company->name,
                    'description' => $this->company->description,
                    'industry' => $this->company->industry,
                    'location' => $this->company->location,
                ],
            ]);

            $this->title = (string) ($draft['title'] ?? '');
            $this->description = (string) ($draft['description'] ?? '');
            $this->requirements = collect($draft['requirements'] ?? [])->map(fn ($line) => (string) $line)->implode("\n");
            $this->location = (string) ($draft['location'] ?? '');
            $this->is_remote = (bool) ($draft['is_remote'] ?? true);
            $this->employment_type = in_array($draft['employment_type'] ?? '', ['full-time', 'part-time', 'contract', 'internship'], true)
                ? $draft['employment_type']
                : 'full-time';
            $this->salary_min = is_numeric($draft['salary_min'] ?? null) ? (int) $draft['salary_min'] : null;
            $this->salary_max = is_numeric($draft['salary_max'] ?? null) ? (int) $draft['salary_max'] : null;
            $this->currency = strtoupper((string) ($draft['currency'] ?? 'USD'));
            $this->deadline = (string) ($draft['deadline'] ?? '') ?: null;

            Flux::toast(variant: 'success', text: 'AI draft ready — review and edit the details, then publish.');
        } catch (Throwable $e) {
            $this->draftError = 'Could not generate a draft right now. Please write the posting manually.';
        } finally {
            $this->drafting = false;
        }
    }

    private function requirementsList(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Post a job</flux:heading>
            <flux:text>Posting for {{ $company->name }}.</flux:text>
        </div>

        @if ($company->plan->isPaid())
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold">Job post credits</div>
                        <div class="mt-0.5 text-xs text-zinc-500">{{ $company->plan->label() }} plan — unlimited job posts.</div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold">Job post credits</div>
                        <div class="mt-0.5 text-xs text-zinc-500">{{ $company->usedJobPosts() }} of {{ $company->jobPostCredits() }} used · {{ $company->remainingJobPosts() }} remaining</div>
                    </div>
                    <flux:button size="sm" variant="subtle" :href="route('companies.manage', $company)" wire:navigate>Buy more</flux:button>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                    <div class="h-full rounded-full bg-accent transition-all" style="width: {{ min(100, ($company->usedJobPosts() / max(1, $company->jobPostCredits())) * 100) }}%"></div>
                </div>
                @if ($company->planLimitReached())
                    <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">You have reached your job post limit — the posting will be saved as a draft until you buy more credits or close an existing job.</p>
                @endif
            </div>
        @endif

        <div class="rounded-xl border border-accent/30 bg-accent/5 p-5 dark:border-accent/40">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="sm">Draft with AI</flux:heading>
                    <flux:text class="mt-1 text-sm">Describe the role in a few sentences — AI drafts the full posting below, and you review and edit before publishing.</flux:text>
                </div>
                <flux:icon name="sparkles" class="text-accent" />
            </div>

            <form wire:submit="draftWithAi" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:textarea wire:model="jobBrief" rows="3" class="flex-1" placeholder="e.g. We're hiring a senior Laravel engineer to own our payments platform — 5+ years of PHP, REST APIs, Postgres and Docker. Remote-friendly." />
                <flux:button type="submit" variant="primary" class="shrink-0" wire:loading.attr="disabled" wire:target="draftWithAi">
                    <span wire:loading.remove wire:target="draftWithAi">Generate draft</span>
                    <span wire:loading wire:target="draftWithAi">Drafting…</span>
                </flux:button>
            </form>

            <flux:error name="jobBrief" />
            @if ($this->draftError)
                <p class="mt-2 text-xs text-red-500">{{ $this->draftError }}</p>
            @endif
        </div>

        <form wire:submit="create" class="grid gap-5">
            <flux:field>
                <flux:label>Job title</flux:label>
                <flux:input wire:model="title" placeholder="Senior Backend Engineer" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" rows="6" placeholder="What will the role own? What does success look like?" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>Requirements</flux:label>
                <flux:textarea wire:model="requirements" rows="4" placeholder="One requirement per line&#10;• 5+ years of Laravel/PHP&#10;• Real-time systems experience" />
                <flux:error name="requirements" />
            </flux:field>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Location</flux:label>
                    <flux:input wire:model="location" placeholder="Remote · New York, US" />
                    <flux:error name="location" />
                </flux:field>

                <flux:field>
                    <flux:label>Employment type</flux:label>
                    <x-searchable-select wire:model="employment_type">
                        @foreach (['full-time' => 'Full-time', 'part-time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-searchable-select>
                    <flux:error name="employment_type" />
                </flux:field>
            </div>

            <flux:switch wire:model="is_remote" label="This is a remote role" />

            <div class="grid gap-5 sm:grid-cols-3">
                <flux:field>
                    <flux:label>Salary (min)</flux:label>
                    <flux:input wire:model="salary_min" type="number" placeholder="80000" />
                    <flux:error name="salary_min" />
                </flux:field>

                <flux:field>
                    <flux:label>Salary (max)</flux:label>
                    <flux:input wire:model="salary_max" type="number" placeholder="140000" />
                    <flux:error name="salary_max" />
                </flux:field>

                <flux:field>
                    <flux:label>Currency</flux:label>
                    <x-searchable-select wire:model="currency">
                        @foreach (['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'INR' => 'INR'] as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </x-searchable-select>
                </flux:field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Application deadline</flux:label>
                    <flux:input wire:model="deadline" type="date" />
                    <flux:error name="deadline" />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button type="button" variant="ghost" :href="route('companies.manage', $company)" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Publish job</flux:button>
            </div>
        </form>
    </div>
</div>
