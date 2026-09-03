<?php

use App\Models\User;
use App\Services\Recruiter\ResumeValidationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Resume vs Evidence Validation')] class extends Component {
    public string $search = '';

    public ?int $candidateId = null;

    public string $resumeText = '';

    public bool $ran = false;

    #[Computed]
    public function searchResults()
    {
        if (strlen(trim($this->search)) < 2 || $this->candidateId) {
            return collect();
        }

        return User::query()
            ->where('public_passport', true)
            ->where(fn ($q) => $q
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('username', 'like', '%'.trim($this->search).'%')
                ->orWhere('headline', 'like', '%'.trim($this->search).'%'))
            ->orderByDesc('reputation_score')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function candidate()
    {
        return $this->candidateId ? User::find($this->candidateId) : null;
    }

    #[Computed]
    public function result()
    {
        if (! $this->ran || ! $this->candidateId) {
            return null;
        }

        return app(ResumeValidationService::class)->validate(
            auth()->user(),
            User::findOrFail($this->candidateId),
            $this->resumeText,
        );
    }

    public function selectCandidate(int $id): void
    {
        $this->candidateId = $id;
        $this->search = '';
    }

    public function runValidation(): void
    {
        $this->validate([
            'candidateId' => ['required'],
            'resumeText' => ['required', 'string', 'min:40'],
        ]);

        $this->ran = true;
    }

    public function startOver(): void
    {
        $this->reset('search', 'candidateId', 'resumeText', 'ran');
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Resume vs Evidence Validation</flux:heading>
        <flux:text>Paste a candidate's resume and ProoDev checks its claims against their analyzed evidence. Claims have to point to proof.</flux:text>
    </div>

    <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
        <flux:heading size="sm">Step 1 - Select candidate</flux:heading>

        @if ($this->candidate)
            <div class="mt-3 flex items-center gap-3 rounded-lg border border-accent/40 bg-accent/5 p-3">
                <flux:avatar :src="$this->candidate->avatarUrl()" :alt="$this->candidate->name" circle class="size-9" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <div class="truncate font-medium">{{ $this->candidate->name }}</div>
                        <x-verified-badge :user="$this->candidate" compact />
                    </div>
                    <div class="truncate text-xs text-zinc-500">{{ $this->candidate->headline }}</div>
                </div>
                <flux:button size="xs" variant="ghost" wire:click="$set('candidateId', null)">Change</flux:button>
            </div>
        @else
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search engineers..." class="mt-3" />
            @if ($this->searchResults->isNotEmpty())
                <div class="mt-2 grid gap-2">
                    @foreach ($this->searchResults as $user)
                        <button type="button" wire:click="selectCandidate({{ $user->id }})" class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 text-left transition hover:border-accent dark:border-zinc-700">
                            <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-8" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <div class="truncate text-sm font-medium">{{ $user->name }}</div>
                                    <x-verified-badge :user="$user" compact />
                                </div>
                                <div class="truncate text-xs text-zinc-500">{{ $user->headline }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif

        <flux:heading size="sm" class="mt-6">Step 2 - Paste the resume</flux:heading>
        <flux:textarea wire:model="resumeText" rows="10" placeholder="Paste the candidate's resume text here..." class="mt-3" />

        <div class="mt-4 flex items-center gap-2">
            <flux:button variant="primary" wire:click="runValidation" :disabled="!$this->candidate || strlen($this->resumeText) < 40">
                <flux:icon name="document-check" variant="micro" />
                Validate against evidence
            </flux:button>
            @if ($this->ran)
                <flux:button variant="ghost" size="sm" wire:click="startOver">Start over</flux:button>
            @endif
        </div>
    </div>

    @if ($this->result)
        <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="sm">Validation result</flux:heading>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-900">
                            <div class="h-full rounded-full {{ $this->result['proof_rate'] >= 80 ? 'bg-emerald-500' : ($this->result['proof_rate'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $this->result['proof_rate'] }}%"></div>
                        </div>
                        <span class="text-sm font-semibold">{{ $this->result['proof_rate'] }}% proven</span>
                    </div>
                    <span class="text-sm text-zinc-500">{{ $this->result['confidence'] }}% confidence</span>
                </div>
            </div>

            <div class="mt-3 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
                <div class="font-medium">{{ $this->result['verdict'] }}</div>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $this->result['evidence_count'] }} analyzed evidence sources - evidence supports at most {{ $this->result['evidence_seniority'] }} seniority.</p>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <div class="font-semibold text-emerald-700 dark:text-emerald-300">Proven claims ({{ count($this->result['proven_claims']) }})</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @if (count($this->result['proven_claims']) > 0)
                            @foreach ($this->result['proven_claims'] as $claim)
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $claim }}</span>
                            @endforeach
                        @else
                            <span class="text-sm text-zinc-500">None detected.</span>
                        @endif
                    </div>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                    <div class="font-semibold text-amber-700 dark:text-amber-300">Unproven claims ({{ count($this->result['unproven_claims']) }})</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @if (count($this->result['unproven_claims']) > 0)
                            @foreach ($this->result['unproven_claims'] as $claim)
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ $claim }}</span>
                            @endforeach
                        @else
                            <span class="text-sm text-zinc-500">None detected.</span>
                        @endif
                    </div>
                </div>
            </div>

            @if ($this->result['contradictions'] !== [])
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <div class="font-semibold text-red-700 dark:text-red-300">Contradictions</div>
                    <ul class="mt-2 grid gap-1.5 text-sm">
                        @foreach ($this->result['contradictions'] as $contradiction)
                            <li class="flex gap-2 text-red-700 dark:text-red-300">
                                <flux:icon name="x-circle" variant="micro" class="mt-0.5 shrink-0" /> {{ $contradiction }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4">
                <flux:button :href="route('recruiter.candidates.show', $this->result['candidate']['id'])" wire:navigate>
                    Open full candidate report
                </flux:button>
            </div>
        </div>
    @endif
</div>
