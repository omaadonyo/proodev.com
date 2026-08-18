<?php

use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Projects')] class extends Component
{
    use WithPagination;

    public string $query = '';

    public string $view = 'browse';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        $this->resetPage();
    }

    #[Computed]
    public function myProjects()
    {
        return auth()->user()
            ->projects()
            ->orderByDesc('updated_at')
            ->take(20)
            ->get();
    }

    #[Computed]
    public function published()
    {
        return Project::query()
            ->published()
            ->with(['user'])
            ->withCount('recognitions')
            ->when($this->query, fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$this->query}%")->orWhere('tagline', 'like', "%{$this->query}%")))
            ->orderByDesc('published_at')
            ->paginate(12);
    }

    public function render()
    {
        return view('pages.projects.index');
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Projects</flux:heading>
            <flux:text>Engineering case studies — problems, solutions, and the decisions behind them.</flux:text>
        </div>
        <flux:button variant="primary" href="{{ route('projects.create') }}" wire:navigate>
            <flux:icon name="plus" variant="micro" /> New Project
        </flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <flux:button :variant="$view === 'browse' ? 'primary' : 'ghost'" size="sm" wire:click="setView('browse')">
            Browse community
        </flux:button>
        <flux:button :variant="$view === 'mine' ? 'primary' : 'ghost'" size="sm" wire:click="setView('mine')">
            My projects ({{ $this->myProjects->count() }})
        </flux:button>

        @if ($view === 'browse')
            <flux:spacer />
            <div class="w-64">
                <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="query" placeholder="Search projects…" />
            </div>
        @endif
    </div>

    @if ($view === 'mine')
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->myProjects as $project)
                <a href="{{ route('projects.show', $project) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $project->status->label() }}</span>
                        @if ($project->isPublished())
                            <span class="text-xs text-zinc-500">{{ $project->published_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    <div class="mt-2 text-base font-semibold group-hover:text-accent">{{ $project->title }}</div>
                    @if ($project->tagline)
                        <p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ \App\Support\Markdown::plain($project->tagline) }}</p>
                    @endif
                    <div class="mt-3 flex items-center gap-2 text-xs text-zinc-400">
                        @if ($project->isPublished())
                            <span class="inline-flex items-center gap-0.5"><flux:icon name="hand-thumb-up" variant="micro" /> {{ $project->recognition_count }}</span>
                            <span class="inline-flex items-center gap-0.5"><flux:icon name="eye" variant="micro" /> {{ $project->views_count }}</span>
                        @endif
                        <flux:spacer />
                        <span class="inline-flex items-center gap-0.5 text-accent">Edit <flux:icon name="arrow-right" variant="micro" /></span>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                    <flux:heading>No projects yet</flux:heading>
                    <flux:text>Document your first engineering case study.</flux:text>
                    <div class="mt-4">
                        <flux:button variant="primary" href="{{ route('projects.create') }}" wire:navigate>Start a project</flux:button>
                    </div>
                </div>
            @endforelse
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->published as $project)
                <a href="{{ route('projects.show', $project) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center gap-2">
                        <flux:avatar :src="$project->user->avatarUrl()" :alt="$project->user->name" circle class="size-8" />
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <div class="truncate text-sm font-medium">{{ $project->user->name }}</div>
                                <x-verified-badge :user="$project->user" compact />
                            </div>
                            <div class="text-xs text-zinc-500">{{ $project->user->levelTitle() }}</div>
                        </div>
                    </div>
                    <div class="mt-3 text-base font-semibold group-hover:text-accent">{{ $project->title }}</div>
                    @if ($project->tagline)
                        <p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ \App\Support\Markdown::plain($project->tagline) }}</p>
                    @endif
                    @if ($project->tech_stack)
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach (array_slice($project->tech_stack, 0, 4) as $tech)
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ $tech }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-3 flex items-center gap-3 text-xs text-zinc-400">
                        <span class="inline-flex items-center gap-0.5"><flux:icon name="hand-thumb-up" variant="micro" /> {{ $project->recognition_count }}</span>
                        <span class="inline-flex items-center gap-0.5"><flux:icon name="eye" variant="micro" /> {{ $project->views_count }}</span>
                        <flux:spacer />
                        <span>{{ $project->published_at->diffForHumans() }}</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full py-12 text-center text-sm text-zinc-500">No published projects yet. Be the first to publish.</div>
            @endforelse
        </div>

        <div class="py-4">
            {{ $this->published->links() }}
        </div>
    @endif
</div>
