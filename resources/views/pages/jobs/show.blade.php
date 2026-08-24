<?php

use App\Enums\CompanyStatus;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Job')] class extends Component {
    public Company $company;

    public Job $job;

    public function mount(Company $company, Job $job): void
    {
        abort_unless($job->company_id === $company->id, 404);

        $visible = $job->status === JobStatus::Open
            && $company->status === CompanyStatus::Approved;

        abort_unless($visible || (auth()->check() && $company->isMember(auth()->user())), 404);

        $this->company = $company;
        $this->job = $job;
    }

    #[Computed]
    public function hasApplied(): bool
    {
        return auth()->check() && $this->job->applications()->where('user_id', auth()->id())->exists();
    }
}
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="grid gap-6">
        <a href="{{ route('jobs.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
            <flux:icon name="arrow-left" variant="micro" />
            All jobs
        </a>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <x-company-logo :company="$company" size="lg" />
                    <div>
                        <flux:heading size="xl" class="!text-2xl">{{ $job->title }}</flux:heading>
                        <a href="{{ route('companies.show', $company) }}" wire:navigate class="text-sm font-medium text-accent hover:underline">{{ $company->name }}</a>
                    </div>
                </div>
                @if ($this->hasApplied)
                    <flux:badge size="sm" inset="top bottom" color="emerald">Applied</flux:badge>
                @endif
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                    <flux:icon name="briefcase" variant="micro" /> {{ ucwords(str_replace('-', ' ', $job->employment_type ?? 'Full-time')) }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                    <flux:icon name="map-pin" variant="micro" /> {{ $job->is_remote ? 'Remote' : ($job->location ?: 'On-site') }}
                </span>
                @if ($job->salaryRange())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 text-xs font-medium text-accent">
                        <flux:icon name="banknotes" variant="micro" /> {{ $job->salaryRange() }}
                    </span>
                @endif
            </div>

            <div class="mt-6">
                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">About the role</div>
                <div class="mt-2 whitespace-pre-line leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $job->description }}</div>
            </div>

            @if ($job->requirements)
                <div class="mt-6">
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Requirements</div>
                    <ul class="mt-2 grid gap-2">
                        @foreach ($job->requirements as $requirement)
                            <li class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <flux:icon name="check-circle" variant="micro" class="mt-0.5 text-emerald-500" />
                                {{ $requirement }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-8 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                @if ($this->hasApplied)
                    <div class="flex flex-wrap items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                        <flux:icon name="check-badge" variant="micro" />
                        You've already applied to this role. Track it in My Applications.
                    </div>
                @else
                    @auth
                        <flux:button variant="primary" :href="route('jobs.apply', ['company' => $company, 'job' => $job])" wire:navigate>
                            Apply for this role
                        </flux:button>
                    @else
                        <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-500">
                            Sign in to apply with your DevID as proof of your work.
                            <flux:button variant="primary" size="sm" :href="route('login')">Sign in</flux:button>
                        </div>
                    @endauth
                @endif
            </div>
        </div>
    </div>
</div>