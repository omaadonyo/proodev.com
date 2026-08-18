<?php

use App\Enums\EvidenceStatus;
use App\Livewire\Concerns\InteractsWithTalentPools;
use App\Models\Evidence;
use App\Models\RecruiterMatch;
use App\Models\TalentPool;
use App\Models\TalentPoolMember;
use App\Models\User;
use App\Services\DiscoverService;
use App\Services\Recruiter\EvidenceSearchService;
use App\Services\Recruiter\JobMatchService;
use App\Services\Recruiter\WorkspaceService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Evidence-Based Talent Search')] class extends Component
{
    use InteractsWithTalentPools;
    use WithPagination;

    public string $query = '';

    public array $skills = [];

    public string $location = '';

    public bool $verifiedOnly = false;

    public string $technology = '';

    public string $engineeringArea = '';

    public int $minMagnitude = 0;

    public bool $applyMinMagnitude = false;

    // Job-description matching.
    public string $jobText = '';

    public ?string $jobUrl = null;

    /**
     * Kept in the URL (?match=1) so a matched Directory view is shareable and
     * reloadable. The match payload itself lives in the recruiter_matches row.
     */
    #[Url(as: 'match', except: false)]
    public bool $matchRan = false;

    public bool $matching = false;

    public bool $includeTechnologies = false;

    public string $matchError = '';

    /** @var array<int, int> */
    public array $matchedIds = [];

    /** @var array{skills: array<int, string>, technologies: array<int, string>} */
    public array $matchedKeywords = ['skills' => [], 'technologies' => []];

    public string $matchedSource = 'text';

    public string $shortlistEmail = '';

    public string $view = 'grid';

    public int $perPage = 18;

    /** @var array<int, int> */
    public array $selected = [];

    /** @var array<int, int> */
    public array $savedIds = [];

    public function mount(): void
    {
        $this->shortlistEmail = auth()->user()->email ?? '';
        $this->restoreMatch();
    }

    /**
     * Restore a previously persisted job match so the Directory and its
     * badges survive browser restarts.
     */
    private function restoreMatch(): void
    {
        $record = RecruiterMatch::activeFor(auth()->user());

        if (! $record || ($record->skills ?? []) === [] || ($record->matched_ids ?? []) === []) {
            return;
        }

        $this->matchedKeywords = [
            'skills' => $record->skills ?? [],
            'technologies' => $record->technologies ?? [],
        ];
        $this->matchedIds = array_map('intval', $record->matched_ids ?? []);
        $this->includeTechnologies = (bool) $record->include_technologies;
        $this->matchRan = true;
    }

    #[Computed]
    public function results()
    {
        // While a job match is active, the Directory is the match results.
        if ($this->matchRan && $this->matchedIds !== []) {
            return $this->matchedResultsPaginator();
        }

        return app(DiscoverService::class)->search([
            'query' => $this->query,
            'skills' => $this->skills,
            'location' => $this->location,
            'verified_only' => $this->verifiedOnly,
        ], $this->perPage);
    }

    /**
     * Matched engineers ranked by evidence-match percentage descending, so the
     * Directory surfaces the best-fit engineers first.
     *
     * @return Collection<int, User>
     */
    public function matchedEngineers(): Collection
    {
        return $this->jobMatches
            ->sortByDesc(fn (User $engineer) => $this->matchPct($engineer))
            ->values();
    }

    /**
     * Paginator over the matched engineers so the Directory keeps its normal
     * pagination, view switcher, selection and export behaviour in match mode.
     *
     * @return LengthAwarePaginator<int, User>
     */
    private function matchedResultsPaginator(): LengthAwarePaginator
    {
        $engineers = $this->matchedEngineers();

        $perPage = $this->perPage;
        $page = max(1, (int) $this->getPage());
        $total = $engineers->count();

        return new LengthAwarePaginator(
            $engineers->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    #[Computed]
    public function technologyMatches()
    {
        if (strlen(trim($this->technology)) < 2) {
            return collect();
        }

        return app(EvidenceSearchService::class)
            ->byTechnology(trim($this->technology))
            ->reject(fn (User $u) => $this->isSaved($u->id))
            ->take(30)
            ->values();
    }

    #[Computed]
    public function areaMatches()
    {
        if (strlen(trim($this->engineeringArea)) < 2) {
            return collect();
        }

        return app(EvidenceSearchService::class)
            ->byEngineeringArea(trim($this->engineeringArea))
            ->reject(fn (User $u) => $this->isSaved($u->id))
            ->take(30)
            ->values();
    }

    #[Computed]
    public function popularSkills(): array
    {
        return app(DiscoverService::class)->popularSkills();
    }

    #[Computed]
    public function jobMatches()
    {
        if (! $this->matchRan || $this->matchedIds === []) {
            return collect();
        }

        $users = User::query()
            ->visibleToPublic()
            ->with(['skills'])
            ->withCount(['evidence as evidence_count' => fn ($q) => $q->ready()])
            ->whereIn('id', $this->matchedIds)
            ->get()
            ->keyBy('id');

        return collect($this->matchedIds)
            ->reject(fn ($id) => $this->isSaved((int) $id))
            ->map(fn ($id) => $users[$id] ?? null)
            ->filter()
            ->values();
    }

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedSkills(): void
    {
        $this->resetPage();
    }

    public function updatedLocation(): void
    {
        $this->resetPage();
    }

    public function updatedVerifiedOnly(): void
    {
        $this->resetPage();
    }

    public function toggleSkill(string $slug): void
    {
        $this->skills = in_array($slug, $this->skills, true)
            ? array_values(array_diff($this->skills, [$slug]))
            : [...$this->skills, $slug];

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('query', 'skills', 'location', 'verifiedOnly', 'technology', 'engineeringArea');
        $this->resetPage();
    }

    public function runJobMatch(): void
    {
        $this->resetErrorBag();
        $this->matchError = '';
        $this->matching = true;

        $this->validate([
            'jobText' => ['nullable', 'string', 'max:20000'],
            'jobUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        if (trim($this->jobText) === '' && ! $this->jobUrl) {
            $this->matchError = 'Paste a job description or a job posting URL to match engineers.';
            $this->matching = false;

            return;
        }

        $service = app(JobMatchService::class);

        $resolved = $service->resolveText($this->jobText, $this->jobUrl);
        $keywords = $service->extractKeywords($resolved['text']);
        $matches = $service->match($keywords);

        $this->matchedKeywords = $keywords;
        $this->matchedSource = $resolved['source'];
        $this->matchedIds = $matches->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->matchRan = true;
        $this->matching = false;
        $this->resetPage();

        $this->rememberMatchContext();

        $this->dispatch('toast', message: 'Found '.count($this->matchedIds).' matching engineer'.(count($this->matchedIds) === 1 ? '' : 's').'.', variant: 'success');
    }

    public function updatedIncludeTechnologies(): void
    {
        $this->rememberMatchContext();
    }

    /**
     * Persist the active match so the evidence-match badge follows the
     * recruiter across pages and browser restarts.
     */
    private function rememberMatchContext(): void
    {
        if (! $this->matchRan || ($this->matchedKeywords['skills'] ?? []) === []) {
            return;
        }

        RecruiterMatch::setFor(auth()->user(), $this->matchedKeywords, $this->matchedIds, $this->includeTechnologies);
    }

    public function resetJobMatch(): void
    {
        $this->reset('jobText', 'jobUrl', 'matchRan', 'matchedIds', 'matchError');
        $this->matchedKeywords = ['skills' => [], 'technologies' => []];
        RecruiterMatch::clearFor(auth()->user());
        $this->resetPage();
    }

    /**
     * Share of the job posting's matched keywords the engineer covers, 0-100.
     * Skills always count; evidence technologies count when the toggle is on.
     */
    public function matchPct(User $engineer): int
    {
        $skills = $this->matchedKeywords['skills'] ?? [];
        $techs = $this->matchedKeywords['technologies'] ?? [];

        if (! $this->matchRan || ($skills === [] && $techs === [])) {
            return 0;
        }

        $ownedSkills = $engineer->skills->pluck('slug')->map(fn ($slug) => (string) $slug)->all();
        $ownedTechs = $this->includeTechnologies && $techs !== []
            ? ($this->technologyCoverage[$engineer->id] ?? [])
            : [];

        $covered = 0;
        $total = 0;

        foreach ($skills as $skill) {
            $total++;
            if (in_array($skill, $ownedSkills, true)) {
                $covered++;
            }
        }

        if ($this->includeTechnologies) {
            foreach ($techs as $tech) {
                $total++;
                if (in_array(Str::lower((string) $tech), $ownedTechs, true)) {
                    $covered++;
                }
            }
        }

        return $total > 0 ? (int) round($covered / $total * 100) : 0;
    }

    /**
     * Lowercased technologies found in the analyzed evidence of the engineers
     * currently visible in the directory, keyed by user id.
     *
     * @return array<int, array<int, string>>
     */
    #[Computed]
    public function technologyCoverage(): array
    {
        // In match mode use the matched set (not the paginator) so the ranking
        // sort below can consult coverage without recursing into results().
        $ids = ($this->matchRan && $this->matchedIds !== [])
            ? $this->jobMatches->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $this->results->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return [];
        }

        return Evidence::query()
            ->where('status', EvidenceStatus::Ready)
            ->whereIn('user_id', $ids)
            ->with('analysis')
            ->get()
            ->groupBy('user_id')
            ->mapWithKeys(function ($items, $userId) {
                $techs = $items
                    ->flatMap(fn ($item) => $item->analysis?->technologies ?? [])
                    ->map(fn ($tech) => Str::lower((string) $tech))
                    ->unique()
                    ->values()
                    ->all();

                return [(int) $userId => $techs];
            })
            ->all();
    }

    public function hasMatchBadges(): bool
    {
        return $this->matchRan && ($this->matchedKeywords['skills'] ?? []) !== [];
    }

    /**
     * Engineers shown in the Directory. When a job match has run, they are
     * ranked by evidence-match percentage descending (best fit first),
     * falling back to the original reputation order otherwise.
     *
     * @return Collection<int, User>
     */
    public function directoryEngineers(): Collection
    {
        $engineers = $this->results->getCollection()->reject(fn (User $engineer) => $this->isSaved($engineer->id));

        if (! $this->hasMatchBadges()) {
            return $engineers->values();
        }

        return $engineers->sortByDesc(fn (User $engineer) => $this->matchPct($engineer))->values();
    }

    protected function afterSavedToPool(User $candidate, TalentPool $pool): void
    {
        if (! in_array($candidate->id, $this->savedIds, true)) {
            $this->savedIds[] = $candidate->id;
        }

        $this->selected = array_values(array_diff($this->selected, [$candidate->id]));
    }

    protected function afterRemovedFromPool(User $candidate, TalentPool $pool): void
    {
        $this->savedIds = array_values(array_diff($this->savedIds, [$candidate->id]));
    }

    public function isSaved(int $id): bool
    {
        return in_array($id, $this->savedIds, true);
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['grid', 'list', 'detailed', 'avatars'], true) ? $view : 'grid';
        $this->resetPage();
    }

    public function toggleSelect(int $id): void
    {
        $this->selected = in_array($id, $this->selected, true)
            ? array_values(array_diff($this->selected, [$id]))
            : [...$this->selected, $id];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function selectAllVisible(): void
    {
        $visible = collect($this->results->getCollection())
            ->reject(fn (User $u) => $this->isSaved($u->id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->selected = array_values(array_unique([...$this->selected, ...$visible]));
    }

    /**
     * The users currently selected for bulk actions, with skills and evidence
     * counts loaded, in selection order.
     *
     * @return Collection<int, User>
     */
    public function selectedEngineers(): Collection
    {
        if ($this->selected === []) {
            return collect();
        }

        $users = User::query()
            ->with(['skills'])
            ->withCount(['evidence as evidence_count' => fn ($q) => $q->ready()])
            ->whereIn('id', $this->selected)
            ->get()
            ->keyBy('id');

        return collect($this->selected)
            ->map(fn ($id) => $users[$id] ?? null)
            ->filter()
            ->values();
    }

    public function exportSelectedExcel(): void
    {
        if ($this->selected === []) {
            return;
        }

        $rows = app(JobMatchService::class)->exportRows($this->selectedEngineers());
        $csv = app(JobMatchService::class)->toCsv($rows);

        $this->dispatch('download', [
            'content' => $csv,
            'filename' => 'selected-candidates-'.now()->format('Y-m-d').'.csv',
            'mime' => 'text/csv;charset=utf-8',
        ]);
    }

    public function exportSelectedPdf(): void
    {
        if ($this->selected === []) {
            return;
        }

        $rows = app(JobMatchService::class)->exportRows($this->selectedEngineers());
        $pdf = app(JobMatchService::class)->toPdf($rows, 'Selected Candidates — '.now()->format('M j, Y'));

        $this->dispatch('download', [
            'content' => base64_encode($pdf),
            'filename' => 'selected-candidates-'.now()->format('Y-m-d').'.pdf',
            'mime' => 'application/pdf',
            'base64' => true,
        ]);
    }

    public function emailSelected(): void
    {
        if ($this->selected === []) {
            return;
        }

        $this->validate(['shortlistEmail' => ['required', 'email', 'max:255']]);

        $rows = app(JobMatchService::class)->exportRows($this->selectedEngineers());

        app(JobMatchService::class)->emailShortlist(
            auth()->user(),
            $this->shortlistEmail,
            $rows,
            'Selected candidates — '.count($rows).' engineers',
        );

        $this->dispatch('toast', message: 'Shortlist emailed to '.$this->shortlistEmail.'.', variant: 'success');
    }

    public function bulkSaveToPool(int $poolId): void
    {
        $ids = array_values(array_filter($this->selected, fn ($id) => ! $this->isSaved($id)));

        if ($ids === []) {
            $this->dispatch('toast', message: 'Selected candidates are already saved.', variant: 'info');

            return;
        }

        $workspace = app(WorkspaceService::class)->current(auth()->user());

        $pool = TalentPool::where('id', $poolId)
            ->where(function ($q) use ($workspace) {
                $q->where('recruiter_id', auth()->id());

                if ($workspace) {
                    $q->orWhere('workspace_id', $workspace->id);
                }
            })
            ->first();

        if (! $pool) {
            return;
        }

        $count = 0;

        foreach ($ids as $id) {
            $candidate = User::find($id);

            if (! $candidate) {
                continue;
            }

            TalentPoolMember::firstOrCreate(
                ['talent_pool_id' => $pool->id, 'candidate_id' => $candidate->id],
                ['status' => 'saved'],
            );

            $this->afterSavedToPool($candidate, $pool);
            $count++;
        }

        $this->dispatch('toast', message: $count.' candidate'.($count === 1 ? '' : 's').' saved to '.$pool->name.'.', variant: 'success');
    }

    public function compareSelected(): void
    {
        if ($this->selected === []) {
            return;
        }

        $this->redirectRoute('recruiter.compare', ['ids' => implode(',', $this->selected)], navigate: true);
    }

    public function exportExcel(): void
    {
        $rows = app(JobMatchService::class)->exportRows($this->jobMatches);
        $csv = app(JobMatchService::class)->toCsv($rows);

        $this->dispatch('download', [
            'content' => $csv,
            'filename' => 'candidate-shortlist-'.now()->format('Y-m-d').'.csv',
            'mime' => 'text/csv;charset=utf-8',
        ]);
    }

    public function exportPdf(): void
    {
        $rows = app(JobMatchService::class)->exportRows($this->jobMatches);
        $pdf = app(JobMatchService::class)->toPdf($rows, 'Candidate Shortlist — '.now()->format('M j, Y'));

        $this->dispatch('download', [
            'content' => base64_encode($pdf),
            'filename' => 'candidate-shortlist-'.now()->format('Y-m-d').'.pdf',
            'mime' => 'application/pdf',
            'base64' => true,
        ]);
    }

    public function emailShortlist(): void
    {
        $this->validate(['shortlistEmail' => ['required', 'email', 'max:255']]);

        $rows = app(JobMatchService::class)->exportRows($this->jobMatches);

        app(JobMatchService::class)->emailShortlist(
            auth()->user(),
            $this->shortlistEmail,
            $rows,
            'Candidate shortlist — '.count($rows).' engineers',
        );

        $this->dispatch('toast', message: 'Shortlist emailed to '.$this->shortlistEmail.'.', variant: 'success');
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Evidence-Based Talent Search</flux:heading>
        <flux:text>Search runs against analyzed evidence - not self-reported resumes. Click a profile picture to select, then bulk-save or compare.</flux:text>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
        {{-- ============ FILTER COLUMN ============ --}}
        <div class="grid content-start gap-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Filters</flux:heading>
                    <span class="text-xs text-zinc-400">Verified network included</span>
                </div>

                <div class="mt-3 grid grid-cols-4 gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-900">
                    @foreach (['grid' => 'squares-2x2', 'list' => 'list-bullet', 'detailed' => 'document-text', 'avatars' => 'user-circle'] as $viewKey => $viewIcon)
                        <button
                            type="button"
                            wire:click="setView('{{ $viewKey }}')"
                            title="{{ ucfirst($viewKey) }} view"
                            @class([
                                'flex h-9 items-center justify-center rounded-md transition',
                                'bg-white text-accent shadow-sm dark:bg-zinc-700' => $this->view === $viewKey,
                                'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200' => $this->view !== $viewKey,
                            ])
                        >
                            <flux:icon :name="$viewIcon" variant="micro" class="size-4" />
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 grid gap-4">
                    <flux:field>
                        <flux:label>Search</flux:label>
                        <flux:input wire:model.live.debounce.300ms="query" placeholder="Name, username, headline..." />
                    </flux:field>

                    <flux:field>
                        <flux:label>Skills</flux:label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->popularSkills as $skill)
                                <button type="button" wire:click="toggleSkill('{{ $skill }}')"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition {{ in_array($skill, $this->skills, true) ? 'bg-accent text-white' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-900 dark:hover:bg-zinc-700' }}">
                                    <x-tech-logo :name="$skill" class="size-3.5 shrink-0" />
                                    {{ $skill }}
                                </button>
                            @endforeach
                        </div>
                    </flux:field>

                    <flux:field>
                        <flux:label>Location</flux:label>
                        <flux:input wire:model.live.debounce.300ms="location" placeholder="e.g. Berlin" />
                    </flux:field>

                    <flux:field>
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <span class="text-sm">Verified only</span>
                            <flux:switch wire:model.live="verifiedOnly" />
                        </div>
                    </flux:field>

                    <flux:button variant="ghost" size="sm" wire:click="resetFilters" class="justify-center">
                        Reset filters
                    </flux:button>
                </div>
            </div>

            {{-- ============ JOB DESCRIPTION MATCH (filter side) ============ --}}
            <div class="rounded-xl border border-accent/30 bg-white p-5 dark:border-accent/40 dark:bg-zinc-800">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <flux:heading size="sm">Match a job description</flux:heading>
                        <flux:text class="mt-1 text-sm">Paste the job post (or a link) and ProoDev ranks engineers who match - verified first.</flux:text>
                    </div>
                    @if ($this->matchRan)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <flux:icon name="check-circle" variant="micro" />
                            {{ count($this->matchedIds) }} matches
                        </span>
                    @endif
                </div>

                <form wire:submit="runJobMatch" class="mt-4 grid gap-3">
                    <flux:field>
                        <flux:textarea wire:model="jobText" rows="4" placeholder="Paste the full job post: responsibilities, requirements, tech stack…" />
                    </flux:field>

                    <flux:field>
                        <flux:input wire:model="jobUrl" type="url" placeholder="…or a job posting URL" />
                    </flux:field>

                    <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <div class="min-w-0">
                            <span class="text-sm">Include evidence technologies</span>
                            <div class="text-[11px] leading-snug text-zinc-500">Count techs found in analyzed work toward the match %</div>
                        </div>
                        <flux:switch wire:model.live="includeTechnologies" />
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full justify-center" wire:loading.attr="disabled" wire:target="runJobMatch">
                        <span wire:loading.remove wire:target="runJobMatch">Find matching engineers</span>
                        <span wire:loading wire:target="runJobMatch">Matching…</span>
                    </flux:button>

                    <flux:error name="jobText" />
                    <flux:error name="jobUrl" />
                    @if ($this->matchError)
                        <p class="text-xs text-red-500">{{ $this->matchError }}</p>
                    @endif
                </form>

                @if ($this->matchRan)
                    <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-white/10">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                @forelse (array_merge($this->matchedKeywords['skills'], $this->matchedKeywords['technologies']) as $keyword)
                                    <span class="rounded-full bg-accent/10 px-2 py-0.5 font-medium text-accent">{{ $keyword }}</span>
                                @empty
                                    <span class="text-zinc-400">No clear keyword overlap — showing top-ranked engineers.</span>
                                @endforelse
                            </div>
                            <button type="button" wire:click="resetJobMatch" class="text-xs font-medium text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">Clear</button>
                        </div>

                        @if ($this->jobMatches->isNotEmpty())
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <flux:button size="xs" wire:click="exportExcel" variant="outline">
                                    <flux:icon name="arrow-down-tray" variant="micro" />
                                    Excel
                                </flux:button>
                                <flux:button size="xs" wire:click="exportPdf" variant="outline">
                                    <flux:icon name="document-arrow-down" variant="micro" />
                                    PDF
                                </flux:button>
                                <flux:input wire:model="shortlistEmail" type="email" size="sm" placeholder="you@company.com" class="w-full" />
                                <flux:button size="xs" variant="primary" wire:click="emailShortlist" wire:loading.attr="disabled" wire:target="emailShortlist" class="w-full justify-center">
                                    <flux:icon name="paper-airplane" variant="micro" />
                                    Email shortlist
                                </flux:button>
                            </div>
                            <flux:error name="shortlistEmail" />
                        @endif

                        @if ($this->jobMatches->isEmpty())
                            <p class="mt-4 rounded-lg border border-dashed border-zinc-300 p-3 text-center text-sm text-zinc-500 dark:border-zinc-600">
                                No engineers matched. Try pasting more of the requirements, or use the deep search below.
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ RESULTS COLUMN ============ --}}
        <div class="grid gap-6 lg:col-span-3">
            @if ($this->selected !== [])
                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-accent/30 bg-accent/5 px-4 py-3 dark:border-accent/40">
                    <span class="text-sm font-semibold">{{ count($this->selected) }} selected</span>
                    <flux:dropdown>
                        <flux:button size="sm" variant="primary">Save to pool</flux:button>
                        <flux:menu>
                            @forelse ($this->pools as $pool)
                                <flux:menu.item wire:click="bulkSaveToPool({{ $pool->id }})">
                                    {{ $pool->name }} ({{ $pool->members_count }})
                                </flux:menu.item>
                            @empty
                                <div class="px-3 py-2 text-xs text-zinc-500">No pools yet — create one on the workspace page.</div>
                            @endforelse
                        </flux:menu>
                    </flux:dropdown>
                    <flux:button size="sm" variant="primary" wire:click="compareSelected">
                        <flux:icon name="scale" variant="micro" />
                        Compare selected
                    </flux:button>
                    <flux:dropdown>
                        <flux:button size="sm" variant="primary">
                            <flux:icon name="arrow-down-tray" variant="micro" />
                            Export
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="exportSelectedPdf">
                                <flux:icon name="document-arrow-down" variant="micro" />
                                Export PDF
                            </flux:menu.item>
                            <flux:menu.item wire:click="exportSelectedExcel">
                                <flux:icon name="table-cells" variant="micro" />
                                Export Excel
                            </flux:menu.item>
                            <flux:menu.item wire:click="emailSelected">
                                <flux:icon name="paper-airplane" variant="micro" />
                                Email shortlist
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <flux:button size="sm" variant="ghost" wire:click="clearSelection">Clear</flux:button>
                </div>
            @endif

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Evidence deep search</flux:heading>
                <flux:text class="mt-1 text-sm">Find engineers by the technologies and areas that appear inside their analyzed work.</flux:text>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Technology in evidence</flux:label>
                        <flux:input wire:model.live.debounce.400ms="technology" placeholder="e.g. docker, react, kubernetes" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Engineering area</flux:label>
                        <flux:input wire:model.live.debounce.400ms="engineeringArea" placeholder="e.g. DevOps, Security" />
                    </flux:field>
                </div>

                @if ($this->technologyMatches->isNotEmpty())
                    <div class="mt-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-zinc-400">Matched by technology</div>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($this->technologyMatches as $engineer)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 transition hover:border-accent dark:border-zinc-700">
                                    <button type="button" wire:click="toggleSelect({{ $engineer->id }})" class="relative shrink-0" title="Select {{ $engineer->name }}">
                                        <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle class="size-8 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : '' }}" />
                                    </button>
                                    <a href="{{ route('recruiter.candidates.show', $engineer->id) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <div class="truncate text-sm font-medium">{{ $engineer->name }}</div>
                                                <x-verified-badge :user="$engineer" compact />
                                            </div>
                                            <div class="truncate text-xs text-zinc-500">{{ $engineer->headline }}</div>
                                        </div>
                                    </a>
                                    <x-save-to-pool :candidate="$engineer" :pools="$this->pools" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->areaMatches->isNotEmpty())
                    <div class="mt-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-zinc-400">Matched by engineering area</div>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($this->areaMatches as $engineer)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 transition hover:border-accent dark:border-zinc-700">
                                    <button type="button" wire:click="toggleSelect({{ $engineer->id }})" class="relative shrink-0" title="Select {{ $engineer->name }}">
                                        <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle class="size-8 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : '' }}" />
                                    </button>
                                    <a href="{{ route('recruiter.candidates.show', $engineer->id) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <div class="truncate text-sm font-medium">{{ $engineer->name }}</div>
                                                <x-verified-badge :user="$engineer" compact />
                                            </div>
                                            <div class="truncate text-xs text-zinc-500">{{ $engineer->headline }}</div>
                                        </div>
                                    </a>
                                    <x-save-to-pool :candidate="$engineer" :pools="$this->pools" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (trim($this->technology) !== '' && $this->technologyMatches->isEmpty() && trim($this->engineeringArea) === '')
                    <flux:text class="mt-4">No engineers matched this technology in their analyzed evidence.</flux:text>
                @endif
            </div>

            {{-- ============ DIRECTORY ============ --}}
            <div>
                @php
                    $pageEngineers = $this->directoryEngineers();
                    $directoryMatchMode = $this->matchRan && $this->matchedIds !== [];
                @endphp

                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="sm">Directory</flux:heading>
                        @if ($directoryMatchMode)
                            <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2.5 py-0.5 text-[11px] font-semibold text-accent">
                                <flux:icon name="sparkles" variant="micro" />
                                {{ $this->jobMatches->count() }} job matches · ranked by fit
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($this->results->total() > 0)
                            <button type="button" wire:click="selectAllVisible" class="text-xs font-medium text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                Select all on page
                            </button>
                        @endif
                        <x-searchable-select wire:model.live="perPage" size="sm" class="w-32">
                            <option value="12">12 / page</option>
                            <option value="18">18 / page</option>
                            <option value="36">36 / page</option>
                            <option value="72">72 / page</option>
                        </x-searchable-select>
                    </div>
                </div>

                @if ($this->view === 'avatars')
                    <div class="grid grid-cols-4 gap-3 sm:grid-cols-6 lg:grid-cols-8">
                        @forelse ($pageEngineers as $engineer)
                            @php
                                $matchPct = $this->matchPct($engineer);
                                $matchBadges = $this->hasMatchBadges();
                            @endphp
                            <button type="button" wire:key="av-{{ $engineer->id }}" wire:click="toggleSelect({{ $engineer->id }})" title="{{ $engineer->name }}"
                                class="group flex flex-col items-center gap-1.5 rounded-lg p-2 transition hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                <span class="relative">
                                    @if ($matchPct === 100 && $matchBadges)
                                        <span class="block rounded-full p-[2.5px]" style="background: linear-gradient(135deg, #34d399, #14b8a6)">
                                            <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                                class="size-14 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : '' }}" />
                                        </span>
                                    @else
                                        <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                            class="size-14 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : 'group-hover:ring-2 group-hover:ring-zinc-300' }}" />
                                    @endif
                                    @if ($matchBadges)
                                        <x-match-badge :pct="$matchPct" :metric="$this->includeTechnologies ? 'tech' : 'skills'" class="absolute -right-1 -top-1 ring-2 ring-white dark:ring-zinc-950" />
                                    @endif
                                    @if (in_array($engineer->id, $this->selected, true))
                                        <span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-accent text-white shadow"><flux:icon name="check" variant="micro" class="size-3" /></span>
                                    @else
                                        <span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-zinc-400/70 text-white opacity-0 shadow transition group-hover:opacity-100 dark:bg-zinc-600"><flux:icon name="plus" variant="micro" class="size-3" /></span>
                                    @endif
                                </span>
                                <span class="w-full truncate text-center text-[10px] text-zinc-500">{{ $engineer->name }}</span>
                            </button>
                        @empty
                            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                                <flux:heading>{{ $directoryMatchMode ? 'No engineers matched this job description' : 'No engineers match your filters' }}</flux:heading>
                                <flux:text>{{ $directoryMatchMode ? 'Try pasting more of the requirements, or use the deep search below.' : 'Try widening your search.' }}</flux:text>
                            </div>
                        @endforelse
                    </div>
                @elseif ($this->view === 'list')
                    <div class="grid gap-2">
                        @forelse ($pageEngineers as $engineer)
                            @php
                                $matchPct = $this->matchPct($engineer);
                                $matchBadges = $this->hasMatchBadges();
                            @endphp
                            <div wire:key="li-{{ $engineer->id }}" class="group flex flex-wrap items-center gap-3 rounded-lg border border-zinc-100 bg-white p-3 transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800">
                                <button type="button" wire:click="toggleSelect({{ $engineer->id }})" class="relative shrink-0" title="Select {{ $engineer->name }}">
                                    @if ($matchPct === 100 && $matchBadges)
                                        <span class="block rounded-full p-[2.5px]" style="background: linear-gradient(135deg, #34d399, #14b8a6)">
                                            <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                                class="size-10 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : '' }}" />
                                        </span>
                                    @else
                                        <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                            class="size-10 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : 'group-hover:ring-2 group-hover:ring-zinc-300' }}" />
                                    @endif
                                    @if (in_array($engineer->id, $this->selected, true))
                                        <span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-accent text-white shadow"><flux:icon name="check" variant="micro" class="size-3" /></span>
                                    @else
                                        <span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-zinc-400/70 text-white opacity-0 shadow transition group-hover:opacity-100 dark:bg-zinc-600"><flux:icon name="plus" variant="micro" class="size-3" /></span>
                                    @endif
                                </button>
                                <a href="{{ route('recruiter.candidates.show', $engineer->id) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <div class="truncate font-medium">{{ $engineer->name }}</div>
                                            <x-verified-badge :user="$engineer" compact />
                                            @if ($matchBadges)
                                                <x-match-badge :pct="$matchPct" :metric="$this->includeTechnologies ? 'tech' : 'skills'" />
                                            @endif
                                        </div>
                                        <div class="truncate text-xs text-zinc-500">{{ $engineer->headline ?: $engineer->levelTitle() }}</div>
                                    </div>
                                </a>
                                <div class="hidden items-center gap-4 text-xs text-zinc-500 sm:flex">
                                    @if ($engineer->location)
                                        <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $engineer->location }}</span>
                                    @endif
                                    @if ($engineer->reputation_score > 0)
                                        <span class="inline-flex items-center gap-1"><flux:icon name="shield-check" variant="micro" class="text-emerald-500" /> {{ $engineer->reputation_score }}</span>
                                    @endif
                                    <span class="tabular-nums">{{ $engineer->levelTitle() }} · {{ number_format($engineer->experience_points) }} XP</span>
                                </div>
                                <x-save-to-pool :candidate="$engineer" :pools="$this->pools" />
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                                <flux:heading>{{ $directoryMatchMode ? 'No engineers matched this job description' : 'No engineers match your filters' }}</flux:heading>
                                <flux:text>{{ $directoryMatchMode ? 'Try pasting more of the requirements, or use the deep search below.' : 'Try widening your search.' }}</flux:text>
                            </div>
                        @endforelse
                    </div>
                @elseif ($this->view === 'detailed')
                    <div class="grid gap-3">
                        @forelse ($pageEngineers as $engineer)
                            @php
                                $matchPct = $this->matchPct($engineer);
                                $matchBadges = $this->hasMatchBadges();
                            @endphp
                            <div wire:key="de-{{ $engineer->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="flex flex-wrap items-start gap-4">
                                    <button type="button" wire:click="toggleSelect({{ $engineer->id }})" class="group relative shrink-0" title="Select {{ $engineer->name }}">
                                        @if ($matchPct === 100 && $matchBadges)
                                            <span class="block rounded-full p-[2.5px]" style="background: linear-gradient(135deg, #34d399, #14b8a6)">
                                                <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                                    class="size-16 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : '' }}" />
                                            </span>
                                        @else
                                            <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                                class="size-16 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : 'group-hover:ring-2 group-hover:ring-zinc-300' }}" />
                                        @endif
                                        @if (in_array($engineer->id, $this->selected, true))
                                            <span class="absolute -bottom-1 -right-1 flex size-6 items-center justify-center rounded-full bg-accent text-white shadow"><flux:icon name="check" variant="micro" class="size-3.5" /></span>
                                        @else
                                            <span class="absolute -bottom-1 -right-1 flex size-6 items-center justify-center rounded-full bg-zinc-400/70 text-white opacity-0 shadow transition group-hover:opacity-100 dark:bg-zinc-600"><flux:icon name="plus" variant="micro" class="size-3.5" /></span>
                                        @endif
                                    </button>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="truncate text-lg font-semibold">{{ $engineer->name }}</div>
                                            <x-verified-badge :user="$engineer" />
                                            @if ($matchBadges)
                                                <x-match-badge :pct="$matchPct" :metric="$this->includeTechnologies ? 'tech' : 'skills'" />
                                            @endif
                                            @if ($engineer->isVerified())
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                    <flux:icon name="check-badge" variant="micro" /> Verified
                                                </span>
                                            @endif
                                        </div>
                                        @if ($engineer->headline)
                                            <div class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-300">{{ $engineer->headline }}</div>
                                        @endif
                                        @if ($engineer->bio)
                                            <p class="mt-2 line-clamp-2 text-sm text-zinc-500">{{ $engineer->bio }}</p>
                                        @endif

                                        @if ($engineer->skills->isNotEmpty())
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @foreach ($engineer->skills->take(6) as $skill)
                                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800/80 dark:text-zinc-200 dark:ring-white/10">
                                                        <x-tech-logo :name="$skill->name" class="size-3.5 shrink-0" />
                                                        {{ $skill->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-zinc-500">
                                            @if ($engineer->location)
                                                <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $engineer->location }}</span>
                                            @endif
                                            @if ($engineer->reputation_score > 0)
                                                <span class="inline-flex items-center gap-1"><flux:icon name="shield-check" variant="micro" class="text-emerald-500" /> {{ number_format($engineer->reputation_score) }}</span>
                                            @endif
                                            <span class="tabular-nums">{{ $engineer->levelTitle() }} · {{ number_format($engineer->experience_points) }} XP</span>
                                            @if ($engineer->evidence_count > 0)
                                                <span class="inline-flex items-center gap-1"><flux:icon name="document-text" variant="micro" /> {{ $engineer->evidence_count }} evidence</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-end gap-2">
                                        <x-save-to-pool :candidate="$engineer" :pools="$this->pools" />
                                        <flux:button size="sm" variant="outline" :href="route('recruiter.candidates.show', $engineer->id)" wire:navigate>
                                            View passport
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                                <flux:heading>{{ $directoryMatchMode ? 'No engineers matched this job description' : 'No engineers match your filters' }}</flux:heading>
                                <flux:text>{{ $directoryMatchMode ? 'Try pasting more of the requirements, or use the deep search below.' : 'Try widening your search.' }}</flux:text>
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($pageEngineers as $engineer)
                            @php
                                $matchPct = $this->matchPct($engineer);
                                $matchBadges = $this->hasMatchBadges();
                            @endphp
                            <div wire:key="gr-{{ $engineer->id }}" class="group relative rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800">
                                <a href="{{ route('recruiter.candidates.show', $engineer->id) }}" wire:navigate class="absolute inset-0 rounded-xl" aria-label="View {{ $engineer->name }}'s passport"></a>

                                <div class="flex items-center gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <button type="button" wire:click="toggleSelect({{ $engineer->id }})" class="group/avatar relative z-10 shrink-0" title="Select {{ $engineer->name }}">
                                            @if ($matchPct === 100 && $matchBadges)
                                                <span class="block rounded-full p-[2.5px]" style="background: linear-gradient(135deg, #34d399, #14b8a6)">
                                                    <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                                        class="size-11 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : '' }}" />
                                                </span>
                                            @else
                                                <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle
                                                    class="size-11 {{ in_array($engineer->id, $this->selected, true) ? 'ring-2 ring-accent ring-offset-2' : 'group-hover/avatar:ring-2 group-hover/avatar:ring-zinc-300' }}" />
                                            @endif
                                            @if (in_array($engineer->id, $this->selected, true))
                                                <span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-accent text-white shadow"><flux:icon name="check" variant="micro" class="size-3" /></span>
                                            @else
                                                <span class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-zinc-400/70 text-white opacity-0 shadow transition group-hover/avatar:opacity-100 dark:bg-zinc-600"><flux:icon name="plus" variant="micro" class="size-3" /></span>
                                            @endif
                                        </button>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <div class="truncate font-semibold group-hover:text-accent">{{ $engineer->name }}</div>
                                                <x-verified-badge :user="$engineer" compact />
                                                @if ($matchBadges)
                                                    <x-match-badge :pct="$matchPct" :metric="$this->includeTechnologies ? 'tech' : 'skills'" />
                                                @endif
                                            </div>
                                            <div class="truncate text-xs text-zinc-500">{{ $engineer->levelTitle() }} - {{ $engineer->experience_points }} XP</div>
                                        </div>
                                    </div>
                                    <div class="relative z-10 ms-auto shrink-0">
                                        <x-save-to-pool :candidate="$engineer" :pools="$this->pools" />
                                    </div>
                                </div>

                                @if ($engineer->headline)
                                    <p class="mt-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $engineer->headline }}</p>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                    @if ($engineer->location)
                                        <span class="inline-flex items-center gap-1"><flux:icon name="map-pin" variant="micro" /> {{ $engineer->location }}</span>
                                    @endif
                                    @if ($engineer->reputation_score > 0)
                                        <span class="inline-flex items-center gap-1"><flux:icon name="shield-check" variant="micro" class="text-emerald-500" /> {{ $engineer->reputation_score }}</span>
                                    @endif
                                </div>

                                @if ($engineer->skills->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($engineer->skills->take(3) as $skill)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium dark:bg-zinc-900">
                                                <x-tech-logo :name="$skill->name" class="size-3 shrink-0" />
                                                {{ $skill->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                                <flux:heading>{{ $directoryMatchMode ? 'No engineers matched this job description' : 'No engineers match your filters' }}</flux:heading>
                                <flux:text>{{ $directoryMatchMode ? 'Try pasting more of the requirements, or use the deep search below.' : 'Try widening your search.' }}</flux:text>
                            </div>
                        @endforelse
                    </div>
                @endif

                <div class="mt-6">
                    {{ $this->results->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
