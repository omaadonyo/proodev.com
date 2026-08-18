<?php

namespace App\Livewire;

use App\Services\Recruiter\WorkspaceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WorkspaceSwitcher extends Component
{
    public bool $compact = false;

    #[Computed]
    public function currentWorkspace()
    {
        return app(WorkspaceService::class)->current(auth()->user());
    }

    #[Computed]
    public function available(): Collection
    {
        return app(WorkspaceService::class)->available(auth()->user());
    }

    #[Computed]
    public function canManage(): bool
    {
        return auth()->user()->hasWorkspaceAccess();
    }

    public function switchTo(int $workspaceId): void
    {
        app(WorkspaceService::class)->switchToId(auth()->user(), $workspaceId);

        $this->redirectRoute('recruiter.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.workspace-switcher');
    }
}
