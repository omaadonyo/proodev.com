<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pricing')] class extends Component {
    public bool $annual = false;

    public function planHref(string $side): string
    {
        if ($side === 'enterprise') {
            return route('for-companies');
        }

        if (auth()->check() && auth()->user()->hasIntelligenceAccess()) {
            return route('recruiter.index');
        }

        if (auth()->check() && auth()->user()->role?->isDeveloper()) {
            return route('companies.create');
        }

        return route('register', ['role' => 'company']);
    }

    public function tiers(): array
    {
        $intelligence = [
            'name' => 'Recruiter Intelligence Suite',
            'tagline' => 'The full recruiting engine - for teams and agencies.',
            'price' => '$599',
            'per' => 'first month · then $199/mo',
            'highlight' => true,
            'badge' => 'Most powerful',
            'cta' => 'Get the Intelligence Suite',
            'side' => 'recruiter',
            'features' => config('billing.companies.intelligence.features', []),
        ];

        $recruiter = [
            'name' => 'Recruiter',
            'tagline' => 'Post jobs and recruit from proven engineers.',
            'price' => $this->annual ? '$239' : '$299',
            'per' => '/month'.($this->annual ? ' billed annually' : ''),
            'highlight' => false,
            'cta' => 'Choose Recruiter',
            'side' => 'recruiter',
            'features' => config('billing.companies.recruiter.features', []),
        ];

        $enterprise = [
            'name' => 'Enterprise',
            'tagline' => 'Multi-seat, custom needs, dedicated support.',
            'price' => 'Custom',
            'per' => '',
            'highlight' => false,
            'cta' => 'Contact sales',
            'side' => 'enterprise',
            'features' => [
                'Everything in Recruiter Intelligence Suite',
                'Multi-seat agency pricing',
                'Custom AI / model configuration',
                'Dedicated onboarding & support',
                'SSO & advanced security',
            ],
        ];

        return [$intelligence, $recruiter, $enterprise];
    }

    public function faqs(): array
    {
        return [
            ['q' => 'What does "evidence-backed" mean?', 'a' => 'Every candidate report is derived from analyzed engineering work - repositories, articles, projects, and verified vouches. Scores are explainable factor by factor, and no self-reported claim is taken at face value.'],
            ['q' => 'How is the Intelligence Suite different from Recruiter?', 'a' => 'Recruiters and companies share the same feature set. The Intelligence Suite is a higher-priced tier with the same features: $599 for the first month, then $199/month.'],
            ['q' => 'Can I try it before paying?', 'a' => 'Yes. You can create a company profile and browse the talent pool free. Upgrade when you are ready to recruit at scale.'],
            ['q' => 'What about agencies with multiple seats?', 'a' => 'Contact us through the Enterprise tier for multi-seat agency pricing and shared workspaces.'],
        ];
    }
}
?>

<div class="mx-auto w-full max-w-6xl">
    <div class="grid gap-10">
        <div class="mx-auto max-w-2xl text-center">
            <flux:heading size="xl">Pricing</flux:heading>
            <flux:text class="mt-2">Recruit at scale with evidence-backed intelligence, or drop into the Enterprise tier for custom needs.</flux:text>

            <div class="mx-auto mt-6 inline-flex items-center gap-1 rounded-full border border-zinc-200 p-1 text-sm dark:border-zinc-700">
                <button type="button" wire:click="$set('annual', false)" class="rounded-full px-4 py-1.5 font-medium transition {{ ! $this->annual ? 'bg-accent text-white' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">Monthly</button>
                <button type="button" wire:click="$set('annual', true)" class="rounded-full px-4 py-1.5 font-medium transition {{ $this->annual ? 'bg-accent text-white' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' }}">Annual</button>
            </div>
        </div>

        <div class="grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->tiers() as $tier)
                <div class="flex flex-col rounded-2xl border p-6 {{ $tier['highlight'] ? 'border-accent/50 bg-white shadow-2xl shadow-accent/15 dark:border-accent/40 dark:bg-white/[0.06] lg:-translate-y-2' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' }}">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">{{ $tier['name'] }}</flux:heading>
                        @if (! empty($tier['badge']))
                            <span class="rounded-full bg-accent px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">{{ $tier['badge'] }}</span>
                        @endif
                    </div>
                    <flux:text class="mt-1 text-sm">{{ $tier['tagline'] }}</flux:text>

                    <div class="mt-5 flex items-baseline gap-1">
                        <span class="text-4xl font-bold tabular-nums tracking-tight">{{ $tier['price'] }}</span>
                        <span class="text-sm text-zinc-500">{{ $tier['per'] }}</span>
                    </div>

                    <div class="mt-6 grid gap-2.5">
                        @foreach ($tier['features'] as $feature)
                            <div class="flex items-start gap-2.5 text-sm text-zinc-600 dark:text-zinc-300">
                                <flux:icon name="check-circle" variant="micro" class="mt-0.5 shrink-0 text-emerald-500" />
                                {{ $feature }}
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-auto pt-7">
                        <flux:button :href="$this->planHref($tier['side'])" variant="{{ $tier['highlight'] ? 'primary' : 'outline' }}" class="w-full justify-center">
                            {{ $tier['cta'] }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mx-auto w-full max-w-3xl">
            <flux:heading size="lg" class="text-center">Frequently asked questions</flux:heading>
            <div class="mt-6 grid gap-3">
                @foreach ($this->faqs() as $faq)
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="font-semibold">{{ $faq['q'] }}</div>
                        <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $faq['a'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
