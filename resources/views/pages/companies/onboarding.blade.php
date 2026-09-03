<?php

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.onboarding')]
#[Title('Company onboarding')]
class extends Component
{
    use WithFileUploads;

    public Company $company;

    public $logo = null;

    public string $phase = 'details';

    public string $industry = '';

    public string $location = '';

    public string $size = '';

    public string $website = '';

    public array $githubRepos = [''];

    public string $description = '';

    public string $title = '';

    public string $jobDescription = '';

    public string $requirements = '';

    public string $jobLocation = '';

    public bool $is_remote = true;

    public string $employment_type = 'full-time';

    public ?int $salary_min = null;

    public ?int $salary_max = null;

    public string $currency = 'USD';

    public function mount(Company $company): void
    {
        abort_unless($company->isMember(auth()->user()), 403);

        $this->company = $company;
        $this->industry = $company->industry ?? '';
        $this->location = $company->location ?? '';
        $this->size = $company->size ?? '';
        $this->website = $company->website ?? '';
        $this->githubRepos = $company->github_repos ?: [''];
        $this->description = $company->description ?? '';
    }

    public function saveLogo(): void
    {
        $this->validate(['logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048']]);

        if ($this->company->logo_path) {
            Storage::disk('public')->delete($this->company->logo_path);
        }

        $path = $this->logo->store('company-logos', 'public');

        $this->company->forceFill(['logo_path' => $path])->save();

        $this->logo = null;

        Flux::toast(variant: 'success', text: 'Company logo updated.');
    }

    public function removeLogo(): void
    {
        if ($this->company->logo_path) {
            Storage::disk('public')->delete($this->company->logo_path);
        }

        $this->company->forceFill(['logo_path' => null])->save();

        $this->logo = null;

        Flux::toast(variant: 'success', text: 'Company logo removed.');
    }

    public function saveDetails(): void
    {
        $validated = $this->validate([
            'industry' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'githubRepos' => ['nullable', 'array'],
            'githubRepos.*' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $cleanRepos = collect($validated['githubRepos'] ?? [])
            ->map(fn ($url) => trim($url))
            ->filter()
            ->values()
            ->all();

        $this->company->update([
            'industry' => $validated['industry'] ?: null,
            'location' => $validated['location'] ?: null,
            'size' => $validated['size'] ?: null,
            'website' => $validated['website'] ?: null,
            'github_repos' => $cleanRepos ?: null,
            'description' => $validated['description'] ?: null,
        ]);

        $this->phase = 'job';
    }

    public function saveJob(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'jobDescription' => ['nullable', 'string', 'max:8000'],
            'requirements' => ['nullable', 'string', 'max:4000'],
            'jobLocation' => ['nullable', 'string', 'max:120'],
            'is_remote' => ['boolean'],
            'employment_type' => ['required', 'string', 'in:full-time,part-time,contract,internship'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'currency' => ['required', 'string', 'max:3'],
        ], [
            'title.required' => 'Give your first role a title.',
        ]);

        $status = $this->company->canPostJobs() ? JobStatus::Open : JobStatus::Draft;

        Job::create([
            'company_id' => $this->company->id,
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['jobDescription'] ?: 'Join '.$this->company->name.'.',
            'requirements' => $this->requirementsList($validated['requirements'] ?? ''),
            'location' => $validated['jobLocation'] ?: null,
            'is_remote' => $validated['is_remote'],
            'employment_type' => $validated['employment_type'],
            'salary_min' => $validated['salary_min'],
            'salary_max' => $validated['salary_max'],
            'currency' => $validated['currency'],
            'status' => $status,
            'published_at' => $status === JobStatus::Open ? now() : null,
        ]);

        Flux::toast(
            variant: 'success',
            text: $status === JobStatus::Open ? 'Your first job is live.' : 'Job saved as draft, job post credit limit reached.',
        );

        $this->redirectRoute('companies.manage', $this->company, navigate: true);
    }

    public function backToDetails(): void
    {
        $this->phase = 'details';
    }

    public function addGithubRepo(): void
    {
        $this->githubRepos[] = '';
    }

    public function removeGithubRepo(int $index): void
    {
        unset($this->githubRepos[$index]);
        $this->githubRepos = array_values($this->githubRepos);

        if (empty($this->githubRepos)) {
            $this->githubRepos = [''];
        }
    }

    public function skipJob(): void
    {
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
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <flux:icon name="check-circle" variant="micro" />
                {{ $company->name }} · Free plan active
            </div>
            <flux:heading size="xl">Finish setting up {{ $company->name }}</flux:heading>
            <flux:text class="mt-2">Add your company details, then post your first role. You can skip to the dashboard anytime.</flux:text>
        </div>

        @if ($phase === 'details')
            <form wire:submit="saveDetails" class="grid gap-5">
                <div class="flex flex-wrap items-center gap-5 rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                    <div class="relative shrink-0">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="size-16 rounded-xl object-cover" />
                        @elseif ($company->logoUrl())
                            <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }} logo" class="size-16 rounded-xl object-cover" />
                        @else
                            <div class="flex size-16 items-center justify-center rounded-xl bg-accent/10 text-lg font-bold text-accent">
                                {{ \Illuminate\Support\Str::initials($company->name, true) }}
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <flux:heading size="sm">Company logo</flux:heading>
                        <flux:text class="mt-1">Upload a square JPG, PNG, WebP or SVG up to 2 MB. Shown on job posts and your public profile.</flux:text>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <label class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-zinc-900 px-4 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                                <flux:icon name="photo" variant="micro" class="size-4" />
                                {{ __('Upload logo') }}
                                <input type="file" wire:model="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="sr-only" />
                            </label>
                            @if ($logo)
                                <flux:button variant="primary" type="button" wire:click="saveLogo" class="h-9">Save logo</flux:button>
                            @endif
                            @if ($company->logo_path)
                                <flux:button variant="subtle" type="button" wire:click="removeLogo" wire:confirm="Remove this logo?" class="h-9">Remove</flux:button>
                            @endif
                        </div>
                        @error('logo')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Industry</flux:label>
                        <flux:input wire:model="industry" placeholder="SaaS, Fintech, AI…" />
                        <flux:error name="industry" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Location</flux:label>
                        <flux:input wire:model="location" placeholder="Remote · New York, US" />
                        <flux:error name="location" />
                    </flux:field>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Team size</flux:label>
                        <x-searchable-select wire:model="size" placeholder="Select size">
                            @foreach (['1-10', '11-50', '51-200', '201-500', '500+'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </x-searchable-select>
                        <flux:error name="size" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Website</flux:label>
                        <flux:input wire:model="website" type="url" placeholder="https://acme.com" />
                        <flux:error name="website" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>GitHub Repositories</flux:label>
                    <flux:text class="mb-2">Add links to your public GitHub repositories (e.g. https://github.com/org/repo).</flux:text>
                    <div class="grid gap-2">
                        @foreach ($githubRepos as $index => $repo)
                            <div class="flex items-center gap-2">
                                <flux:input wire:model="githubRepos.{{ $index }}" type="url" placeholder="https://github.com/your-org/your-repo" class="flex-1" />
                                @if (count($githubRepos) > 1)
                                    <button type="button" wire:click="removeGithubRepo({{ $index }})" class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                                        <flux:icon name="x-mark" variant="micro" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addGithubRepo" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-accent hover:underline">
                        <flux:icon name="plus" variant="micro" />
                        Add another repo
                    </button>
                    <flux:error name="githubRepos" />
                    <flux:error name="githubRepos.*" />
                </flux:field>

                <flux:field>
                    <flux:label>About the company</flux:label>
                    <flux:textarea wire:model="description" rows="4" placeholder="What does your team build?" />
                    <flux:error name="description" />
                </flux:field>

                <div class="flex items-center justify-between gap-4">
                    <flux:button type="button" variant="ghost" wire:click="skipJob">Skip for now</flux:button>
                    <flux:button type="submit" variant="primary">Continue to your first job</flux:button>
                </div>
            </form>
        @else
            <form wire:submit="saveJob" class="grid gap-5">
                <flux:field>
                    <flux:label>Job title</flux:label>
                    <flux:input wire:model="title" placeholder="Senior Backend Engineer" autofocus />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="jobDescription" rows="5" placeholder="What will the role own? What does success look like?" />
                    <flux:error name="jobDescription" />
                </flux:field>

                <flux:field>
                    <flux:label>Requirements</flux:label>
                    <flux:textarea wire:model="requirements" rows="4" placeholder="One requirement per line&#10;• 5+ years of Laravel/PHP&#10;• Real-time systems experience" />
                    <flux:error name="requirements" />
                </flux:field>

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Location</flux:label>
                        <flux:input wire:model="jobLocation" placeholder="Remote · New York, US" />
                        <flux:error name="jobLocation" />
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
                            @foreach (['USD', 'EUR', 'GBP', 'INR'] as $code)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </x-searchable-select>
                    </flux:field>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <flux:button type="button" variant="ghost" wire:click="skipJob">Skip for now</flux:button>
                    <div class="flex items-center gap-3">
                        <flux:button type="button" variant="subtle" wire:click="backToDetails">Back</flux:button>
                        <flux:button type="submit" variant="primary">Publish job</flux:button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>