<?php

use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Services\ProjectScoutService;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Project')]
class extends Component {
    public ProjectForm $form;

    public ?string $url = null;

    public string $phase = 'input';

    public array $log = [];

    public int $stage = 0;

    public ?array $material = null;

    public ?array $draft = null;

    public ?int $score = null;

    public ?string $error = null;

    public string $newDecision = '';

    public function mount(): void
    {
        $this->authorize('create', Project::class);

        $url = (string) request()->query('url', '');

        if ($url !== '') {
            $this->url = $url;
            $this->begin();
        }
    }

    public function begin(): void
    {
        $this->resetErrorBag();
        $this->error = null;

        $validated = $this->validate(['url' => ['required', 'string', 'url', 'max:255']]);

        $this->url = Str::startsWith($validated['url'], ['http://', 'https://'])
            ? $validated['url']
            : 'https://'.$validated['url'];

        $this->phase = 'scouting';
        $this->stage = 0;

        $this->log = [
            $this->line('Fetching page', 'Resolving '.$this->url, 0, 'pending'),
            $this->line('Extracting content', 'Reading structure & signals…', 0, 'pending'),
            $this->line('AI draft', 'Writing problem, solution & architecture…', 0, 'pending'),
            $this->line('Scoring', 'Evaluating readiness…', 0, 'pending'),
        ];
    }

    public function tick(): void
    {
        if ($this->phase !== 'scouting') {
            return;
        }

        $this->stage++;

        try {
            match ($this->stage) {
                1 => $this->stageFetch(),
                2 => $this->stageExtract(),
                3 => $this->stageDraft(),
                default => $this->stageScore(),
            };
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();
            $this->phase = 'input';
        } catch (\Throwable $e) {
            \Log::error('ProjectScout error: '.$e->getMessage(), [
                'exception' => $e,
                'url' => $this->url ?? null,
                'stage' => $this->stage ?? null,
            ]);
            $this->error = 'Connection issue while scouting that project. Please try again.';
            $this->phase = 'input';
        }
    }

    public function backToInput(): void
    {
        $this->phase = 'input';
        $this->error = null;
    }

    private function stageFetch(): void
    {
        $this->material = app(ProjectScoutService::class)->fetch($this->url);

        $this->log[0] = $this->line(
            'Fetching page',
            Str::ucfirst((string) ($this->material['source'] ?? 'web')).' · '.$this->url,
            100,
            'done',
        );
    }

    private function stageExtract(): void
    {
        $tech = $this->material['tech_stack'] ?? [];

        $this->log[1] = $this->line(
            'Extracting content',
            ($this->material['title'] ?? 'Untitled').(count($tech) > 0 ? ' · '.implode(', ', array_slice($tech, 0, 4)) : ''),
            100,
            'done',
        );
    }

    private function stageDraft(): void
    {
        $this->draft = app(ProjectScoutService::class)->draft(
            $this->facts($this->material),
            $this->material,
        );

        $this->log[2] = $this->line('AI draft', 'Problem, solution & architecture written', 100, 'done');
    }

    private function stageScore(): void
    {
        $this->score = app(ProjectScoutService::class)->score($this->material, $this->draft);

        $this->log[3] = $this->line('Scoring', 'Project ready: '.$this->score.'/100', 100, 'done');

        $this->form->applyDraft(array_merge($this->draft, ['score' => $this->score]));
        $this->phase = 'review';
    }

    public function addTech(): void
    {
        $this->validate(['form.newTech' => ['required', 'string', 'max:60']]);

        $tech = trim($this->form->newTech);

        if (! in_array($tech, $this->form->techStack, true)) {
            $this->form->techStack[] = $tech;
        }

        $this->form->newTech = '';
    }

    public function removeTech(int $index): void
    {
        unset($this->form->techStack[$index]);
        $this->form->techStack = array_values($this->form->techStack);
    }

    public function addDecision(): void
    {
        $this->validate(['newDecision' => ['required', 'string', 'max:500']]);

        $this->form->engineeringDecisions[] = trim($this->newDecision);
        $this->newDecision = '';
    }

    public function removeDecision(int $index): void
    {
        unset($this->form->engineeringDecisions[$index]);
        $this->form->engineeringDecisions = array_values($this->form->engineeringDecisions);
    }

    public function createDraft(): void
    {
        $this->authorize('create', Project::class);

        $this->form->validate();

        $project = app(\App\Actions\Projects\SaveProjectAction::class)->handle(auth()->user(), $this->form->data());

        Flux::toast(variant: 'success', text: 'Draft saved.');

        $this->redirectRoute('projects.edit', $project, navigate: true);
    }

    public function publish(): void
    {
        $this->authorize('create', Project::class);

        $this->form->validate();

        $project = app(\App\Actions\Projects\SaveProjectAction::class)->handle(auth()->user(), $this->form->data());

        app(\App\Actions\Projects\SaveProjectAction::class)->publish($project);

        Flux::toast(variant: 'success', text: 'Project published to the community.');

        $this->redirectRoute('projects.show', $project, navigate: true);
    }

    /**
     * @param  array<string, mixed>  $material
     */
    private function facts(array $material): string
    {
        return collect([
            'Source URL: '.($material['profile_url'] ?? ''),
            'Title: '.($material['title'] ?? ''),
            'Tagline: '.($material['tagline'] ?? 'Not provided'),
            'Tech signals: '.implode(', ', $material['tech_stack'] ?? []),
            'Content: '.Str::limit((string) ($material['content'] ?? ''), 8000),
        ])->filter()->implode("\n\n");
    }

    private function line(string $label, string $status, int $progress, string $state): array
    {
        return compact('label', 'status', 'progress', 'state');
    }

}
?>

<div class="mx-auto w-full max-w-3xl">
    @if ($phase === 'review')
        <div class="grid gap-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div>
                        <flux:heading size="xl">{{ $this->form->title ?: 'Untitled project' }}</flux:heading>
                        <flux:text class="mt-1">AI-drafted from your source. Review, edit, then publish.</flux:text>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if ($score !== null)
                        <div
                            class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-bold
                                {{ $score >= 80 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($score >= 50 ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400') }}"
                        >
                            <flux:icon name="sparkles" variant="micro" />
                            {{ $score }}/100
                        </div>
                    @endif
                    @if (($draft['generated_by'] ?? null) === 'ai')
                        <flux:badge variant="success" inset="top bottom">AI drafted</flux:badge>
                    @else
                        <flux:badge color="zinc" inset="top bottom">Draft</flux:badge>
                    @endif
                </div>
            </div>

            <form wire:submit="createDraft" class="grid gap-6">
                <div class="grid gap-5 ">
                    <flux:heading size="sm">Basics</flux:heading>

                    <flux:field>
                        <flux:label>Title *</flux:label>
                        <flux:input wire:model="form.title" />
                        <flux:error name="form.title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tagline</flux:label>
                        <flux:input wire:model="form.tagline" />
                        <flux:error name="form.tagline" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Demo URL</flux:label>
                            <flux:input type="url" wire:model="form.demoUrl" placeholder="https://…" />
                            <flux:error name="form.demoUrl" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Repository URL</flux:label>
                            <flux:input type="url" wire:model="form.repositoryUrl" placeholder="https://github.com/…" />
                            <flux:error name="form.repositoryUrl" />
                        </flux:field>
                    </div>
                </div>

                <div class="grid gap-5 ">
                    <flux:heading size="sm">Engineering story</flux:heading>

                    <flux:field>
                        <flux:label>Problem *</flux:label>
                        <flux:textarea wire:model="form.problem" rows="4" />
                        <flux:error name="form.problem" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Solution *</flux:label>
                        <flux:textarea wire:model="form.solution" rows="4" />
                        <flux:error name="form.solution" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Architecture</flux:label>
                        <flux:textarea wire:model="form.architecture" rows="5" placeholder="Describe the architecture, components, data flow, trade-offs…" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Lessons learned</flux:label>
                        <flux:textarea wire:model="form.lessonsLearned" rows="4" />
                    </flux:field>
                </div>

                <div class="grid gap-5 ">
                    <flux:heading size="sm">Tech stack</flux:heading>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->form->techStack as $index => $tech)
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-sm dark:bg-zinc-900">
                                {{ $tech }}
                                <button type="button" wire:click="removeTech({{ $index }})" class="text-zinc-400 hover:text-rose-500">
                                    <flux:icon name="x-mark" variant="micro" />
                                </button>
                            </span>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:input wire:model="form.newTech" placeholder="e.g. Laravel 13" class="max-w-xs" wire:keydown.enter="addTech" />
                        <flux:button type="button" variant="subtle" wire:click="addTech">Add</flux:button>
                    </div>
                </div>

                <div class="grid gap-5 ">
                    <flux:heading size="sm">Engineering decisions</flux:heading>

                    <div class="grid gap-2">
                        @foreach ($this->form->engineeringDecisions as $index => $decision)
                            <div class="flex items-start gap-2 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900">
                                <span class="mt-0.5 size-1.5 shrink-0 rounded-full bg-accent"></span>
                                <span class="flex-1">{{ $decision }}</span>
                                <button type="button" wire:click="removeDecision({{ $index }})" class="text-zinc-400 hover:text-rose-500">
                                    <flux:icon name="x-mark" variant="micro" />
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:input wire:model="newDecision" placeholder="Decision + rationale (e.g. Chose queues over cron for retries…)" class="flex-1" wire:keydown.enter="addDecision" />
                        <flux:button type="button" variant="subtle" wire:click="addDecision">Add</flux:button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button type="button" variant="primary" wire:click.prevent="publish">
                        <flux:icon name="paper-airplane" variant="micro" /> Publish
                    </flux:button>

                    <flux:button type="submit" variant="filled">Save draft</flux:button>

                    <flux:spacer />

                    <button type="button" wire:click="backToInput" class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
                        Scout a different URL
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="p-8">
            <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent">
                <flux:icon name="sparkles" variant="micro" />
                Project scout
            </div>
            <flux:heading size="xl">Let's draft your project.</flux:heading>
            <flux:text class="mt-2">
                Paste a repository, live demo or any project page. We'll extract the details, write the engineering story, and score it, all you have to do is review and publish.
            </flux:text>

            <form wire:submit="begin" class="mt-5 rounded-xl border border-zinc-200 bg-white p-[calc(var(--spacing)*2)] dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                        <flux:icon name="magnifying-glass" variant="micro" />
                    </div>
                    <div class="flex-1">
                        <flux:input
                            wire:model="url"
                            type="url"
                            autofocus
                            placeholder="Paste a GitHub profile, repo or project URL to scout it live…"
                            class="border-none bg-transparent shadow-none focus:ring-0"
                        />
                    </div>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Scout
                    </flux:button>
                </div>
                <flux:error name="url" class="mt-2" />
                @if ($error)
                    <p class="mt-2 text-left text-xs text-red-500">{{ $error }}</p>
                @endif
                <p class="mt-2 text-left text-[11px] text-zinc-400">
                    Works with <code class="rounded bg-zinc-100 px-1 py-0.5 dark:bg-zinc-900">github.com/…</code> repositories and any <code class="rounded bg-zinc-100 px-1 py-0.5 dark:bg-zinc-900">https://…</code> project or demo page.
                </p>
                @if (! auth()->user()->github_url)
                    <div class="mt-3 rounded-lg border border-amber-300/40 bg-amber-50 p-3 text-left text-xs text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                        <div class="flex items-start gap-2">
                            <flux:icon name="exclamation-triangle" variant="micro" class="mt-0.5 shrink-0" />
                            <div>
                                <div class="text-[11px] font-semibold">Claiming work that isn't yours is treated as plagiarism</div>
                                <p class="mt-1 text-[11px] leading-relaxed">
                                    You haven't linked a GitHub account, so ownership can't be verified. If you scout a repository that another ProoDev user already claimed, or one you can't show as your own, it will be flagged and removed.
                                    <a href="{{ route('profile.edit') }}" wire:navigate class="font-semibold text-amber-900 underline underline-offset-2 hover:no-underline dark:text-amber-100">Link your GitHub in settings</a> to verify yourself.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </form>

            <div class="mt-4">
                <a href="{{ route('projects.index') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
                    Cancel
                </a>
            </div>

            @if ($phase === 'scouting')
                <div wire:poll.700ms="tick" class="mt-6 overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 shadow-lg">
                    <div class="flex items-center gap-1.5 border-b border-zinc-800/80 px-4 py-2.5">
                        <span class="size-2.5 rounded-full bg-rose-500/80"></span>
                        <span class="size-2.5 rounded-full bg-amber-500/80"></span>
                        <span class="size-2.5 rounded-full bg-emerald-500/80"></span>
                        <span class="ms-2 font-mono text-xs text-zinc-500">proodev · project scout</span>
                    </div>
                    <div class="p-4 font-mono text-[13px] leading-7">
                        <div class="text-zinc-500">$ scout --url {{ $url }}</div>

                        @foreach ($log as $entry)
                            <div class="flex items-center gap-3">
                                <span class="w-32 shrink-0 text-zinc-400">{{ $entry['label'] }}</span>
                                <span class="flex-1 truncate {{ $entry['state'] === 'done' ? 'text-zinc-100' : 'text-zinc-500' }}">
                                    {{ $entry['status'] }}
                                </span>
                                <span class="shrink-0 tabular-nums text-zinc-400">{{ $entry['progress'] }}%</span>
                            </div>
                        @endforeach

                        <div class="mt-1 flex items-center gap-2 text-emerald-400">
                            <span class="inline-block size-2 animate-pulse rounded-full bg-emerald-400"></span>
                            <span>processing…</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>