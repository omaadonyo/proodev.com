<?php

use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Project')] class extends Component {
    public Project $project;

    public ProjectForm $form;

    public function mount(Project $project): void
    {
        $this->authorize('update', $project);

        $this->project = $project;
        $this->form->set($project);
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

    public string $newDecision = '';

    public function removeDecision(int $index): void
    {
        unset($this->form->engineeringDecisions[$index]);
        $this->form->engineeringDecisions = array_values($this->form->engineeringDecisions);
    }

    public function save(): void
    {
        $this->authorize('update', $this->project);

        $this->form->validate();

        $project = app(\App\Actions\Projects\SaveProjectAction::class)->handle(auth()->user(), $this->form->data(), $this->project);

        Flux::toast(variant: 'success', text: 'Project saved.');

        $this->redirectRoute('projects.show', $project, navigate: true);
    }

    public function publish(): void
    {
        $this->authorize('publish', $this->project);

        $this->form->validate();

        $project = app(\App\Actions\Projects\SaveProjectAction::class)->handle(auth()->user(), $this->form->data(), $this->project);

        app(\App\Actions\Projects\SaveProjectAction::class)->publish($project);

        Flux::toast(variant: 'success', text: 'Project published to the community.');

        $this->redirectRoute('projects.show', $project, navigate: true);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->project);

        $this->project->delete();

        Flux::toast(variant: 'success', text: 'Project deleted.');

        $this->redirectRoute('projects.index', navigate: true);
    }

}
?>

<div class="mx-auto grid max-w-3xl gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ $this->form->title ?: 'Untitled project' }}</flux:heading>
            <flux:text>
                {{ $this->project->isPublished() ? 'Published '.$this->project->published_at->diffForHumans() : 'Draft — only you can see this.' }}
            </flux:text>
        </div>

        @if ($this->project->isPublished())
            <flux:badge variant="success" inset="top bottom">Published</flux:badge>
        @else
            <flux:badge color="zinc" inset="top bottom">Draft</flux:badge>
        @endif
    </div>

    <form wire:submit="save" class="grid gap-6">
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
            @if (! $this->project->isPublished())
                <flux:button type="submit" variant="primary" wire:click.prevent="publish">
                    <flux:icon name="paper-airplane" variant="micro" /> Publish
                </flux:button>
            @endif

            <flux:button type="submit" variant="filled">Save draft</flux:button>

            <flux:spacer />

            <flux:button variant="danger" wire:click.prevent="delete" wire:confirm="Delete this project permanently?">
                Delete
            </flux:button>
        </div>
    </form>
</div>