<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Applications')] class extends Component {
    #[Computed]
    public function applications()
    {
        return auth()->user()
            ->applications()
            ->with(['job.company'])
            ->latest()
            ->get();
    }
}
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">My applications</flux:heading>
            <flux:text>Track every role you've applied to with your DevID.</flux:text>
        </div>

        <div class="grid gap-3">
            @forelse ($this->applications as $application)
                <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $application->job->title }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-zinc-500">
                                <a href="{{ route('companies.show', $application->job->company) }}" wire:navigate class="text-accent hover:underline">{{ $application->job->company->name }}</a>
                                <span>{{ $application->job->is_remote ? 'Remote' : ($application->job->location ?: 'On-site') }}</span>
                                <span>{{ $application->created_at->diffForHumans() }}</span>
                                @if ($application->resume_path)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                        <flux:icon name="document-arrow-down" variant="micro" />
                                        Resume attached
                                    </span>
                                    @if (($application->resume_view_count ?? 0) > 0)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 font-medium text-accent" title="Times an employer opened your CV">
                                            <flux:icon name="eye" variant="micro" />
                                            CV viewed {{ $application->resume_view_count }} {{ $application->resume_view_count === 1 ? 'time' : 'times' }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" inset="top bottom" :color="match ($application->status->value) {
                                'shortlisted' => 'green',
                                'rejected' => 'red',
                                'hired' => 'sky',
                                default => 'zinc',
                            }">{{ $application->status->label() }}</flux:badge>
                            <flux:button size="sm" variant="subtle" :href="route('jobs.show', ['company' => $application->job->company, 'job' => $application->job])" wire:navigate>
                                View role
                            </flux:button>
                        </div>
                    </div>
                    @if ($application->notes)
                        <div class="mt-3 rounded-lg bg-amber-400/10 p-3 text-xs text-amber-700 dark:text-amber-300">
                            <span class="font-semibold">Recruiter note:</span> {{ $application->notes }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-600">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <flux:icon name="document-text" />
                    </div>
                    <flux:heading>No applications yet</flux:heading>
                    <flux:text class="mt-2">Browse open roles and apply with your evidence-backed DevID.</flux:text>
                    <flux:button class="mt-4" variant="primary" :href="route('jobs.index')" wire:navigate>Browse jobs</flux:button>
                </div>
            @endforelse
        </div>
    </div>
</div>