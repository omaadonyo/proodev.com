<?php

use App\Enums\CompanyStatus;
use App\Enums\JobStatus;
use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Companies')] class extends Component {
    public string $search = '';

    public ?string $industry = null;

    #[Computed]
    public function companies()
    {
        return Company::query()
            ->where('status', CompanyStatus::Approved)
            ->withCount(['jobs as open_jobs_count' => fn ($q) => $q->where('status', JobStatus::Open)])
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('location', 'like', '%'.trim($this->search).'%')))
            ->when($this->industry, fn ($q) => $q->where('industry', $this->industry))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function industries(): array
    {
        return Company::query()
            ->where('status', CompanyStatus::Approved)
            ->whereNotNull('industry')
            ->distinct()
            ->orderBy('industry')
            ->pluck('industry')
            ->all();
    }
}
?>

<div class="mx-auto w-full max-w-6xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">Companies hiring</flux:heading>
                <flux:text>Evidence-backed developers, recruiting teams that care about real work.</flux:text>
            </div>
            @auth
                <flux:button variant="primary" :href="route('companies.create')" wire:navigate>
                    <flux:icon name="plus" variant="micro" />
                    Register your company
                </flux:button>
            @endauth
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="w-full sm:w-72">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search companies…" />
            </div>
            <x-searchable-select wire:model.live="industry" size="sm" placeholder="All industries" class="w-full sm:w-48">
                <option value="">All industries</option>
                @foreach ($this->industries as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </x-searchable-select>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->companies as $company)
                <a href="{{ route('companies.show', $company) }}" wire:navigate
                    class="group flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-accent/50 hover:shadow-lg hover:shadow-zinc-900/5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-sm font-bold text-accent">
                            <x-company-logo :company="$company" size="sm" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="truncate font-semibold text-zinc-900 group-hover:text-accent dark:text-white">{{ $company->name }}</span>
                                @if ($company->isApproved())
                                    <flux:badge size="sm" color="emerald" inset="top bottom">Verified</flux:badge>
                                @endif
                            </div>
                            <div class="truncate text-xs text-zinc-500">{{ $company->industry ?: 'Tech' }}@if ($company->location) · {{ $company->location }}@endif</div>
                        </div>
                    </div>
                    @if ($company->description)
                        <p class="mt-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $company->description }}</p>
                    @endif
                    <div class="mt-4 flex items-center justify-between text-xs text-zinc-500">
                        <span class="inline-flex items-center gap-1">
                            <flux:icon name="briefcase" variant="micro" />
                            {{ $company->open_jobs_count }} open {{ Str::plural('role', $company->open_jobs_count) }}
                        </span>
                        <span class="inline-flex items-center gap-1 font-medium text-accent transition group-hover:gap-2">
                            View profile
                            <flux:icon name="arrow-right" variant="micro" />
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center md:col-span-3 dark:border-zinc-600">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <flux:icon name="building-office-2" />
                    </div>
                    <flux:heading>No companies found</flux:heading>
                    <flux:text class="mt-2">Be the first to register your company and start hiring from the DevID.</flux:text>
                </div>
            @endforelse
        </div>
    </div>
</div>
