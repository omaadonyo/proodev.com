<?php

use App\Actions\Comments\AddCommentAction;
use App\Actions\Projects\RecognizeProjectAction;
use App\Enums\RecognitionType;
use App\Models\Project;
use App\Models\ProjectRecognition;
use App\Services\ReputationService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project')] class extends Component
{
    public Project $project;

    public string $comment = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;

        if ($project->isPublished()) {
            $project->increment('views_count');
        }
    }

    #[Computed]
    public function recognitions(): array
    {
        return $this->project->recognitionTypes();
    }

    #[Computed]
    public function myRecognition(): ?ProjectRecognition
    {
        return ProjectRecognition::where('project_id', $this->project->id)
            ->where('user_id', auth()->id())
            ->first();
    }

    #[Computed]
    public function comments()
    {
        return $this->project->comments()
            ->with('user')
            ->latest()
            ->take(20)
            ->get();
    }

    #[Computed]
    public function authorReputation(): array
    {
        return app(ReputationService::class)->breakdown($this->project->user);
    }

    public function recognize(string $type): void
    {
        $this->authorize('recognize', $this->project);

        $result = app(RecognizeProjectAction::class)->toggle(auth()->user(), $this->project, RecognitionType::from($type));

        unset($this->recognitions, $this->myRecognition);

        Flux::toast(variant: 'success', text: $result['removed'] ? 'Recognition removed.' : 'Recognition recorded, thank you.');
    }

    public function postComment(): void
    {
        $validated = $this->validate(['comment' => ['required', 'string', 'min:2', 'max:2000']]);

        app(AddCommentAction::class)->handle(auth()->user(), $this->project, $validated['comment']);

        $this->comment = '';

        unset($this->comments);

        Flux::toast(variant: 'success', text: 'Comment posted.');
    }

    #[On('echo:feed,feed-event')]
    public function refresh(): void
    {
        unset($this->comments);
    }
}
?>

<div class="mx-auto grid max-w-4xl gap-6">
    <div>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="2xl">{{ $this->project->title }}</flux:heading>
                    @if ($this->project->verification_status === \App\Enums\ProjectVerificationStatus::Verified)
                        <flux:badge variant="success" inset="top bottom">
                            <flux:icon name="check-badge" variant="micro" /> Verified
                        </flux:badge>
                    @endif
                    @if ($this->project->ai_score !== null)
                        <flux:badge color="zinc" inset="top bottom">
                            <flux:icon name="sparkles" variant="micro" /> {{ $this->project->ai_score }}/100
                        </flux:badge>
                    @endif
                </div>

                @if ($this->project->tagline)
                    <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $this->project->tagline }}</p>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500">
                    <a href="{{ route('devid', $this->project->user->handle()) }}" wire:navigate class="flex items-center gap-1.5 font-medium hover:underline">
                        <flux:avatar :src="$this->project->user->avatarUrl()" :alt="$this->project->user->name" circle class="size-5" />
                        {{ $this->project->user->name }}
                    </a>
                    <x-verified-badge :user="$this->project->user" compact />
                    <span>{{ $this->project->published_at?->diffForHumans() }}</span>
                    <span class="inline-flex items-center gap-1"><flux:icon name="eye" variant="micro" /> {{ $this->project->views_count }}</span>
                </div>
            </div>

            @if ($this->project->user_id === auth()->id())
                <flux:button variant="subtle" href="{{ route('projects.edit', $this->project) }}" wire:navigate>
                    <flux:icon name="pencil" variant="micro" /> Edit
                </flux:button>
            @endif
        </div>

        @if ($this->project->tech_stack)
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($this->project->tech_stack as $tech)
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium dark:bg-zinc-900">{{ $tech }}</span>
                @endforeach
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            @if ($this->project->demo_url)
                <flux:button variant="primary" size="sm" href="{{ $this->project->demo_url }}" target="_blank">
                    <flux:icon name="arrow-top-right-on-square" variant="micro" /> Live demo
                </flux:button>
            @endif
            @if ($this->project->repository_url)
                <flux:button variant="subtle" size="sm" href="{{ $this->project->repository_url }}" target="_blank">
                    <flux:icon name="folder-git-2" variant="micro" /> Repository
                </flux:button>
            @endif
        </div>
    </div>

    @if ($this->project->ai_summary)
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <div class="flex items-center gap-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                <flux:icon name="sparkles" variant="micro" /> AI Summary
            </div>
            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $this->project->ai_summary }}</p>
        </div>
    @endif

    @auth
        @if ($this->project->isPublished() && $this->project->user_id !== auth()->id())
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">Recognize this work</flux:heading>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($this->recognitions as $key => $recognition)
                        <button
                            type="button"
                            wire:click="recognize('{{ $key }}')"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition {{ $this->myRecognition?->type->value === $key ? 'border-accent bg-accent text-accent-foreground' : 'border-zinc-300 hover:border-accent dark:border-zinc-600' }}"
                        >
                            {{ $recognition['label'] }}
                            @if ($recognition['count'] > 0)
                                <span class="text-zinc-400">{{ $recognition['count'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endauth

    <div class="grid gap-6">
        <div>
            <flux:heading size="sm">Problem</flux:heading>
            <x-markdown :text="$this->project->problem" class="mt-3" />
        </div>

        <div>
            <flux:heading size="sm">Solution</flux:heading>
            <x-markdown :text="$this->project->solution" class="mt-3" />
        </div>

        @if ($this->project->architecture)
            <div>
                <flux:heading size="sm">Architecture</flux:heading>
                <x-markdown :text="$this->project->architecture" class="mt-3" />
            </div>
        @endif

        @if ($this->project->engineering_decisions)
            <div>
                <flux:heading size="sm">Engineering decisions</flux:heading>
                <div class="mt-3 grid gap-3">
                    @foreach ($this->project->engineering_decisions as $decision)
                        <div class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-accent"></span>
                            <span>{{ $decision }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($this->project->lessons_learned)
            <div>
                <flux:heading size="sm">Lessons learned</flux:heading>
                <x-markdown :text="$this->project->lessons_learned" class="mt-3" />
            </div>
        @endif
    </div>

    <div>
        <flux:heading size="sm">Discussion</flux:heading>

        <form wire:submit="postComment" class="mt-4 flex items-start gap-3">
            <flux:avatar :src="auth()->user()->avatarUrl()" :alt="auth()->user()->name" circle class="size-8 shrink-0" />
            <div class="flex-1">
                <flux:textarea wire:model="comment" rows="2" placeholder="Share feedback or @mention another engineer…" />
                <flux:error name="comment" />
            </div>
            <flux:button type="submit" variant="primary" size="sm">Post</flux:button>
        </form>

        <div class="mt-5 grid gap-4">
            @forelse ($this->comments as $comment)
                <div class="flex items-start gap-3">
                    <flux:avatar :src="$comment->user->avatarUrl()" :alt="$comment->user->name" circle class="size-8 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-semibold">{{ $comment->user->name }}</span>
                            <x-verified-badge :user="$comment->user" compact />
                            <span class="text-xs text-zinc-400">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-0.5 whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $comment->body }}</div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-zinc-500">No discussion yet. Start the conversation.</p>
            @endforelse
        </div>
    </div>
</div>