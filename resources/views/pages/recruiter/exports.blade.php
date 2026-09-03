<?php

use App\Models\User;
use App\Services\Recruiter\ExecutiveReportService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Executive Candidate Brief')] class extends Component {
    public User $user;

    public function mount(User $candidate): void
    {
        $this->user = $candidate;
    }

    #[Computed]
    public function brief()
    {
        return app(ExecutiveReportService::class)->build($this->user, auth()->user());
    }

    public function downloadMarkdown(): void
    {
        $markdown = $this->renderMarkdown($this->brief);

        $safeName = str_replace(' ', '-', strtolower($this->brief['profile']['name']));

        $this->dispatch('download', [
            'content' => $markdown,
            'filename' => 'candidate-brief-'.$safeName.'.md',
        ]);
    }

    /**
     * @param  array<string, mixed>  $brief
     */
    private function renderMarkdown(array $brief): string
    {
        $lines = [];

        $lines[] = '# '.$brief['meta']['title'].': '.$brief['meta']['candidate'];
        $lines[] = '';
        $lines[] = '> Prepared for '.$brief['meta']['prepared_for'].' on '.$brief['meta']['generated_at'].' ('.$brief['meta']['generated_by'].', '.$brief['meta']['confidence'].'% confidence).';
        $lines[] = '';
        $lines[] = '## Executive summary';
        $lines[] = '';
        $lines[] = $brief['executive_summary'];
        $lines[] = '';
        $lines[] = '## Snapshot';
        $lines[] = '';
        foreach ($brief['snapshot'] as $label => $value) {
            $lines[] = '- **'.Str::title(str_replace('_', ' ', $label)).':** '.$value;
        }
        $lines[] = '';
        $lines[] = '## Engineering Magnitude breakdown';
        $lines[] = '';
        foreach ($brief['magnitude_factors'] as $factor) {
            $lines[] = '- **'.$factor['label'].':** '.$factor['points'].'/'.$factor['max'].' - '.$factor['description'];
        }
        $lines[] = '';
        $lines[] = '## Verified skills';
        $lines[] = '';
        $lines[] = $brief['verified_skills'] !== []
            ? implode(', ', $brief['verified_skills'])
            : 'None.';
        $lines[] = '';
        $lines[] = '## Strengths';
        $lines[] = '';
        foreach ($brief['strengths'] as $strength) {
            $lines[] = '- '.$strength;
        }
        $lines[] = '';
        $lines[] = '## Concerns';
        $lines[] = '';
        foreach ($brief['concerns'] as $concern) {
            $lines[] = '- '.$concern;
        }
        $lines[] = '';
        $lines[] = '## Recommended roles';
        $lines[] = '';
        $lines[] = implode(', ', $brief['recommended_roles']);
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '_'.$brief['disclaimer'].'_';

        return implode("\n", $lines);
    }
}
?>

<div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Executive Candidate Brief</flux:heading>
            <flux:text>An evidence-backed deliverable for hiring managers and execs.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" wire:click="downloadMarkdown">
                <flux:icon name="arrow-down-tray" variant="micro" />
                Download Markdown
            </flux:button>
            <flux:button :href="route('recruiter.candidates.show', $this->user->id)" wire:navigate>
                Back to report
            </flux:button>
        </div>
    </div>

    <div class="mx-auto w-full max-w-3xl rounded-xl bg-zinc-100 p-8 dark:bg-white/5">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs uppercase tracking-wide text-zinc-400">Candidate Executive Brief</div>
                <flux:heading size="2xl" class="mt-1">{{ $this->brief['meta']['candidate'] }}</flux:heading>
                <flux:text class="mt-1">{{ $this->brief['profile']['headline'] }}</flux:text>
                <flux:text class="text-sm">Prepared for {{ $this->brief['meta']['prepared_for'] }} - {{ $this->brief['meta']['generated_at'] }}</flux:text>
            </div>
            <span class="rounded-full bg-accent/10 px-3 py-1 text-sm font-semibold text-accent">{{ $this->brief['meta']['confidence'] }}% confidence</span>
        </div>

        <div class="mt-6 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
            <div class="text-xs uppercase tracking-wide text-zinc-400">Executive summary</div>
            <p class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-200">{{ $this->brief['executive_summary'] }}</p>
        </div>

        <div class="mt-6">
            <div class="text-xs uppercase tracking-wide text-zinc-400">Snapshot</div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($this->brief['snapshot'] as $label => $value)
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                        <div class="text-xs text-zinc-500">{{ Str::title(str_replace('_', ' ', $label)) }}</div>
                        <div class="mt-0.5 text-sm font-medium">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6">
            <div class="text-xs uppercase tracking-wide text-zinc-400">Engineering Magnitude breakdown</div>
            <div class="mt-3 grid gap-2">
                @foreach ($this->brief['magnitude_factors'] as $factor)
                    <div class="flex items-center justify-between rounded-lg bg-zinc-100 px-3 py-2 text-sm dark:bg-white/5">
                        <div class="min-w-0">
                            <div class="font-medium">{{ $factor['label'] }}</div>
                            <div class="truncate text-xs text-zinc-500">{{ $factor['description'] }}</div>
                        </div>
                        <span class="ml-3 shrink-0 font-semibold">{{ $factor['points'] }}/{{ $factor['max'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            <div>
                <div class="text-xs uppercase tracking-wide text-zinc-400">Verified skills</div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse ($this->brief['verified_skills'] as $skill)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <flux:icon name="check" variant="micro" /> {{ $skill }}
                        </span>
                    @empty
                        <span class="text-sm text-zinc-500">None.</span>
                    @endforelse
                </div>

                <div class="text-xs uppercase tracking-wide text-zinc-400 mt-5">Recommended roles</div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($this->brief['recommended_roles'] as $role)
                        <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium dark:bg-zinc-900">{{ $role }}</span>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-zinc-400">Community</div>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <div><div class="font-semibold">{{ $this->brief['community']['vouches'] }}</div><div class="text-xs text-zinc-500">vouches</div></div>
                    <div><div class="font-semibold">{{ $this->brief['community']['projects_shipped'] }}</div><div class="text-xs text-zinc-500">projects</div></div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            <div>
                <div class="text-xs uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Strengths</div>
                <ul class="mt-2 grid gap-1.5 text-sm text-zinc-600 dark:text-zinc-300">
                    @foreach ($this->brief['strengths'] as $strength)
                        <li class="flex gap-2"><flux:icon name="check-circle" variant="micro" class="mt-0.5 shrink-0 text-emerald-500" />{{ $strength }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-amber-600 dark:text-amber-400">Concerns</div>
                <ul class="mt-2 grid gap-1.5 text-sm text-zinc-600 dark:text-zinc-300">
                    @forelse ($this->brief['concerns'] as $concern)
                        <li class="flex gap-2"><flux:icon name="exclamation-circle" variant="micro" class="mt-0.5 shrink-0 text-amber-500" />{{ $concern }}</li>
                    @empty
                        <li class="text-zinc-500">None identified.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <p class="mt-8 border-t border-zinc-100 pt-4 text-xs italic text-zinc-400 dark:border-zinc-700">{{ $this->brief['disclaimer'] }}</p>
    </div>
</div>
