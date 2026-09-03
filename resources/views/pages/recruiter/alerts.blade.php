<?php

use App\Models\Skill;
use App\Models\TalentAlert;
use App\Services\Recruiter\JobMatchService;
use App\Services\Recruiter\TalentAlertService;
use App\Services\Recruiter\WorkspaceService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Talent Alerts')] class extends Component {
    public string $name = '';

    public string $skillsCsv = '';

    public string $location = '';

    public string $frequency = 'daily';

    public int $minMagnitude = 0;

    public bool $verifiedOnly = false;

    public bool $showCreate = false;

    // Create-from-job-posting.
    public string $jobText = '';

    public ?string $jobUrl = null;

    public bool $extracting = false;

    /** @var array<int, string> */
    public array $extractedSkills = [];

    /** @var array<int, string> */
    public array $extractedTechnologies = [];

    #[Computed]
    public function alerts()
    {
        $workspace = app(WorkspaceService::class)->current(auth()->user());

        if ($workspace) {
            return TalentAlert::where('workspace_id', $workspace->id)->latest()->get();
        }

        return auth()->user()->talentAlerts()->latest()->get();
    }

    #[Computed]
    public function currentWorkspace()
    {
        return app(WorkspaceService::class)->current(auth()->user());
    }

    #[Computed]
    public function skillNames(): array
    {
        $slugs = $this->alerts->flatMap(fn ($alert) => (array) ($alert->criteria['skills'] ?? []))->unique()->values()->all();

        return Skill::whereIn('slug', $slugs)->pluck('name', 'slug')->all();
    }

    public function createAlert(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'skillsCsv' => ['nullable', 'string', 'max:500'],
            'jobText' => ['nullable', 'string', 'max:20000'],
            'jobUrl' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:100'],
            'frequency' => ['required', 'in:realtime,daily,weekly'],
            'minMagnitude' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $fromJob = trim($this->jobText) !== '' || $this->jobUrl !== null;

        if ($fromJob) {
            $service = app(JobMatchService::class);
            $resolved = $service->resolveText($this->jobText, $this->jobUrl);
            $keywords = $service->extractKeywords($resolved['text']);

            $criteria = [
                'skills' => $keywords['skills'],
                'technologies' => $keywords['technologies'],
                'location' => $this->location ?: null,
                'verified_only' => $this->verifiedOnly,
                'min_magnitude' => $this->minMagnitude ?: null,
                'source' => 'job',
                'job_source' => $resolved['source'],
            ];
        } else {
            $criteria = [
                'skills' => $this->skillsCsv !== ''
                    ? array_values(array_filter(array_map('trim', explode(',', $this->skillsCsv))))
                    : [],
                'location' => $this->location ?: null,
                'verified_only' => $this->verifiedOnly,
                'min_magnitude' => $this->minMagnitude ?: null,
            ];
        }

        TalentAlert::create([
            'workspace_id' => app(WorkspaceService::class)->currentId(auth()->user()),
            'recruiter_id' => auth()->id(),
            'name' => $this->name,
            'criteria' => $criteria,
            'frequency' => $this->frequency,
        ]);

        $this->reset('name', 'skillsCsv', 'location', 'frequency', 'minMagnitude', 'verifiedOnly', 'showCreate', 'jobText', 'jobUrl', 'extractedSkills', 'extractedTechnologies');

        $keywordCount = count($criteria['skills'] ?? []) + count($criteria['technologies'] ?? []);
        $this->dispatch('toast', message: 'Talent alert created'.($fromJob ? ' from job posting ('.$keywordCount.' keywords extracted)' : '').'.', variant: 'success');
    }

    public function extractFromJob(): void
    {
        $this->resetErrorBag();
        $this->extracting = true;
        $this->extractedSkills = [];
        $this->extractedTechnologies = [];

        $this->validate([
            'jobText' => ['nullable', 'string', 'max:20000'],
            'jobUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        if (trim($this->jobText) === '' && ! $this->jobUrl) {
            $this->addError('jobText', 'Paste a job description or a job posting URL.');
            $this->extracting = false;

            return;
        }

        $service = app(JobMatchService::class);
        $resolved = $service->resolveText($this->jobText, $this->jobUrl);
        $keywords = $service->extractKeywords($resolved['text']);

        $this->extractedSkills = $keywords['skills'];
        $this->extractedTechnologies = $keywords['technologies'];
        $this->extracting = false;

        if ($keywords['skills'] === [] && $keywords['technologies'] === []) {
            $this->dispatch('toast', message: 'No known keywords found - the alert will match broadly. Try pasting the full requirements.', variant: 'warning');
        } else {
            $this->dispatch('toast', message: 'Extracted '.count($keywords['skills']).' skill'.(count($keywords['skills']) === 1 ? '' : 's').' from the job posting.', variant: 'success');
        }
    }

    public function runAlert(int $id): void
    {
        $alert = TalentAlert::find($id);

        $workspaceId = app(WorkspaceService::class)->currentId(auth()->user());

        if (! $alert || ($alert->workspace_id !== null && $alert->workspace_id !== $workspaceId)) {
            return;
        }

        $matches = app(TalentAlertService::class)->runAlert($alert);

        $this->dispatch('toast', message: $matches->isNotEmpty()
            ? 'Alert ran and found '.$matches->count().' new match(es).'
            : 'Alert ran - no new matches right now.', variant: 'success');
    }

    public function toggleActive(int $id): void
    {
        $alert = TalentAlert::find($id);

        $workspaceId = app(WorkspaceService::class)->currentId(auth()->user());

        if ($alert && ($alert->workspace_id === null || $alert->workspace_id === $workspaceId)) {
            $alert->update(['is_active' => ! $alert->is_active]);
        }
    }

    public function deleteAlert(int $id): void
    {
        $alert = TalentAlert::find($id);

        $workspaceId = app(WorkspaceService::class)->currentId(auth()->user());

        if ($alert && ($alert->workspace_id === null || $alert->workspace_id === $workspaceId)) {
            $alert->delete();
        }
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Talent Alerts</flux:heading>
            <flux:text>Watch the talent pool for candidates matching your criteria and get notified when new matches surface.</flux:text>
            @if ($this->currentWorkspace)
                <flux:text class="mt-1 text-sm">
                    Working in <span class="font-semibold text-accent">{{ $this->currentWorkspace->name }}</span>
                    · <a href="{{ route('workspaces') }}" wire:navigate class="underline hover:text-accent">Switch workspace</a>
                </flux:text>
            @endif
        </div>
        <flux:button variant="primary" wire:click="$toggle('showCreate')">
            <flux:icon name="plus" variant="micro" />
            New alert
        </flux:button>
    </div>

    @if ($showCreate)
        <form wire:submit="createAlert" class="rounded-xl border border-accent/40 bg-accent/5 p-5">
            <flux:heading size="sm">Create alert</flux:heading>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Alert name</flux:label>
                    <flux:input wire:model="name" placeholder="e.g. Senior Laravel in Berlin" />
                </flux:field>
                <flux:field>
                    <flux:label>Skills (comma separated)</flux:label>
                    <flux:input wire:model="skillsCsv" placeholder="laravel, redis, kubernetes" />
                </flux:field>
                <flux:field>
                    <flux:label>Location</flux:label>
                    <flux:input wire:model="location" placeholder="e.g. Berlin" />
                </flux:field>
                <flux:field>
                    <flux:label>Frequency</flux:label>
                    <x-searchable-select wire:model="frequency">
                        <option value="realtime">Realtime</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                    </x-searchable-select>
                </flux:field>
                <flux:field>
                    <flux:label>Minimum magnitude</flux:label>
                    <flux:input type="number" wire:model="minMagnitude" min="0" max="1000" placeholder="e.g. 450" />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <div class="flex items-center justify-between rounded-lg bg-zinc-100 px-3 py-2 dark:bg-white/5">
                        <span class="text-sm">Verified candidates only</span>
                        <flux:switch wire:model="verifiedOnly" />
                    </div>
                </flux:field>

                <div class="sm:col-span-2">
                    <div class="flex items-center gap-3">
                        <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">or create from a job posting</span>
                        <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></span>
                    </div>
                </div>

                <flux:field class="sm:col-span-2">
                    <flux:label>Job description</flux:label>
                    <flux:textarea wire:model="jobText" rows="4" placeholder="Paste the full job post - responsibilities, requirements, tech stack…" />
                    <flux:error name="jobText" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <flux:field class="flex-1">
                            <flux:label>…or a job posting URL</flux:label>
                            <flux:input wire:model="jobUrl" type="url" placeholder="https://careers.acme.com/senior-backend" />
                            <flux:error name="jobUrl" />
                        </flux:field>
                        <flux:button type="button" variant="outline" class="shrink-0" wire:click="extractFromJob" wire:loading.attr="disabled" wire:target="extractFromJob">
                            <span wire:loading.remove wire:target="extractFromJob">Extract skills</span>
                            <span wire:loading wire:target="extractFromJob">Extracting…</span>
                        </flux:button>
                    </div>
                </flux:field>

                @if ($this->extractedSkills !== [] || $this->extractedTechnologies !== [])
                    <div class="sm:col-span-2">
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                            <div class="text-[11px] font-medium uppercase tracking-wide text-zinc-400">Extracted keywords - the alert watches engineers with these skills &amp; evidence</div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($this->extractedSkills as $skill)
                                    <span class="rounded-full bg-accent/10 px-2.5 py-1 text-[11px] font-medium text-accent">{{ $skill }}</span>
                                @endforeach
                                @foreach ($this->extractedTechnologies as $tech)
                                    <span class="rounded-full bg-zinc-200 px-2.5 py-1 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $tech }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="mt-4 flex items-center gap-2">
                <flux:button type="submit" variant="primary">Create alert</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showCreate', false)">Cancel</flux:button>
            </div>
        </form>
    @endif

    <div class="grid gap-4">
        @forelse ($this->alerts as $alert)
            <div class="flex flex-wrap items-center gap-4 rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 font-medium">
                        {{ $alert->name }}
                        @if ($alert->is_active)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">active</span>
                        @else
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-900">paused</span>
                        @endif
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                        <span class="inline-flex items-center gap-1"><flux:icon name="clock" variant="micro" /> {{ ucfirst($alert->frequency) }}</span>
                        @if (($alert->criteria['source'] ?? null) === 'job')
                            <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 font-medium text-accent">
                                <flux:icon name="document-text" variant="micro" />
                                from job posting ({{ ($alert->criteria['job_source'] ?? 'text') === 'url' ? 'URL' : 'description' }})
                            </span>
                        @endif
                        @if ($alert->criteria['min_magnitude'] ?? null)
                            <span>magnitude >= {{ $alert->criteria['min_magnitude'] }}</span>
                        @endif
                        @if (! empty($alert->criteria['location']))
                            <span>{{ $alert->criteria['location'] }}</span>
                        @endif
                        @if ($alert->last_run_at)
                            <span>last ran {{ $alert->last_run_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    @if (! empty($alert->criteria['skills']) || ! empty($alert->criteria['technologies']))
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($alert->criteria['skills'] as $skill)
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium dark:bg-zinc-900">{{ $this->skillNames[$skill] ?? $skill }}</span>
                            @endforeach
                            @foreach ($alert->criteria['technologies'] ?? [] as $tech)
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-900">{{ $tech }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <flux:button size="xs" wire:click="runAlert({{ $alert->id }})">
                        <flux:icon name="play" variant="micro" />
                        Run now
                    </flux:button>
                    <flux:button size="xs" variant="ghost" wire:click="toggleActive({{ $alert->id }})">
                        {{ $alert->is_active ? 'Pause' : 'Resume' }}
                    </flux:button>
                    <flux:button size="xs" variant="danger" wire:click="deleteAlert({{ $alert->id }})" wire:confirm="Delete this alert?">
                        <flux:icon name="trash" variant="micro" />
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                <flux:heading>No talent alerts yet</flux:heading>
                <flux:text>Create an alert to be notified when matching engineers join the network.</flux:text>
            </div>
        @endforelse
    </div>
</div>
