<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?int $candidateId = null;

    private const SESSION_KEY = 'recruiter_compare_ids';

    public const MAX = 3;

    #[Computed]
    public function stack()
    {
        return User::whereIn('id', $this->stackIds())->get();
    }

    #[On('add-to-compare')]
    public function addFromEvent(int $candidateId): void
    {
        $this->candidateId = $candidateId;
        $this->add();
    }

    public function add(): void
    {
        $id = $this->candidateId;

        if ($id === null) {
            return;
        }

        $ids = $this->stackIds();

        if (in_array($id, $ids, true)) {
            $this->dispatch('toast', message: 'Candidate is already in the comparison stack.', variant: 'info');

            return;
        }

        if (count($ids) >= self::MAX) {
            $this->dispatch('toast', message: 'Compare up to '.self::MAX.' candidates.', variant: 'warning');

            return;
        }

        session()->push(self::SESSION_KEY, $id);

        $candidate = User::find($id);

        $this->dispatch('toast', message: ($candidate?->name ?? 'Candidate').' added to comparison ('.count($this->stackIds()).'/'.self::MAX.').', variant: 'success');
    }

    public function remove(int $id): void
    {
        session()->put(self::SESSION_KEY, array_values(array_diff($this->stackIds(), [$id])));
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function compare(): void
    {
        $ids = $this->stackIds();

        if ($ids === []) {
            return;
        }

        $this->redirectRoute('recruiter.compare', ['ids' => implode(',', $ids)], navigate: true);
    }

    /**
     * @return array<int, int>
     */
    private function stackIds(): array
    {
        return array_values(array_filter(
            array_map('intval', (array) session(self::SESSION_KEY, [])),
            fn (int $id) => $id > 0,
        ));
    }
}
?>

<div>
    @if ($this->stack->isNotEmpty())
        <div class="fixed bottom-4 right-4 z-50 w-80 rounded-xl bg-zinc-100 p-3 shadow-2xl shadow-zinc-900/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Comparison stack ({{ $this->stack->count() }}/3)</span>
                <button type="button" wire:click="clear" class="text-xs font-medium text-zinc-400 transition hover:text-red-500">Clear</button>
            </div>

            <div class="mt-2 grid gap-1.5">
                @foreach ($this->stack as $candidate)
                    <div wire:key="ct-{{ $candidate->id }}" class="flex items-center gap-2 rounded-lg bg-zinc-50 p-1.5 dark:bg-zinc-800">
                        <flux:avatar :src="$candidate->avatarUrl()" :alt="$candidate->name" circle class="size-7" />
                        <div class="min-w-0 flex-1 truncate text-sm">{{ $candidate->name }}</div>
                        <button type="button" wire:click="remove({{ $candidate->id }})" class="text-zinc-400 transition hover:text-red-500">
                            <flux:icon name="x-mark" variant="micro" />
                        </button>
                    </div>
                @endforeach
            </div>

            <flux:button variant="primary" size="sm" class="mt-2 w-full justify-center" wire:click="compare">
                <flux:icon name="scale" variant="micro" />
                Compare {{ $this->stack->count() }} candidate{{ $this->stack->count() === 1 ? '' : 's' }}
            </flux:button>
        </div>
    @endif
</div>
