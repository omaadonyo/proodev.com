<?php

use App\Mail\ContactMessageMail;
use App\Models\Evidence;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('About')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $message = '';

    public string $website = '';

    public bool $sent = false;

    protected $spamKeywords = ['http://', 'https://', 'casino', 'crypto giveaway', 'seo services', 'viagra'];

    public function mount(): void
    {
        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
        }
    }

    public function submit(): void
    {
        // Honeypot: bots fill every field, humans leave this hidden one empty.
        if ($this->website !== '') {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $lower = strtolower($validated['message']);

        if (collect($this->spamKeywords)->contains(fn ($keyword) => str_contains($lower, $keyword))) {
            Flux::toast(variant: 'warning', text: 'Your message looks like spam and was not sent.');

            return;
        }

        Mail::to(config('platform.admin_email'))->send(new ContactMessageMail(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            messageBody: $validated['message'],
        ));

        $this->reset('name', 'email', 'message');
        $this->sent = true;

        Flux::toast(variant: 'success', text: 'Message sent — we will get back to you soon.');
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'developers' => User::visibleToPublic()->count(),
            'verified' => User::where('is_verified', true)->count(),
            'evidence' => Evidence::ready()->count(),
        ];
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-10">
        {{-- About us --}}
        <div class="grid gap-4">
            <flux:heading size="xl">About ProoDev</flux:heading>
            <flux:text class="max-w-3xl">
                ProoDev is a developer-centered professional identity platform. We help developers turn the work they
                have already done — repositories, pull requests, open-source contributions, projects, packages and
                articles — into understandable, evidence-backed proof of their engineering ability.
            </flux:text>
            <flux:text class="max-w-3xl">
                Most of a developer's best work disappears into commit histories and merged PRs. ProoDev brings the
                meaning behind that work forward: what you built, the problems you solved, why it matters — and it
                connects that proof to real opportunities with companies looking for engineers like you.
            </flux:text>
        </div>

        {{-- Flywheel --}}
        <div class="grid gap-3 sm:grid-cols-4">
            @foreach ([['Build', 'Connect the work you\'ve already done'], ['Prove', 'AI surfaces achievements and evidence'], ['Get Noticed', 'Recruiters see demonstrated expertise'], ['Get Hired', 'Match with the right opportunities']] as $step)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="text-xs font-bold uppercase tracking-widest text-zinc-900 dark:text-white">{{ $step[0] }}</div>
                    <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $step[1] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-px overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="bg-zinc-50 px-4 py-5 text-center dark:bg-zinc-800">
                <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->stats['developers']) }}</div>
                <div class="mt-0.5 text-xs uppercase tracking-wide text-zinc-500">Developers</div>
            </div>
            <div class="bg-zinc-50 px-4 py-5 text-center dark:bg-zinc-800">
                <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->stats['verified']) }}</div>
                <div class="mt-0.5 text-xs uppercase tracking-wide text-zinc-500">Verified</div>
            </div>
            <div class="bg-zinc-50 px-4 py-5 text-center dark:bg-zinc-800">
                <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->stats['evidence']) }}</div>
                <div class="mt-0.5 text-xs uppercase tracking-wide text-zinc-500">Evidence items</div>
            </div>
        </div>

        {{-- Contact us --}}
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div class="grid content-start gap-3">
                <flux:heading size="lg">Contact us</flux:heading>
                <flux:text>
                    Questions, feedback or partnership ideas? Send us a message and the team will get back to you by email.
                </flux:text>
                <div class="mt-2 grid gap-2">
                    <a href="{{ route('news.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline">
                        <flux:icon name="newspaper" variant="micro" />
                        Read the latest news
                    </a>
                    <a href="{{ route('verified') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline">
                        <flux:icon name="check-badge" variant="micro" />
                        Browse verified developers
                    </a>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                @if ($sent)
                    <div class="grid place-items-center gap-3 py-8 text-center">
                        <span class="flex size-12 items-center justify-center rounded-full bg-emerald-400/10 text-emerald-500">
                            <flux:icon name="check" variant="solid" class="size-6" />
                        </span>
                        <flux:heading size="sm">Message sent</flux:heading>
                        <flux:text>Thanks for reaching out — we will reply to your email soon.</flux:text>
                        <flux:button size="sm" variant="subtle" wire:click="$set('sent', false)">Send another</flux:button>
                    </div>
                @else
                    <form wire:submit="submit" class="grid gap-4">
                        <flux:input wire:model="name" :label="__('Name')" placeholder="Your name" />
                        <flux:input wire:model="email" type="email" :label="__('Email')" placeholder="you@example.com" />

                        <div class="hidden" aria-hidden="true">
                            <flux:input wire:model="website" label="Website" tabindex="-1" autocomplete="off" />
                        </div>

                        <flux:textarea wire:model="message" :label="__('Message')" rows="5" placeholder="How can we help?" />

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <flux:icon name="paper-airplane" variant="micro" />
                            Send message
                        </flux:button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>