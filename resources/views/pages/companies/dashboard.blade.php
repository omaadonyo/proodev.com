<?php

use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Company Dashboard')] class extends Component
{
    public Company $company;

    public function mount(Company $company): void
    {
        abort_unless($company->isMember(auth()->user()), 403);

        $this->company = $company;
    }

    #[Computed]
    public function jobs()
    {
        return $this->company->jobs()->withCount('applications')->latest()->limit(5)->get();
    }

    #[Computed]
    public function openJobsCount(): int
    {
        return $this->company->openJobsCount();
    }

    #[Computed]
    public function totalApplications(): int
    {
        return $this->company->jobs()->whereHas('applications')->withCount('applications')->get()->sum('applications_count');
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $company->name }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $company->usedJobPosts() }} of {{ $company->jobPostCredits() }} job post credits in use.
                </flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="primary" :href="route('companies.jobs.create', $company)" wire:navigate>
                    <flux:icon name="plus" variant="micro" />
                    Post a job
                </flux:button>
                <a href="{{ route('companies.onboarding', $company) }}" wire:navigate class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-zinc-100 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-white/10 dark:text-zinc-200 dark:hover:bg-white/15">
                    Company details
                </a>
                <a href="{{ route('companies.show', $company) }}" wire:navigate class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-zinc-100 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-white/10 dark:text-zinc-200 dark:hover:bg-white/15">
                    View public profile
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">Open roles</span>
                    <flux:icon name="briefcase" variant="micro" class="text-accent" />
                </div>
                <div class="mt-2 text-3xl font-bold tabular-nums">{{ $this->openJobsCount }}</div>
            </div>
            <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">Applications</span>
                    <flux:icon name="users" variant="micro" class="text-accent" />
                </div>
                <div class="mt-2 text-3xl font-bold tabular-nums">{{ $this->totalApplications }}</div>
            </div>
        </div>

        {{-- Recruiting toolkit --}}
        <div>
            <div class="flex items-center justify-between">
                <flux:heading size="sm">Recruiting toolkit</flux:heading>
                <flux:icon name="academic-cap" class="text-accent" />
            </div>
            <p class="mt-1 text-sm text-zinc-500">Proof-first hiring tools built for companies and recruiters.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $tools = [
                        ['icon' => 'document-text', 'label' => 'Applicant pipeline', 'desc' => 'Track every application in one place.', 'href' => route('companies.applicants', $company)],
                        ['icon' => 'magnifying-glass', 'label' => 'Evidence search', 'desc' => 'Find engineers by analyzed evidence and save them to talent pools.', 'href' => route('recruiter.search')],
                        ['icon' => 'briefcase', 'label' => 'Post roles', 'desc' => 'Each credit keeps one job post active.', 'href' => route('companies.jobs.create', $company)],
                    ];
                @endphp
                @foreach ($tools as $tool)
                    <a href="{{ $tool['href'] }}" wire:navigate class="group rounded-lg bg-zinc-100 p-4 transition hover:bg-zinc-200 dark:bg-white/5 dark:hover:bg-white/10">
                        <div class="flex items-center gap-2">
                            <flux:icon :name="$tool['icon']" variant="micro" class="text-accent" />
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $tool['label'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">{{ $tool['desc'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <flux:heading size="sm">Recent job posts</flux:heading>
                <a href="{{ route('companies.manage', $company) }}" wire:navigate class="text-xs font-medium text-accent hover:underline">Manage</a>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($this->jobs as $job)
                    <div class="rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <span class="font-semibold text-zinc-900 dark:text-white">{{ $job->title }}</span>
                                <flux:badge size="sm" inset="top bottom" :color="$job->status === JobStatus::Open ? 'green' : 'zinc'">{{ $job->status->label() }}</flux:badge>
                            </div>
                            <span class="text-xs text-zinc-500">{{ $job->applications_count }} application{{ $job->applications_count === 1 ? '' : 's' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600">
                        <flux:text class="text-sm">No jobs yet - post your first role to start receiving applications.</flux:text>
                        <flux:button class="mt-3" size="sm" variant="primary" :href="route('companies.jobs.create', $company)" wire:navigate>Post a job</flux:button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
