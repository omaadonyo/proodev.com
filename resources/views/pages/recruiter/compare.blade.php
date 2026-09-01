<?php

use App\Livewire\Concerns\ExportsSelectedRows;
use App\Livewire\Concerns\InteractsWithTalentPools;
use App\Models\TalentPool;
use App\Models\User;
use App\Services\Recruiter\CandidateComparisonService;
use App\Services\Recruiter\WorkspaceService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Compare Candidates')] class extends Component
{
    use ExportsSelectedRows;
    use InteractsWithTalentPools;

    public string $search = '';

    public string $activePoolId = '';

    public array $selected = [];

    public bool $compared = false;

    public function mount(): void
    {
        $ids = collect(explode(',', (string) request()->query('ids')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->take(3)
            ->all();

        $this->selected = $ids;
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen(trim($this->search)) < 2) {
            return collect();
        }

        return User::query()
            ->where('public_passport', true)
            ->where(fn ($q) => $q
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('username', 'like', '%'.trim($this->search).'%')
                ->orWhere('headline', 'like', '%'.trim($this->search).'%'))
            ->orderByDesc('reputation_score')
            ->limit(8)
            ->get()
            ->reject(fn ($user) => in_array($user->id, $this->selected, true))
            ->values();
    }

    #[Computed]
    public function selectedCandidates()
    {
        return $this->selected !== []
            ? User::whereIn('id', $this->selected)->get()
            : collect();
    }

    #[Computed]
    public function poolMembers()
    {
        if ($this->activePoolId === '') {
            return collect();
        }

        $workspace = app(WorkspaceService::class)->current(auth()->user());

        $pool = TalentPool::with(['members.candidate'])
            ->where('id', (int) $this->activePoolId)
            ->where(function ($q) use ($workspace) {
                $q->where('recruiter_id', auth()->id());

                if ($workspace) {
                    $q->orWhere('workspace_id', $workspace->id);
                }
            })
            ->first();

        if (! $pool) {
            return collect();
        }

        return $pool->members
            ->map(fn ($member) => $member->candidate)
            ->filter()
            ->reject(fn (User $u) => in_array($u->id, $this->selected, true))
            ->values();
    }

    public function addFromPool(int $id): void
    {
        $this->addCandidate($id);
    }

    #[Computed]
    public function comparison()
    {
        if (! $this->compared || count($this->selected) < 2) {
            return null;
        }

        return app(CandidateComparisonService::class)->compare(
            $this->selectedCandidates->all(),
            auth()->user(),
        );
    }

    public function addCandidate(int $id): void
    {
        if (count($this->selected) >= 3) {
            $this->dispatch('toast', message: 'Compare up to 3 candidates.', variant: 'warning');

            return;
        }

        if (! in_array($id, $this->selected, true)) {
            $this->selected[] = $id;
            $this->search = '';
        }
    }

    public function removeCandidate(int $id): void
    {
        $this->selected = array_values(array_diff($this->selected, [$id]));
        $this->compared = false;
    }

    public function runComparison(): void
    {
        if (count($this->selected) < 2) {
            $this->dispatch('toast', message: 'Select at least 2 candidates.', variant: 'warning');

            return;
        }

        $this->selectedIds = $this->selected;
        $this->compared = true;
    }

    protected function selectableIds(): array
    {
        return $this->comparison !== null ? $this->selected : [];
    }

    protected function exportData(): array
    {
        if ($this->comparison === null) {
            return [['Signal'], []];
        }

        $headings = ['Signal'];

        foreach ($this->comparison['candidates'] as $candidate) {
            $headings[] = $candidate['developer']['name'];
        }

        $rows = [];

        foreach ($this->comparison['matrix'] as $row) {
            $rows[] = array_merge([$row['label']], array_map('strval', $row['values']));
        }

        return [$headings, $rows];
    }

    protected function exportTitle(): string
    {
        return 'Candidate comparison';
    }

    protected function exportBasename(): string
    {
        return 'candidate-comparison';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Compare Candidates</flux:heading>
        <flux:text>Side-by-side, evidence-backed comparison of up to three candidates.</flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm">1 - Select candidates</flux:heading>
        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            @forelse ($this->selectedCandidates as $candidate)
                <div class="flex items-center gap-3 rounded-lg border border-accent/40 bg-accent/5 p-3">
                    <flux:avatar :src="$candidate->avatarUrl()" :alt="$candidate->name" circle class="size-9" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <div class="truncate text-sm font-medium">{{ $candidate->name }}</div>
                            <x-verified-badge :user="$candidate" compact />
                        </div>
                        <div class="truncate text-xs text-zinc-500">{{ $candidate->headline }}</div>
                    </div>
                    <button type="button" wire:click="removeCandidate({{ $candidate->id }})" class="text-zinc-400 hover:text-red-500">
                        <flux:icon name="x-mark" variant="micro" />
                    </button>
                </div>
            @empty
                <flux:text class="sm:col-span-3">Search below and add 2-3 candidates.</flux:text>
            @endforelse
        </div>

        @if (count($this->selected) < 3)
            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                <div>
                    <flux:label>Search engineers</flux:label>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search engineers by name or headline..." />
                    @if ($this->searchResults->isNotEmpty())
                        <div class="mt-2 grid gap-2">
                            @foreach ($this->searchResults as $user)
                                <button type="button" wire:click="addCandidate({{ $user->id }})" class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 text-left transition hover:border-accent dark:border-zinc-700">
                                    <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-8" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <div class="truncate text-sm font-medium">{{ $user->name }}</div>
                                            <x-verified-badge :user="$user" compact />
                                        </div>
                                        <div class="truncate text-xs text-zinc-500">{{ $user->headline }}</div>
                                    </div>
                                    <flux:icon name="plus" variant="micro" class="text-zinc-400" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <flux:label>Add from talent pool</flux:label>
                    <x-searchable-select wire:model.live="activePoolId">
                        <option value="">Choose a pool…</option>
                        @foreach ($this->pools as $pool)
                            <option value="{{ $pool->id }}">{{ $pool->name }} ({{ $pool->members_count }})</option>
                        @endforeach
                    </x-searchable-select>

                    @if ($this->poolMembers->isNotEmpty())
                        <div class="mt-2 grid gap-2">
                            @foreach ($this->poolMembers as $user)
                                <button type="button" wire:key="pool-{{ $user->id }}" wire:click="addFromPool({{ $user->id }})" class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 text-left transition hover:border-accent dark:border-zinc-700">
                                    <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-8" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <div class="truncate text-sm font-medium">{{ $user->name }}</div>
                                            <x-verified-badge :user="$user" compact />
                                        </div>
                                        <div class="truncate text-xs text-zinc-500">{{ $user->headline }}</div>
                                    </div>
                                    <flux:icon name="plus" variant="micro" class="text-zinc-400" />
                                </button>
                            @endforeach
                        </div>
                    @elseif ($this->activePoolId !== '')
                        <flux:text class="mt-2">No more candidates in this pool, everyone is already selected or the pool is empty.</flux:text>
                    @endif
                </div>
            </div>
        @endif

        <div class="mt-4 flex items-center gap-2">
            <flux:button variant="primary" wire:click="runComparison">
                <flux:icon name="scale" variant="micro" />
                Compare {{ count($this->selected) }} candidate{{ count($this->selected) === 1 ? '' : 's' }}
            </flux:button>
            @if ($this->comparison)
                <flux:button variant="ghost" size="sm" wire:click="$set('compared', false)">Reset</flux:button>
            @endif
        </div>
    </div>

    @if ($this->comparison)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="sm">2 - Result</flux:heading>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="document-arrow-down" variant="micro" />
                        PDF
                    </button>
                    <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="table-cells" variant="micro" />
                        Excel
                    </button>
                </div>
            </div>
            @if ($this->comparison['winner'])
                <div class="mt-3 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <flux:icon name="trophy" class="text-emerald-500" />
                    <div>
                        <div class="font-semibold">Recommended: {{ $this->comparison['winner']['name'] }}</div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $this->comparison['summary'] }}</p>
                    </div>
                </div>
            @endif

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="py-2 pr-4 text-left font-medium text-zinc-500">Signal</th>
                            @foreach ($this->comparison['candidates'] as $candidate)
                                <th class="px-3 py-2 text-left font-medium">
                                    <a href="{{ route('recruiter.candidates.show', $candidate['developer']['id']) }}" wire:navigate class="hover:text-accent">{{ $candidate['developer']['name'] }}</a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->comparison['matrix'] as $row)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2.5 pr-4 text-zinc-500">{{ $row['label'] }}</td>
                                @foreach ($row['values'] as $value)
                                    <td class="px-3 py-2.5">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($this->comparison['candidates'] as $candidate)
                    <flux:button size="sm" :href="route('recruiter.candidates.show', $candidate['developer']['id'])" wire:navigate>
                        View {{ $candidate['developer']['name'] }}
                    </flux:button>
                @endforeach
            </div>
        </div>
    @endif
</div>
