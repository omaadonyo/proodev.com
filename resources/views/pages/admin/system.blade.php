<?php

use App\Services\SystemResetService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('System Reset')] class extends Component
{
    public bool $confirming = false;

    public string $confirmation = '';

    public ?array $result = null;

    public function openConfirm(): void
    {
        $this->confirmation = '';
        $this->confirming = true;
    }

    public function runReset(): void
    {
        if ($this->confirmation !== 'RESET') {
            Flux::toast(variant: 'warning', text: 'Type RESET to confirm the reset.');

            return;
        }

        try {
            $this->result = app(SystemResetService::class)->reset();
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: 'Reset failed: '.$e->getMessage());

            return;
        }

        $this->confirming = false;
        $this->confirmation = '';

        unset($this->counts);

        Flux::toast(variant: 'success', text: 'System reset complete. Demo data removed.');
    }

    #[Computed]
    public function counts(): array
    {
        return app(SystemResetService::class)->counts();
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">System reset</flux:heading>
        <flux:text>Wipe demo data and trim the user base back to a clean, presentable state.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Users</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['users']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->counts['users_removed']) }} will be removed</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Developers</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['developers']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->counts['developers_with_photo']) }} with photo</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Companies</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['companies']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->counts['jobs']) }} open roles</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Transactions</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['payments'] + $this->counts['credit_transactions']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->counts['credit_transactions']) }} credit transactions</div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Evidence</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['evidence']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Projects</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['projects']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Vouches</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['vouches']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Chats</div>
            <div class="text-2xl font-bold">{{ number_format($this->counts['chats']) }}</div>
            <div class="mt-1 text-xs text-zinc-400">{{ number_format($this->counts['messages']) }} messages</div>
        </div>
    </div>

    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/20">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <flux:heading size="sm" class="text-red-700 dark:text-red-400">Reset to clean state</flux:heading>
                <p class="mt-1 max-w-2xl text-sm text-red-700/80 dark:text-red-300/80">
                    Removes every payment, credit transaction, company, open role, application, evidence item,
                    project, journal entry, vouch, report, chat, notification and login/analytics record across
                    {{ number_format($this->counts['tables']) }} tables. Every account except the platform admin
                    (<strong>adonyo@proodev.com</strong>) is removed ({{ number_format($this->counts['users_removed']) }}
                    users), then <strong>50 realistic engineers from around the world</strong> are reseeded with
                    full records — skills, XP, streaks, projects, vouches, journal entries and verifications.
                    Skills and achievements stay. This action is permanent and cannot be undone.
                </p>
                <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">This action is permanent and cannot be undone.</p>
            </div>
            <flux:button variant="danger" wire:click="openConfirm">
                <flux:icon name="arrow-path" variant="micro" />
                Reset system
            </flux:button>
        </div>
    </div>

    @if ($this->result)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
            <flux:heading size="sm" class="text-emerald-700 dark:text-emerald-400">Reset complete</flux:heading>
            <p class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-300/80">
                {{ number_format($this->result['users_removed']) }} user accounts removed ·
                {{ number_format($this->result['users_reseeded']) }} engineers reseeded ·
                {{ number_format($this->result['accounts_kept']) }} accounts kept ·
                data purged from {{ number_format($this->result['tables']) }} tables.
            </p>
        </div>
    @endif

    <flux:modal name="confirm-reset" wire:model="confirming" class="max-w-lg">
        <form wire:submit="runReset" class="grid gap-4">
            <div>
                <flux:heading size="lg">Reset the system?</flux:heading>
                <flux:text>
                    This permanently deletes all demo data and removes every account except the platform admin
                    (adonyo@proodev.com), then reseeds 50 realistic engineers from around the world with full
                    records. Type <span class="font-semibold text-red-600 dark:text-red-400">RESET</span>
                    to continue.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Type RESET to confirm</flux:label>
                <flux:input wire:model="confirmation" placeholder="RESET" autocomplete="off" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="subtle" type="button" @click="$flux.modal('confirm-reset').close()">Cancel</flux:button>
                <flux:button type="submit" variant="danger">Delete everything and reset</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
