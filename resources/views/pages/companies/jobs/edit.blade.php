<?php

use App\Models\Company;
use App\Models\Job;
use App\Services\Ai\AiService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Job')] class extends Component
{
    public Company $company;

    public Job $job;

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

    public function mount(Company $company, Job $job): void
    {
        abort_unless($company->isMember(auth()->user()), 403);
        abort_unless($job->company_id === $company->id, 404);

        $this->company = $company;
        $this->job = $job;

        $this->title = $job->title;
        $this->description = $job->description;
        $this->requirements = implode("\n", $job->requirements ?? []);
        $this->location = $job->location ?? '';
        $this->is_remote = $job->is_remote;
        $this->employment_type = $job->employment_type ?? 'full-time';
        $this->salary_min = $job->salary_min;
        $this->salary_max = $job->salary_max;
        $this->currency = $job->currency;
        $this->deadline = $job->deadline?->toDateString();
    }

    public function update(): void
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

        $this->job->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'requirements' => $this->requirementsList($validated['requirements'] ?? ''),
            'location' => $validated['location'],
            'is_remote' => $validated['is_remote'],
            'employment_type' => $validated['employment_type'] ?: null,
            'salary_min' => $validated['salary_min'],
            'salary_max' => $validated['salary_max'],
            'currency' => $validated['currency'],
            'deadline' => $validated['deadline'] ?: null,
        ]);

        Flux::toast(variant: 'success', text: 'Job updated.');

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

            Flux::toast(variant: 'success', text: 'AI draft ready. Review and edit the details, then save.');
        } catch (Throwable $e) {
            $this->draftError = 'Could not generate a draft right now. Please edit the posting manually.';
        } finally {
            $this->drafting = false;
        }
    }

    public function delete(): void
    {
        $this->job->delete();

        Flux::toast(variant: 'success', text: 'Job deleted.');

        $this->redirectRoute('companies.manage', $this->company, navigate: true);
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
            <flux:heading size="xl">Edit job</flux:heading>
            <flux:text>Editing {{ $job->title }} at {{ $company->name }}.</flux:text>
        </div>

        @if ($company->plan->isPaid())
            <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold">Job post credits</div>
                        <div class="mt-0.5 text-xs text-zinc-500">{{ $company->plan->label() }} plan, unlimited job posts.</div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
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
            </div>
        @endif

        <div class="rounded-xl border border-accent/30 bg-accent/5 p-5 dark:border-accent/40">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="sm">Refresh with AI</flux:heading>
                    <flux:text class="mt-1 text-sm">Describe how the role has changed. AI rewrites the posting below, and you review and edit before saving.</flux:text>
                </div>
                <flux:icon name="sparkles" class="text-accent" />
            </div>

            <form wire:submit="draftWithAi" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:textarea wire:model="jobBrief" rows="3" class="flex-1" placeholder="e.g. The role now also owns our mobile checkout, add React Native, Stripe and on-call expectations. Keep it senior and remote-friendly." />
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

        <form wire:submit="update" class="grid gap-5">
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
                <flux:textarea wire:model="requirements" rows="4" placeholder="One requirement per line" />
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

            <div class="flex items-center justify-between gap-3">
                <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Delete this job permanently?">
                    Delete
                </flux:button>

                <div class="flex items-center gap-3">
                    <flux:button type="button" variant="ghost" :href="route('companies.manage', $company)" wire:navigate>Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
