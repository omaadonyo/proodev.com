<?php

use App\Enums\ApplicationStatus;
use App\Enums\CompanyPlan;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\Payment;
use App\Services\BillingService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Company')] class extends Component
{
    public Company $company;

    public ?int $activeJobId = null;

    public string $note = '';

    public function mount(Company $company): void
    {
        abort_unless($company->isMember(auth()->user()), 403);

        $this->company = $company;
    }

    public function buyJobPosts(int $posts): void
    {
        app(BillingService::class)->createJobPostsPayment($this->company, $posts);

        unset($this->pendingJobPosts);

        Flux::toast(variant: 'success', text: 'Job post credits checkout created — credits unlock once payment is confirmed.');

        $this->redirectRoute('companies.manage', $this->company, navigate: true);
    }

    public function upgrade(string $plan): void
    {
        $planEnum = CompanyPlan::tryFrom($plan);

        abort_unless(in_array($planEnum, [CompanyPlan::Recruiter, CompanyPlan::Intelligence], true), 403);

        app(BillingService::class)->createSubscriptionPayment($this->company, $planEnum);

        unset($this->pendingPayments);

        Flux::toast(variant: 'success', text: 'Upgrade checkout created — your plan activates once payment is confirmed.');

        $this->redirectRoute('companies.manage', $this->company, navigate: true);
    }

    public function verifyCompany(): void
    {
        app(BillingService::class)->createCompanyVerificationPayment($this->company);

        unset($this->pendingVerification);

        Flux::toast(variant: 'success', text: 'Company verification checkout created — your verified badge unlocks once payment is confirmed.');

        $this->redirectRoute('companies.manage', $this->company, navigate: true);
    }

    public function toggleJob(int $jobId): void
    {
        $job = $this->company->jobs()->findOrFail($jobId);

        if ($job->status === JobStatus::Open) {
            $job->update(['status' => JobStatus::Closed]);
        } else {
            if (! $this->company->canPostJobs()) {
                Flux::toast(variant: 'warning', text: 'Plan limit reached — close a job or upgrade to reopen more.');

                return;
            }

            $job->update([
                'status' => JobStatus::Open,
                'published_at' => $job->published_at ?? now(),
            ]);
        }

        unset($this->jobs);
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

    #[Computed]
    public function pendingPayments()
    {
        return Payment::query()
            ->where('company_id', $this->company->id)
            ->where('purpose', 'subscription')
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    #[Computed]
    public function pendingJobPosts()
    {
        return Payment::query()
            ->where('company_id', $this->company->id)
            ->where('purpose', 'job-posts')
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    #[Computed]
    public function pendingVerification()
    {
        return Payment::query()
            ->where('company_id', $this->company->id)
            ->where('purpose', 'verification')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    #[Computed]
    public function jobPostBundles(): array
    {
        return collect(config('billing.companies.job_posts.bundles', []))
            ->map(fn (array $bundle) => [
                'posts' => (int) $bundle['posts'],
                'price' => (int) $bundle['price'],
            ])
            ->values()
            ->all();
    }

    #[Computed]
    public function recruiterPrice(): int
    {
        return (int) config('billing.companies.recruiter.price', 299);
    }

    #[Computed]
    public function intelligencePrice(): int
    {
        return (int) config('billing.companies.intelligence.price', 199);
    }

    #[Computed]
    public function intelligenceFirstMonth(): int
    {
        return (int) config('billing.companies.intelligence.first_month_price', 599);
    }

    #[Computed]
    public function verificationPrice(): int
    {
        return (int) config('billing.companies.verification.price', 299);
    }

    #[Computed]
    public function planRenewsAt(): ?string
    {
        if (! $this->company->plan_renews_at) {
            return null;
        }

        return $this->company->plan_renews_at->format('M j, Y');
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl">{{ $company->name }}</flux:heading>
                    <flux:badge inset="top bottom" :color="$company->status->value === 'approved' ? 'green' : 'amber'">{{ ucfirst($company->status->value) }}</flux:badge>
                    <flux:badge inset="top bottom" color="zinc">{{ $company->plan->label() }}</flux:badge>
                </div>
                <flux:text class="mt-1">
                    @if ($company->plan->isPaid())
                        {{ $company->plan->label() }} plan — unlimited job posts.
                    @else
                        {{ $company->usedJobPosts() }} of {{ $company->jobPostCredits() }} job post credits used — buy more any time.
                    @endif
                </flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="primary" :href="route('companies.jobs.create', $company)" wire:navigate>
                    <flux:icon name="plus" variant="micro" />
                    Post a job
                </flux:button>
                <a href="{{ route('companies.onboarding', $company) }}" wire:navigate class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-black dark:text-zinc-200 dark:hover:bg-zinc-900">
                    Company details
                </a>
                <a href="{{ route('companies.show', $company) }}" wire:navigate class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-black dark:text-zinc-200 dark:hover:bg-zinc-900">
                    View public profile
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Current plan</div>
                <div class="mt-1.5 flex items-center gap-2">
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $company->plan->label() }}</span>
                    @if ($company->plan->isPaid())
                        <flux:badge size="sm" variant="success" inset="top bottom">Active</flux:badge>
                    @else
                        <flux:badge size="sm" inset="top bottom" color="zinc">Free</flux:badge>
                    @endif
                </div>
                <div class="mt-1 text-xs text-zinc-500">
                    @if ($company->plan->isPaid())
                        Unlimited job posts
                    @else
                        {{ $company->jobPostCredits() }} job post credit{{ $company->jobPostCredits() === 1 ? '' : 's' }}
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Job post credits</div>
                <div class="mt-1.5 text-lg font-bold tabular-nums text-zinc-900 dark:text-white">
                    @if ($company->plan->isPaid())
                        Unlimited
                    @else
                        {{ $company->usedJobPosts() }} / {{ $company->jobPostCredits() }}
                    @endif
                </div>
                <div class="mt-1 text-xs text-zinc-500">{{ $company->plan->isPaid() ? 'active roles' : 'used' }}</div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Plan renews</div>
                @if ($company->plan->isPaid() && $this->planRenewsAt)
                    <div class="mt-1.5 text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ $this->planRenewsAt }}</div>
                    <div class="mt-1 text-xs text-zinc-500">then ${{ number_format($company->plan === \App\Enums\CompanyPlan::Intelligence ? $this->intelligencePrice : $this->recruiterPrice) }}/month</div>
                @elseif ($company->plan->isPaid())
                    <div class="mt-1.5 text-lg font-bold text-zinc-900 dark:text-white">Active</div>
                    <div class="mt-1 text-xs text-zinc-500">renewal date pending confirmation</div>
                @else
                    <div class="mt-1.5 text-lg font-bold text-zinc-900 dark:text-white">—</div>
                    <div class="mt-1 text-xs text-zinc-500">upgrade to start a plan</div>
                @endif
            </div>
        </div>

        @if ($this->pendingVerification)
            <div class="rounded-xl border border-amber-300/40 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                <div class="font-semibold">Company verification checkout awaiting confirmation</div>
                <div class="mt-1 text-xs">Once an admin confirms the ${{ number_format($this->verificationPrice) }} payment, your verified badge unlocks.</div>
            </div>
        @endif

        @if ($this->pendingPayments->isNotEmpty())
            <div class="rounded-xl border border-amber-300/40 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                <div class="font-semibold">Upgrade checkout awaiting confirmation</div>
                <div class="mt-1 text-xs">This build uses manual checkout — your plan activates once an admin confirms the payment.</div>
            </div>
        @endif

        @if ($this->pendingJobPosts->isNotEmpty())
            <div class="rounded-xl border border-amber-300/40 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                <div class="font-semibold">Job post credits checkout awaiting confirmation</div>
                <div class="mt-1 text-xs">Once an admin confirms the payment, your credits unlock and you can publish more roles.</div>
            </div>
        @endif

        @unless ($company->plan->isPaid())
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <flux:heading size="sm">Buy job post credits</flux:heading>
                        <flux:text>Each credit lets you keep one job post active. Pay once, no recurring fees.</flux:text>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold tabular-nums">{{ $company->usedJobPosts() }} / {{ $company->jobPostCredits() }}</div>
                        <div class="text-xs text-zinc-500">used</div>
                    </div>
                </div>

                <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                    <div class="h-full rounded-full bg-accent transition-all" style="width: {{ min(100, ($company->usedJobPosts() / max(1, $company->jobPostCredits())) * 100) }}%"></div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($this->jobPostBundles as $bundle)
                        <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ $bundle['posts'] }} job post{{ $bundle['posts'] === 1 ? '' : 's' }}</span>
                            </div>
                            <div class="mt-2 text-3xl font-bold tabular-nums">${{ number_format($bundle['price']) }}</div>
                            <div class="text-xs text-zinc-500">one-time · 1 credit added instantly on confirmation</div>
                            <flux:button class="mt-4 w-full" variant="primary" wire:click="buyJobPosts({{ $bundle['posts'] }})">
                                Buy {{ $bundle['posts'] }} job post
                            </flux:button>
                        </div>
                    @endforeach

                    <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Recruiter Intelligence Suite</span>
                            <flux:badge size="sm" variant="success" inset="top bottom">Best value</flux:badge>
                        </div>
                        <div class="mt-2 text-3xl font-bold tabular-nums">${{ number_format($this->intelligenceFirstMonth) }}</div>
                        <div class="text-xs text-zinc-500">first month · unlimited job posts · then ${{ number_format($this->intelligencePrice) }}/month</div>
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach (config('billing.companies.intelligence.features', []) as $feature)
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ $feature }}</span>
                            @endforeach
                        </div>
                        <flux:button class="mt-4 w-full" variant="primary" wire:click="upgrade('intelligence')">Get the Intelligence Suite</flux:button>
                    </div>
                </div>
            </div>
        @endunless

        <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-semibold">Company verification</div>
                    <div class="text-xs text-zinc-500">A one-time ${{ number_format($this->verificationPrice) }} fee unlocks a verified badge on your company profile and priority listing in talent search.</div>
                </div>
                @if ($company->isApproved())
                    <flux:badge color="emerald" inset="top bottom">Verified</flux:badge>
                @else
                    <flux:button variant="primary" wire:click="verifyCompany">Verify company - ${{ number_format($this->verificationPrice) }}</flux:button>
                @endif
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <flux:heading size="sm">Job posts</flux:heading>
                <span class="text-xs text-zinc-500">{{ $company->plan->isPaid() ? 'Unlimited' : $company->usedJobPosts().' of '.$company->jobPostCredits().' credits in use' }}</span>
            </div>

            <div class="mt-4 grid gap-3">
                @forelse ($this->jobs as $job)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $job->title }}</span>
                                    <flux:badge size="sm" inset="top bottom" :color="$job->status->value === 'open' ? 'green' : 'zinc'">{{ ucfirst($job->status->value) }}</flux:badge>
                                </div>
                                <div class="mt-0.5 text-xs text-zinc-500">{{ $job->applications_count }} application{{ $job->applications_count === 1 ? '' : 's' }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($job->status === 'open')
                                    <flux:button size="sm" variant="subtle" wire:click="toggleJob({{ $job->id }})">Close</flux:button>
                                @else
                                    <flux:button size="sm" variant="subtle" wire:click="toggleJob({{ $job->id }})">Reopen</flux:button>
                                @endif
                                <flux:button size="sm" variant="subtle" :href="route('companies.jobs.edit', ['company' => $company, 'job' => $job])" wire:navigate>Edit</flux:button>
                                <flux:button size="sm" variant="{{ $this->activeJobId === $job->id ? 'primary' : 'subtle' }}" wire:click="selectJob({{ $job->id }})">Applications</flux:button>
                            </div>
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
                                                        <flux:badge size="sm" inset="top bottom" :color="match ($application->status->value) {
                                                            'shortlisted' => 'green',
                                                            'rejected' => 'red',
                                                            'hired' => 'sky',
                                                            default => 'zinc',
                                                        }">{{ $application->status->label() }}</flux:badge>
                                                    </div>
                                                    <a href="{{ route('passport', $application->user->handle()) }}" wire:navigate class="text-xs text-accent hover:underline">{{ $application->user->handle() }}</a>
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
                                        <p class="text-sm text-zinc-500">No applications yet for this role.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                        <flux:icon name="briefcase" class="mx-auto text-zinc-400" />
                        <flux:heading class="mt-3">No jobs yet</flux:heading>
                        <flux:text class="mt-2">Post your first role to start receiving applications.</flux:text>
                        <flux:button class="mt-4" variant="primary" :href="route('companies.jobs.create', $company)" wire:navigate>Post a job</flux:button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
