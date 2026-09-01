<?php

namespace App\Livewire;

use App\Actions\Vouches\CreateVouchAction;
use App\Livewire\Forms\VouchForm;
use App\Models\Skill;
use App\Models\User;
use App\Models\Vouch;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class VouchDialog extends Component
{
    public int $userId;

    public VouchForm $form;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
        $this->form->voucheeId = $userId;
    }

    #[Computed]
    public function user(): User
    {
        return User::findOrFail($this->userId);
    }

    #[Computed]
    public function skills(): Collection
    {
        return Skill::orderBy('name')->limit(60)->get();
    }

    public function submit(): void
    {
        $this->authorize('create', Vouch::class);

        $this->form->validate();

        try {
            app(CreateVouchAction::class)->handle(auth()->user(), $this->form->data());

            Flux::toast(variant: 'success', text: 'Vouch sent. It carries responsibility — and counts.');
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->form->reset();
        $this->form->voucheeId = $this->userId;

        $this->dispatch('vouch-created', userId: $this->userId);
    }

    public function render(): View
    {
        return view('livewire.vouch-dialog');
    }
}
