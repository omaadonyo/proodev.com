<?php

use App\Models\Workspace;
use App\Services\Recruiter\WorkspaceService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Workspaces')] class extends Component {
    public string $newName = '';

    public bool $showCreate = false;

    public ?int $manageWorkspaceId = null;

    public string $memberEmail = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasWorkspaceAccess(), 403, 'Workspaces require the Recruiter or Recruiter Intelligence plan.');
    }

    public function createWorkspace(): void
    {
        $validated = $this->validate(['newName' => ['required', 'string', 'max:100']]);

        $workspace = app(WorkspaceService::class)->create(auth()->user(), $validated['newName']);

        $this->reset('newName', 'showCreate');

        Flux::toast(variant: 'success', text: 'Workspace "'.$workspace->name.'" created. You are the owner.');

        $this->redirectRoute('workspaces', navigate: true);
    }

    public function switchWorkspace(int $workspaceId): void
    {
        app(WorkspaceService::class)->switchToId(auth()->user(), $workspaceId);

        Flux::toast(variant: 'success', text: 'Switched to the selected workspace.');

        $this->redirectRoute('recruiter.index', navigate: true);
    }

    public function manageWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::findOrFail($workspaceId);

        abort_unless($workspace->isOwner(auth()->user()) || auth()->user()->isAdmin(), 403);

        $this->manageWorkspaceId = $workspaceId;
        $this->memberEmail = '';
    }

    public function addMember(): void
    {
        $workspace = Workspace::findOrFail($this->manageWorkspaceId);

        abort_unless($workspace->isOwner(auth()->user()) || auth()->user()->isAdmin(), 403);

        $validated = $this->validate(['memberEmail' => ['required', 'email']]);

        $member = \App\Models\User::where('email', $validated['memberEmail'])->first();

        if (! $member) {
            $this->addError('memberEmail', 'No account found for that email.');

            return;
        }

        if ($workspace->users()->whereKey($member->id)->exists()) {
            $this->addError('memberEmail', 'That user is already a member of this workspace.');

            return;
        }

        app(WorkspaceService::class)->addMember($workspace, $member);

        $this->memberEmail = '';

        unset($this->workspaceDetail);

        Flux::toast(variant: 'success', text: $member->name.' added as a workspace member.');
    }

    public function removeMember(int $workspaceId, int $userId): void
    {
        $workspace = Workspace::findOrFail($workspaceId);

        abort_unless($workspace->isOwner(auth()->user()) || auth()->user()->isAdmin(), 403);

        $member = \App\Models\User::findOrFail($userId);

        app(WorkspaceService::class)->removeMember($workspace, $member);

        unset($this->workspaceDetail);

        Flux::toast(variant: 'success', text: $member->name.' removed from the workspace.');
    }

    #[Computed]
    public function workspaces()
    {
        return app(WorkspaceService::class)->available(auth()->user());
    }

    #[Computed]
    public function currentWorkspace()
    {
        return app(WorkspaceService::class)->current(auth()->user());
    }

    #[Computed]
    public function workspaceDetail()
    {
        if (! $this->manageWorkspaceId) {
            return null;
        }

        $workspace = Workspace::with('users')->find($this->manageWorkspaceId);

        abort_unless($workspace && ($workspace->isOwner(auth()->user()) || auth()->user()->isAdmin()), 403);

        return $workspace;
    }
}
?>

<div class="mx-auto w-full max-w-4xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">Workspaces</flux:heading>
                <flux:text>Create workspaces, invite recruiter seats, and switch between them. Each workspace keeps its own talent pools, alerts, notes, and candidates.</flux:text>
            </div>
            <flux:button variant="primary" wire:click="$toggle('showCreate')">
                <flux:icon name="plus" variant="micro" />
                New workspace
            </flux:button>
        </div>

        @if ($showCreate)
            <form wire:submit="createWorkspace" class="rounded-xl border border-accent/40 bg-accent/5 p-5">
                <flux:heading size="sm">Create a workspace</flux:heading>
                <flux:text class="mt-1">Give it a name - e.g. an agency name or a client you recruit for.</flux:text>
                <div class="mt-4 flex items-end gap-3">
                    <flux:field class="flex-1">
                        <flux:label>Workspace name</flux:label>
                        <flux:input wire:model="newName" placeholder="e.g. Northstar Talent" />
                        <flux:error name="newName" />
                    </flux:field>
                    <flux:button type="submit" variant="primary">Create</flux:button>
                </div>
            </form>
        @endif

        <div class="grid gap-4">
            @forelse ($this->workspaces as $workspace)
                <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex size-11 items-center justify-center rounded-lg bg-accent/10 text-sm font-bold text-accent">
                                {{ \Illuminate\Support\Str::initials($workspace->name, true) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 font-medium">
                                    {{ $workspace->name }}
                                    @if ($this->currentWorkspace?->id === $workspace->id)
                                        <flux:badge size="sm" color="emerald" inset="top bottom">Active</flux:badge>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $workspace->users()->count() }} seat(s)
                                    @if ($workspace->isOwner(auth()->user()))
                                        · You own this workspace
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($this->currentWorkspace?->id !== $workspace->id)
                                <flux:button size="sm" variant="primary" wire:click="switchWorkspace({{ $workspace->id }})">Switch</flux:button>
                            @endif
                            @if ($workspace->isOwner(auth()->user()) || auth()->user()->isAdmin())
                                <flux:button size="sm" variant="subtle" wire:click="manageWorkspace({{ $workspace->id }})">
                                    <flux:icon name="users" variant="micro" />
                                    Manage seats
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    @if ($this->manageWorkspaceId === $workspace->id && $this->workspaceDetail)
                        <div class="mt-5 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
                            <flux:heading size="sm">Members ({{ $this->workspaceDetail->users()->count() }})</flux:heading>

                            <div class="mt-3 grid gap-2">
                                @foreach ($this->workspaceDetail->users as $member)
                                    <div class="flex items-center gap-3 rounded-lg bg-zinc-100 p-3 text-sm dark:bg-white/5">
                                        <flux:avatar :src="$member->avatarUrl()" :alt="$member->name" circle class="size-8" />
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate font-medium">{{ $member->name }}</div>
                                            <div class="truncate text-xs text-zinc-500">{{ $member->email }}</div>
                                        </div>
                                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium capitalize dark:bg-zinc-900">{{ $member->pivot->role }}</span>
                                        @if (! $this->workspaceDetail->isOwner($member))
                                            <flux:button size="xs" variant="danger" wire:click="removeMember({{ $workspace->id }}, {{ $member->id }})" wire:confirm="Remove {{ $member->name }} from this workspace?">
                                                <flux:icon name="trash" variant="micro" />
                                            </flux:button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 flex items-end gap-3">
                                <flux:field class="flex-1">
                                    <flux:label>Add member by email</flux:label>
                                    <flux:input type="email" wire:model="memberEmail" placeholder="recruiter@example.com" />
                                    <flux:error name="memberEmail" />
                                </flux:field>
                                <flux:button variant="primary" wire:click="addMember">Add seat</flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-600">
                    <flux:heading>No workspaces yet</flux:heading>
                    <flux:text class="mt-1">Create a workspace to start collaborating with other recruiters and keep data isolated per client or agency.</flux:text>
                    <flux:button class="mt-4" variant="primary" wire:click="$set('showCreate', true)">Create your first workspace</flux:button>
                </div>
            @endforelse
        </div>
    </div>
</div>
