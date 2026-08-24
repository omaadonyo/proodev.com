<?php

use App\Enums\CompanyStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CreditTransaction;
use App\Models\Evidence;
use App\Models\Job;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Project;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\Vouch;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reports')] class extends Component {
    use ExportsSelectedRows;

    public string $featureSearch = '';
    public string $usageSearch = '';

    #[Computed]
    public function overview(): array
    {
        return [
            'users' => User::count(),
            'verified' => User::where('is_verified', true)->count(),
            'companies' => Company::where('status', CompanyStatus::Approved)->count(),
            'jobs' => Job::open()->count(),
            'applications' => Application::count(),
            'projects' => Project::published()->count(),
            'vouches' => Vouch::count(),
            'revenue' => (float) Payment::where('status', PaymentStatus::Paid)->sum('amount'),
        ];
    }

    #[Computed]
    public function features(): array
    {
        $rows = [
            ['key' => 'battles', 'label' => 'Battles', 'description' => 'Developer vs. developer skill battles.'],
            ['key' => 'linkedin-onboarding', 'label' => 'LinkedIn onboarding', 'description' => 'Sign up and import your profile with LinkedIn.'],
            ['key' => 'profile-completion', 'label' => 'Profile completion', 'description' => 'Guided steps that help developers finish their profile.'],
            ['key' => 'evidence-pipeline', 'label' => 'Evidence pipeline', 'description' => 'Collect, validate and score project evidence for the DevID.'],
            ['key' => 'companies', 'label' => 'Companies & careers', 'description' => 'Recruiter companies, jobs and applications.'],
            ['key' => 'credits', 'label' => 'Credits', 'description' => 'Purchase credit bundles to unlock scouting and analysis.'],
            ['key' => 'verification', 'label' => 'Verification', 'description' => 'Paid developer and company verification badges.'],
            ['key' => 'public-presence', 'label' => 'Public presence', 'description' => 'Show online status and activity to visitors.'],
        ];

        foreach ($rows as &$row) {
            $row['active'] = (bool) Feature::for(null)->active($row['key']);
        }
        unset($row);

        if (trim($this->featureSearch) !== '') {
            $needle = strtolower(trim($this->featureSearch));
            $rows = array_values(array_filter($rows, fn (array $row) => str_contains(strtolower(implode(' ', $row)), $needle)));
        }

        return $rows;
    }

    #[Computed]
    public function usage(): array
    {
        $today = now()->startOfDay();

        return [
            ['feature' => 'Users', 'total' => User::count(), 'today' => User::where('created_at', '>=', $today)->count()],
            ['feature' => 'Verified developers', 'total' => User::where('is_verified', true)->count(), 'today' => null],
            ['feature' => 'Companies', 'total' => Company::count(), 'today' => Company::where('created_at', '>=', $today)->count()],
            ['feature' => 'Jobs', 'total' => Job::count(), 'today' => Job::where('created_at', '>=', $today)->count()],
            ['feature' => 'Applications', 'total' => Application::count(), 'today' => Application::where('created_at', '>=', $today)->count()],
            ['feature' => 'Projects', 'total' => Project::count(), 'today' => Project::where('created_at', '>=', $today)->count()],
            ['feature' => 'Vouches', 'total' => Vouch::count(), 'today' => Vouch::where('created_at', '>=', $today)->count()],
            ['feature' => 'Verification requests', 'total' => VerificationRequest::count(), 'today' => VerificationRequest::where('created_at', '>=', $today)->count()],
            ['feature' => 'Paid transactions', 'total' => Payment::where('status', PaymentStatus::Paid)->count(), 'today' => Payment::where('status', PaymentStatus::Paid)->where('paid_at', '>=', $today)->count()],
            ['feature' => 'Credit transactions', 'total' => CreditTransaction::count(), 'today' => CreditTransaction::where('created_at', '>=', $today)->count()],
            ['feature' => 'Journal entries', 'total' => JournalEntry::count(), 'today' => JournalEntry::where('created_at', '>=', $today)->count()],
            ['feature' => 'Evidence items', 'total' => Evidence::count(), 'today' => Evidence::where('created_at', '>=', $today)->count()],
            ['feature' => 'Timeline events', 'total' => TimelineEvent::count(), 'today' => TimelineEvent::where('created_at', '>=', $today)->count()],
            ['feature' => 'Login events', 'total' => AuditLog::where('action', 'login')->count(), 'today' => AuditLog::where('action', 'login')->where('created_at', '>=', $today)->count()],
        ];
    }

    public function activeFeatures(): int
    {
        return count(array_filter($this->features, fn (array $row) => $row['active']));
    }

    public function updatedUsageSearch(): void
    {
        $this->selectedIds = [];
    }

    protected function selectableIds(): array
    {
        return array_keys($this->usage);
    }

    protected function exportData(): array
    {
        $rows = array_values(array_intersect_key($this->usage, array_flip($this->selectedIds)));

        $rows = array_map(fn (array $row) => [
            $row['feature'],
            $row['today'] !== null ? number_format($row['today']) : '—',
            number_format($row['total']),
        ], $rows);

        return [
            ['Resource', 'Created today', 'Total'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Platform usage overview';
    }

    protected function exportBasename(): string
    {
        return 'usage-overview';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Reports</flux:heading>
        <flux:text>Platform feature flags and usage across the whole system.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Registered users</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['users']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->overview['verified']) }} verified</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Companies &amp; jobs</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['companies']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->overview['jobs']) }} open jobs · {{ number_format($this->overview['applications']) }} applications</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Projects &amp; vouches</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['projects']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->overview['vouches']) }} total vouches</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Lifetime revenue</div>
            <div class="text-2xl font-bold tabular-nums">{{ number_format($this->overview['revenue'], 2) }} {{ config('billing.currency', 'USD') }}</div>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="sm">System features</flux:heading>
                <flux:text>{{ $this->activeFeatures() }} of {{ count($this->features) }} features enabled.</flux:text>
            </div>
            <flux:input icon="magnifying-glass" type="search" placeholder="Search features..." wire:model.live.debounce.300ms="featureSearch" class="w-full sm:w-72" />
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="px-3 py-2.5 font-medium">Feature</th>
                        <th class="px-3 py-2.5 font-medium">Description</th>
                        <th class="px-3 py-2.5 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->features as $feature)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="px-3 py-2.5">
                                <span class="font-medium">{{ $feature['label'] }}</span>
                                <span class="block font-mono text-xs text-zinc-500">{{ $feature['key'] }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-zinc-600 dark:text-zinc-300">{{ $feature['description'] }}</td>
                            <td class="px-3 py-2.5">
                                @if ($feature['active'])
                                    <flux:badge size="sm" inset="top bottom" color="emerald">Enabled</flux:badge>
                                @else
                                    <flux:badge size="sm" inset="top bottom" color="zinc">Disabled</flux:badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No features match your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="sm">Usage overview</flux:heading>
            <flux:input icon="magnifying-glass" type="search" placeholder="Search usage..." wire:model.live.debounce.300ms="usageSearch" class="w-full sm:w-72" />
            @if (count($this->selectedIds) > 0)
                <span class="text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
                <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                    <flux:icon name="document-arrow-down" variant="micro" />
                    PDF
                </button>
                <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                    <flux:icon name="table-cells" variant="micro" />
                    Excel
                </button>
            @endif
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="w-8 px-3 py-2.5 font-medium">
                            <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === count($this->usage) && count($this->usage) > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </th>
                        <th class="px-3 py-2.5 font-medium">Resource</th>
                        <th class="px-3 py-2.5 font-medium">Created today</th>
                        <th class="px-3 py-2.5 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->usage as $index => $row)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($index, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                            <td class="px-3 py-2.5">
                                <input type="checkbox" wire:click="toggleSelect({{ $index }})" {{ in_array($index, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                            </td>
                            <td class="px-3 py-2.5 font-medium">{{ $row['feature'] }}</td>
                            <td class="px-3 py-2.5 tabular-nums">{{ $row['today'] !== null ? number_format($row['today']) : '—' }}</td>
                            <td class="px-3 py-2.5 font-semibold tabular-nums">{{ number_format($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No usage data matches your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
