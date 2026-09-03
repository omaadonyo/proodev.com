<?php

use App\Enums\AiProvider;
use App\Services\Ai\AiSettings;
use Flux\Flux;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('AI Models')] class extends Component {
    public ?string $editing = null;
    public array $form = [];
    public ?string $testResult = null;
    public ?string $testMessage = null;

    public function mount(): void
    {
        $this->editing = app(AiSettings::class)->active()->value;
        $this->loadForm($this->editing);
    }

    public function setActive(string $provider): void
    {
        $settings = app(AiSettings::class);
        $providerEnum = AiProvider::tryFrom($provider);

        if (! $providerEnum || $providerEnum->isFallback()) {
            return;
        }

        $config = $settings->for($providerEnum);

        if (empty($config['api_key'])) {
            Flux::toast(variant: 'warning', text: 'Add an API key for this provider before activating it.');

            return;
        }

        $settings->setActive($providerEnum);

        unset($this->rows);

        Flux::toast(variant: 'success', text: 'AI model switched to '.$providerEnum->label().'.');
    }

    public function edit(string $provider): void
    {
        $this->editing = $provider;
        $this->loadForm($provider);
        $this->testResult = null;
        $this->testMessage = null;
    }

    public function save(): void
    {
        $provider = AiProvider::tryFrom((string) $this->editing);

        if (! $provider) {
            return;
        }

        $settings = app(AiSettings::class);

        if ($provider->isFallback()) {
            $settings->update($provider, ['enabled' => true]);

            unset($this->rows);

            Flux::toast(variant: 'success', text: 'The built-in rules engine is always available.');

            return;
        }

        $rules = [
            'form.model' => ['required', 'string', 'max:120'],
            'form.api_key' => ['nullable', 'string', 'max:255'],
        ];

        if (! empty($this->form['base_url'])) {
            $rules['form.base_url'] = ['string', 'url', 'max:255'];
        }

        $validated = $this->validate($rules);

        $settings = app(AiSettings::class);
        $existing = $settings->for($provider);

        $payload = [
            'enabled' => true,
            'model' => $validated['form']['model'],
            'base_url' => $validated['form']['base_url'] ?: $existing['base_url'],
        ];

        if (! empty($validated['form']['api_key'])) {
            $payload['api_key'] = $validated['form']['api_key'];
        } elseif (array_key_exists('api_key', $existing)) {
            $payload['api_key'] = $existing['api_key'];
        }

        $settings->update($provider, $payload);

        unset($this->rows);

        Flux::toast(variant: 'success', text: $provider->label().' settings saved.');
    }

    public function testConnection(): void
    {        $provider = AiProvider::tryFrom((string) $this->editing);

        if (! $provider || $provider->isFallback()) {
            $this->testResult = 'ok';
            $this->testMessage = 'The built-in rules engine runs offline, no key needed.';

            return;
        }

        $settings = app(AiSettings::class);
        $config = $settings->for($provider);

        $apiKey = $this->form['api_key'] ?: $config['api_key'];
        $baseUrl = $this->form['base_url'] ?: $config['base_url'];

        if (empty($apiKey) || empty($baseUrl)) {
            $this->testResult = 'error';
            $this->testMessage = 'Save an API key and endpoint first.';

            return;
        }

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->timeout(30)
                ->post($baseUrl, [
                    'model' => $this->form['model'],
                    'messages' => [
                        ['role' => 'user', 'content' => 'Reply with the single word: OK'],
                    ],
                    'max_tokens' => 8,
                ])
                ->throw();

            $content = data_get($response->json(), 'choices.0.message.content', '');

            $this->testResult = 'ok';
            $this->testMessage = 'Connected. Model replied: '.trim($content);
        } catch (\Throwable $e) {
            $this->testResult = 'error';
            $this->testMessage = 'Connection failed: '.$e->getMessage();
        }
    }

    #[Computed]
    public function rows()
    {
        return app(AiSettings::class)->all();
    }

    #[Computed]
    public function activeConfig(): array
    {
        return app(AiSettings::class)->activeConfig();
    }

    #[Computed]
    public function editingConfig(): array
    {
        return app(AiSettings::class)->for(AiProvider::tryFrom((string) $this->editing) ?? AiProvider::Rules);
    }

    private function loadForm(string $provider): void
    {
        $config = app(AiSettings::class)->for(AiProvider::tryFrom($provider) ?? AiProvider::Rules);

        $this->form = [
            'model' => (string) ($config['model'] ?? ''),
            'api_key' => '',
            'base_url' => (string) ($config['base_url'] ?? ''),
        ];
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">AI models</flux:heading>
        <flux:text>Pick a free AI model that scouts and reads page content, then add your API key. Changes apply immediately.</flux:text>
    </div>

        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                <flux:icon name="sparkles" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold">
                    Currently active: {{ app(AiSettings::class)->active()->label() }}
                </div>
                <div class="text-xs text-zinc-500">
                    @if ($this->activeConfig['model'] ?? null)
                        Model: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $this->activeConfig['model'] }}</span>
                    @else
                        Using the deterministic offline engine, no API key required.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div>
        <flux:heading size="sm">Available providers</flux:heading>
        <flux:text>All listed providers are free-tier OpenAI-compatible endpoints. Activate one to use it for scouting and analysis.</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->rows as $row)
            <div
                wire:key="provider-{{ $row['provider']->value }}"
                wire:click="edit('{{ $row['provider']->value }}')"
                role="button"
                tabindex="0"
                @class([
                    'cursor-pointer rounded-lg border p-3 transition',
                    'border-accent bg-accent/5 ring-1 ring-accent' => $row['active'],
                    'bg-zinc-100 hover:border-accent dark:bg-white/5' => ! $row['active'],
                ])
            >
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                        <flux:icon :name="$row['provider']->icon()" variant="solid" class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ $row['provider']->label() }}</span>
                            @if ($row['active'])
                                <flux:badge size="sm" color="emerald" inset="top bottom">Active</flux:badge>
                            @elseif ($row['settings']['api_key'] ?? null)
                                <flux:badge size="sm" color="zinc" inset="top bottom">Key set</flux:badge>
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-zinc-500">
                            @if ($row['provider']->isFallback())
                                Offline, deterministic, free, no key needed.
                            @else
                                Free tier · {{ $row['settings']['model'] ?? '-' }}
                            @endif
                        </div>
                    </div>
                    @if (! $row['provider']->isFallback() && $row['enabled'])
                        <flux:button size="sm" variant="subtle" wire:click.stop="setActive('{{ $row['provider']->value }}')">
                            Activate
                        </flux:button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($this->editing)
        @php($editingProvider = \App\Enums\AiProvider::tryFrom($this->editing))
    <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">Configure {{ $editingProvider?->label() }}</flux:heading>
                    <flux:text class="mt-1">
                        @if ($editingProvider?->isFallback())
                            The rules engine analyzes content locally. It always works and never requires a key.
                        @else
                            Grab a free API key from the provider dashboard, paste it below, and pick a model.
                        @endif
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:button variant="subtle" wire:click="testConnection">
                        <flux:icon name="beaker" variant="micro" />
                        Test connection
                    </flux:button>
                </div>
            </div>

            @if ($this->testResult)
                <div @class([
                    'mt-4 rounded-lg border p-3 text-sm',
                    'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300' => $this->testResult === 'ok',
                    'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300' => $this->testResult === 'error',
                ])>
                    {{ $this->testMessage }}
                </div>
            @endif

            <form wire:submit="save" class="mt-4 grid gap-4">
                @if (! $editingProvider?->isFallback())
                    <flux:field>
                        <flux:label>API key</flux:label>
                        <flux:input type="password" wire:model="form.api_key" placeholder="{{ ($this->editingConfig['api_key'] ?? '') ? '•••••••• (leave blank to keep existing)' : 'sk-…' }}" autocomplete="off" />
                        <flux:error name="form.api_key" />
                    </flux:field>
                @endif

                @if (isset($this->form['base_url']))
                    <flux:field>
                        <flux:label>Endpoint URL</flux:label>
                        <flux:input wire:model="form.base_url" />
                        <flux:error name="form.base_url" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Model</flux:label>
                    @if (($this->editingConfig['models'] ?? []) !== [])
                        <x-searchable-select wire:model="form.model">
                            @foreach ($this->editingConfig['models'] as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </x-searchable-select>
                    @else
                        <flux:input wire:model="form.model" />
                    @endif
                    <flux:error name="form.model" />
                </flux:field>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="submit" variant="primary">Save settings</flux:button>
                    @if (! $editingProvider?->isFallback() && app(AiSettings::class)->active()->value !== $this->editing)
                        <flux:button variant="subtle" wire:click="setActive('{{ $this->editing }}')">Save &amp; activate</flux:button>
                    @endif
                </div>
            </form>
        </div>
    @endif
</div>
