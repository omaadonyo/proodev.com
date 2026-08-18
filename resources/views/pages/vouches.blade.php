<?php

use App\Livewire\Forms\VouchForm;
use App\Models\Skill;
use App\Models\User;
use App\Models\Vouch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Vouches')] class extends Component {
    public VouchForm $form;

    public string $search = '';

    public function submit(): void
    {
        $this->authorize('create', Vouch::class);

        $this->form->validate();

        try {
            app(\App\Actions\Vouches\CreateVouchAction::class)->handle(auth()->user(), $this->form->data());

            Flux::toast(variant: 'success', text: 'Vouch sent. It carries responsibility — and counts.');

            $this->form->reset();
        } catch (\DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    #[Computed]
    public function skills(): \Illuminate\Support\Collection
    {
        return Skill::orderBy('name')->limit(60)->get();
    }

    #[Computed]
    public function searchResults()
    {
        $query = trim($this->search);

        if ($query === '') {
            return collect();
        }

        return User::query()
            ->where('id', '!=', auth()->id())
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$query}%")
                ->orWhere('username', 'like', "%{$query}%"))
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function vouchesGiven()
    {
        return auth()->user()->vouchesGiven()
            ->with(['vouchee', 'skill'])
            ->latest()
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function vouchesReceived()
    {
        return auth()->user()->vouchesReceived()
            ->with(['voucher', 'skill'])
            ->latest()
            ->limit(20)
            ->get();
    }

}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Vouches</flux:heading>
        <flux:text>Skill vouches are the community's currency of trust. You earn <span class="font-semibold text-accent">{{ auth()->user()->vouch_credits }} credits</span> by contributing.</flux:text>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class=" lg:col-span-2">
            <flux:heading size="sm">Give a vouch</flux:heading>

            <form wire:submit="submit" class="mt-4 grid gap-4">
                <flux:field>
                    <flux:label>Who are you vouching for?</flux:label>
                    <flux:input wire:model.live="search" placeholder="Search by name or username…" />
                    @if ($this->search !== '' && $this->searchResults->isNotEmpty())
                        <div class="mt-2 grid gap-1">
                            @foreach ($this->searchResults as $user)
                                <button type="button" wire:click="$set('form.voucheeId', {{ $user->id }})"
                                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-900 {{ $this->form->voucheeId === $user->id ? 'bg-accent/10 text-accent' : '' }}">
                                    <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-7" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <div class="truncate font-medium">{{ $user->name }}</div>
                                            <x-verified-badge :user="$user" compact />
                                        </div>
                                        <div class="truncate text-xs text-zinc-500">@{{ $user->handle() }}</div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <flux:error name="form.voucheeId" />
                </flux:field>

                <flux:field>
                    <flux:label>Type</flux:label>
                    <x-searchable-select wire:model="form.type">
                        @foreach (\App\Enums\VouchType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-searchable-select>
                </flux:field>

                @if ($this->form->type === 'skill')
                    <flux:field>
                        <flux:label>Skill</flux:label>
                        <x-searchable-select wire:model="form.skillId">
                            <option value="">Select a skill…</option>
                            @foreach ($this->skills as $skill)
                                <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                            @endforeach
                        </x-searchable-select>
                        <flux:error name="form.skillId" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Message</flux:label>
                    <flux:textarea wire:model="form.message" rows="3" placeholder="What did they do well?" />
                </flux:field>

                <flux:button type="submit" variant="primary" :disabled="! $this->form->voucheeId">
                    Send vouch
                </flux:button>
            </form>
        </div>

        <div class="grid gap-4 lg:col-span-3">
            <div>
                <flux:heading size="sm">Vouches received</flux:heading>
                <div class="mt-4 grid gap-3">
                    @forelse ($this->vouchesReceived as $vouch)
                        <div class="flex items-start gap-3 text-sm">
                            <flux:avatar :src="$vouch->voucher->avatarUrl()" :alt="$vouch->voucher->name" circle class="size-8 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <div class="text-zinc-700 dark:text-zinc-300">
                                    <span class="font-medium">{{ $vouch->voucher->name }}</span>
                                    <x-verified-badge :user="$vouch->voucher" compact />
                                    vouched for <span class="font-medium text-accent">{{ $vouch->type->label() }}</span>
                                    @if ($vouch->skill) <span class="text-zinc-500">· {{ $vouch->skill->name }}</span> @endif
                                </div>
                                @if ($vouch->message)
                                    <div class="mt-0.5 text-xs text-zinc-500">"{{ $vouch->message }}"</div>
                                @endif
                                <div class="mt-1">
                                    <flux:badge size="sm" inset="top bottom" :variant="$vouch->isApproved() ? 'success' : 'warning'">
                                        {{ $vouch->status->value }}
                                    </flux:badge>
                                </div>
                            </div>
                            @if ($vouch->isApproved())
                                <span class="text-sm font-semibold text-emerald-500">+{{ $vouch->weight }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No vouches yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <flux:heading size="sm">Vouches given</flux:heading>
                <div class="mt-4 grid gap-3">
                    @forelse ($this->vouchesGiven as $vouch)
                        <div class="flex items-start gap-3 text-sm">
                            <flux:avatar :src="$vouch->vouchee->avatarUrl()" :alt="$vouch->vouchee->name" circle class="size-8 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <div class="text-zinc-700 dark:text-zinc-300">
                                    You vouched for <span class="font-medium">{{ $vouch->vouchee->name }}</span>
                                    <x-verified-badge :user="$vouch->vouchee" compact />
                                    <span class="text-zinc-500">· {{ $vouch->type->label() }}</span>
                                    @if ($vouch->skill) <span class="text-zinc-500">· {{ $vouch->skill->name }}</span> @endif
                                </div>
                                <div class="mt-1">
                                    <flux:badge size="sm" inset="top bottom" :variant="$vouch->isApproved() ? 'success' : 'warning'">
                                        {{ $vouch->status->value }}
                                    </flux:badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">You haven't vouched for anyone yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>