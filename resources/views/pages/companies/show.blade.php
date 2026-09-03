<?php

use App\Enums\CompanyStatus;
use App\Enums\JobStatus;
use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Company')] class extends Component {
    public Company $company;

    public function mount(Company $company): void
    {
        abort_unless($company->status === CompanyStatus::Approved || $company->isMember(auth()->user()), 404);

        $this->company = $company;
    }

    #[Computed]
    public function openJobs()
    {
        return $this->company
            ->jobs()
            ->where('status', JobStatus::Open)
            ->latest('published_at')
            ->get();
    }

    #[Computed]
    public function isMember(): bool
    {
        return auth()->check() && $this->company->isMember(auth()->user());
    }

    #[Computed]
    public function isOwner(): bool
    {
        return auth()->check() && $this->company->isOwner(auth()->user());
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div class="rounded-2xl bg-zinc-100 p-6 dark:bg-white/5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-xl font-bold text-accent">
                        <x-company-logo :company="$company" size="lg" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="xl" class="!text-2xl">{{ $company->name }}</flux:heading>
                            @if ($company->isApproved())
                                <flux:badge color="emerald" inset="top bottom">Verified</flux:badge>
                            @endif
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-sm text-zinc-500">
                            @if ($company->industry)
                                <span class="inline-flex items-center gap-1"><flux:icon name="tag" variant="micro" /> {{ $company->industry }}</span>
                            @endif
                            @if ($company->location)
                                <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $company->location }}</span>
                            @endif
                            @if ($company->size)
                                <span class="inline-flex items-center gap-1"><flux:icon name="users" variant="micro" /> {{ $company->size }} people</span>
                            @endif
                        </div>
                    </div>
                </div>
                @if ($this->isMember)
                    <flux:button variant="primary" :href="route('companies.manage', $company)" wire:navigate>
                        Manage company
                    </flux:button>
                @endif
            </div>

            @if ($company->description)
                <p class="mt-5 max-w-3xl leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $company->description }}</p>
            @endif

            @if ($company->website)
                <a href="{{ $company->website }}" target="_blank" rel="noopener"
                    class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">
                    {{ $company->website }}
                    <flux:icon name="arrow-up-right" variant="micro" />
                </a>
            @endif
        </div>

        <div>
            <flux:heading size="lg">Open roles</flux:heading>
            <div class="mt-4 grid gap-3">
                @forelse ($this->openJobs as $job)
                    <a href="{{ route('jobs.show', ['company' => $company, 'job' => $job]) }}" wire:navigate
                        class="group flex flex-wrap items-center justify-between gap-3 rounded-xl bg-zinc-100 p-4 transition hover:shadow-lg hover:shadow-zinc-900/5 dark:bg-white/5">
                        <div class="min-w-0">
                            <div class="font-semibold text-zinc-900 group-hover:text-accent dark:text-white">{{ $job->title }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-zinc-500">
                                <span class="inline-flex items-center gap-1">
                                    <flux:icon name="briefcase" variant="micro" />
                                    {{ ucwords(str_replace('-', ' ', $job->employment_type ?? 'Full-time')) }}
                                </span>
                                @if ($job->location)
                                    <span class="inline-flex items-center gap-1">
                                        <flux:icon name="map-pin" variant="micro" />
                                        {{ $job->location }}
                                    </span>
                                @endif
                                @if ($job->is_remote)
                                    <span class="inline-flex items-center gap-1"><flux:icon name="globe-alt" variant="micro" /> Remote</span>
                                @endif
                                @if ($job->salaryRange())
                                    <span class="inline-flex items-center gap-1"><flux:icon name="banknotes" variant="micro" /> {{ $job->salaryRange() }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-sm font-medium text-accent">
                            Apply
                            <flux:icon name="arrow-right" variant="micro" class="transition group-hover:translate-x-0.5" />
                        </span>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                        <flux:icon name="briefcase" class="mx-auto text-zinc-400" />
                        <flux:heading class="mt-3">No open roles right now</flux:heading>
                        <flux:text class="mt-2">Check back soon or browse other companies.</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
