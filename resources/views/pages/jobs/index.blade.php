<?php

use App\Enums\CompanyStatus;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Services\JobMatchService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Jobs')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $companyFilter = null;

    public ?string $employmentFilter = null;

    public string $sort = 'match';

    public ?int $analyzingId = null;

    public string $analysisError = '';

    public function updatedCompanyFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEmploymentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function canMatch(): bool
    {
        return auth()->check() && ! auth()->user()->isCompanyAccount();
    }

    #[Computed]
    public function jobs()
    {
        return $this->baseQuery()
            ->latest('published_at')
            ->paginate(15);
    }

    #[Computed]
    public function results(): Collection
    {
        $service = app(JobMatchService::class);
        $user = auth()->user();
        $profileKeywords = $user ? $service->profileKeywords($user) : [];

        $rows = $this->jobs->getCollection()->map(function (Job $job) use ($service, $user, $profileKeywords) {
            $match = $user ? $service->cached($user, $job) : null;

            return [
                'job' => $job,
                'match' => $match,
                'preview' => ($match || ! $user || ! $this->canMatch) ? null : $service->quickScoreWithProfile($profileKeywords, $job),
            ];
        });

        if ($this->sort === 'match' && $this->canMatch) {
            $rows = $rows->sortByDesc(fn (array $row) => $row['match']?->score ?? $row['preview']['score'] ?? 0)->values();
        }

        return $rows;
    }

    public function analyze(int $jobId): void
    {
        if (! $this->canMatch) {
            return;
        }

        $this->analyzingId = $jobId;
        $this->analysisError = '';

        $job = $this->baseQuery()->whereKey($jobId)->first();

        if (! $job) {
            return;
        }

        try {
            app(JobMatchService::class)->match(auth()->user(), $job, force: true);

            Flux::toast(variant: 'success', text: 'AI match analysis complete.');
        } catch (Throwable) {
            $this->analysisError = 'Something went wrong while analyzing this role. Please try again.';
        } finally {
            $this->analyzingId = null;
            unset($this->results);
        }
    }

    #[Computed]
    public function companies(): array
    {
        return Company::query()
            ->where('status', CompanyStatus::Approved)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function baseQuery()
    {
        return Job::query()
            ->where('status', JobStatus::Open)
            ->whereHas('company', fn ($q) => $q->where('status', CompanyStatus::Approved))
            ->with('company')
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', '%'.trim($this->search).'%')
                ->orWhere('description', 'like', '%'.trim($this->search).'%')))
            ->when($this->companyFilter, fn ($q) => $q->where('company_id', $this->companyFilter))
            ->when($this->employmentFilter, fn ($q) => $q->where('employment_type', $this->employmentFilter));
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Open roles</flux:heading>
            <flux:text>Companies hiring from evidence-backed developers - scouted against your profile with AI.</flux:text>
        </div>

        @if ($this->canMatch)
            @if ($analysisError)
                <div class="rounded-xl border border-rose-300 bg-rose-500/10 p-4 text-sm text-rose-700 dark:border-rose-700 dark:text-rose-300">
                    {{ $analysisError }}
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-accent/20 bg-accent/5 p-3 text-xs text-zinc-600 dark:text-zinc-300">
                <flux:icon name="sparkles" variant="micro" class="text-accent" />
                <span>Each role is scored against your evidence-backed profile. Scores under 100 are quick estimates - run <strong>Analyze</strong> for a deep dive.</span>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-2">
            <div class="w-full sm:w-72">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search roles..." />
            </div>
            <x-searchable-select wire:model.live="companyFilter" size="sm" placeholder="All companies" class="w-full sm:w-48">
                <option value="">All companies</option>
                @foreach ($this->companies as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-searchable-select>
            <x-searchable-select wire:model.live="employmentFilter" size="sm" placeholder="All types" class="w-full sm:w-40">
                <option value="">All types</option>
                @foreach (['full-time' => 'Full-time', 'part-time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-searchable-select>
            @if ($this->canMatch)
                <x-searchable-select wire:model.live="sort" size="sm" placeholder="Best match" class="w-full sm:w-40">
                    <option value="match">Best match</option>
                    <option value="latest">Latest</option>
                </x-searchable-select>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5">
            @forelse ($this->results as $row)
                @php($job = $row['job'])
                @php($match = $row['match'])
                @php($preview = $row['preview'])
                @php($score = $match?->score ?? $preview['score'] ?? 0)

                <div class="group flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-900 {{ $loop->first ? '' : 'border-t border-zinc-100 dark:border-zinc-700/60' }}">
                    <x-company-logo :company="$job->company" />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <a href="{{ route('jobs.show', ['company' => $job->company, 'job' => $job]) }}" wire:navigate class="truncate font-semibold text-zinc-900 transition group-hover:text-accent dark:text-white">{{ $job->title }}</a>
                            @if ($this->canMatch)
                                @if ($match)
                                    <flux:badge size="sm" inset="top bottom" color="{{ $match->generated_by === 'ai' ? 'indigo' : 'emerald' }}">
                                        {{ $match->generated_by === 'ai' ? 'AI analyzed' : 'Scouted' }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" inset="top bottom" color="zinc">Quick estimate</flux:badge>
                                @endif
                            @endif
                        </div>
                        <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-zinc-500">
                            <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $job->company->name }}</span>
                            <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $job->is_remote ? 'Remote' : ($job->location ?: 'On-site') }}</span>
                            <span class="inline-flex items-center gap-1"><flux:icon name="briefcase" variant="micro" /> {{ ucwords(str_replace('-', ' ', $job->employment_type ?? 'Full-time')) }}</span>
                            @if ($job->salaryRange())
                                <span class="tabular-nums">{{ $job->salaryRange() }}</span>
                            @endif
                            @if ($job->published_at)
                                <span class="inline-flex items-center gap-1"><flux:icon name="clock" variant="micro" /> Posted {{ $job->published_at->diffForHumans() }}</span>
                            @endif
                            @if ($job->deadline)
                                <span class="inline-flex items-center gap-1"><flux:icon name="calendar-days" variant="micro" /> Deadline {{ $job->deadline->format('M j, Y') }}</span>
                            @endif
                        </div>
                        @if ($this->canMatch && ($match || $preview))
                            <div class="mt-1 line-clamp-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $match?->summary ?? $preview['summary'] ?? '' }}
                            </div>
                        @endif
                        @php($jobTags = $job->skillTags())
                        @if ($jobTags !== [])
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($jobTags as $tag)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                                        <x-tech-logo :name="$tag" class="size-3.5 shrink-0" />
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($this->canMatch)
                        <div
                            class="flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold tabular-nums
                                {{ $score >= 70 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($score >= 35 ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400') }}"
                            title="Match score"
                        >
                            <flux:icon name="sparkles" variant="micro" />
                            {{ $score }}/100
                        </div>
                    @endif

                    <div class="flex items-center gap-1.5">
                        @if ($this->canMatch && ! $match)
                            <flux:button size="sm" variant="subtle" wire:click="analyze({{ $job->id }})" wire:loading.attr="disabled" wire:target="analyze({{ $job->id }})" title="Run an in-depth AI match analysis">
                                <flux:icon name="sparkles" variant="micro" />
                                <span class="hidden md:inline" wire:loading.remove wire:target="analyze({{ $job->id }})">Analyze</span>
                                <span wire:loading wire:target="analyze({{ $job->id }})">Analyzing...</span>
                            </flux:button>
                        @endif
                        <flux:button size="sm" variant="primary" :href="route('jobs.show', ['company' => $job->company, 'job' => $job])" wire:navigate>
                            View role
                        </flux:button>
                        @auth
                            <flux:button size="sm" variant="filled" :href="route('jobs.apply', ['company' => $job->company, 'job' => $job])" wire:navigate>
                                Apply
                            </flux:button>
                        @endauth
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <flux:icon name="briefcase" />
                    </div>
                    <flux:heading>No open roles match</flux:heading>
                    <flux:text class="mt-2">Try adjusting your filters or check back soon.</flux:text>
                </div>
            @endforelse
        </div>

        @if ($this->jobs->hasPages())
            <div>{{ $this->jobs->links() }}</div>
        @endif
    </div>
</div>
