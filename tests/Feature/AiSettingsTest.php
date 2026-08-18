<?php

use App\Enums\AiProvider;
use App\Models\User;
use App\Services\Ai\AiService;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiProvider as AiProviderContract;
use App\Services\Ai\OpenAiProvider;
use App\Services\Ai\RuleBasedFallbackProvider;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('the default provider is the built-in rules engine', function () {
    expect(app(AiSettings::class)->active())->toBe(AiProvider::Rules);
});

test('every listed provider is genuinely free tier', function () {
    $providers = collect(AiProvider::free())
        ->map(fn (AiProvider $provider) => $provider->value);

    expect($providers)->toContain('rules', 'groq', 'gemini', 'openrouter', 'cerebras', 'mistral');
});

test('the AiService falls back when no API key is configured', function () {
    expect(app(AiProviderContract::class))->toBeInstanceOf(RuleBasedFallbackProvider::class);
});

test('activating a provider without a key is rejected', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ai')
        ->call('setActive', AiProvider::Groq->value)
        ->assertHasNoErrors();

    expect(app(AiSettings::class)->active())->toBe(AiProvider::Rules);
});

test('an admin can save a provider key and activate it', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ai')
        ->set('editing', AiProvider::Groq->value)
        ->set('form.api_key', 'gsk_test_123')
        ->set('form.model', 'llama-3.3-70b-versatile')
        ->set('form.base_url', 'https://api.groq.com/openai/v1/chat/completions')
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(AiSettings::class);

    expect($settings->for(AiProvider::Groq)['api_key'])->toBe('gsk_test_123');

    Livewire::actingAs($admin)
        ->test('pages::admin.ai')
        ->call('setActive', AiProvider::Groq->value)
        ->assertHasNoErrors();

    expect($settings->active())->toBe(AiProvider::Groq)
        ->and(app(AiProviderContract::class))->toBeInstanceOf(OpenAiProvider::class);
});

test('the active provider config drives the OpenAI-compatible provider', function () {
    app(AiSettings::class)->update(AiProvider::Groq, [
        'enabled' => true,
        'api_key' => 'gsk_test_123',
        'base_url' => 'https://api.groq.com/openai/v1/chat/completions',
        'model' => 'llama-3.3-70b-versatile',
    ]);
    app(AiSettings::class)->setActive(AiProvider::Groq);

    Http::fake([
        'api.groq.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"summary":"test"}']],
            ],
        ]),
    ]);

    $result = app(AiService::class)->summarize('Some project content');

    expect($result)->toBe('{"summary":"test"}');
});

test('the admin AI page renders the provider list', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ai')
        ->assertOk()
        ->assertSee('AI models')
        ->assertSee('Built-in rules engine')
        ->assertSee('Groq')
        ->assertSee('Google Gemini')
        ->assertSee('OpenRouter')
        ->assertSee('Cerebras')
        ->assertSee('Mistral');
});

test('testing the connection reports success against a fake endpoint', function () {
    Http::fake([
        'api.groq.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'OK']],
            ],
        ]),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ai')
        ->set('editing', AiProvider::Groq->value)
        ->set('form.api_key', 'gsk_test_123')
        ->set('form.model', 'llama-3.3-70b-versatile')
        ->set('form.base_url', 'https://api.groq.com/openai/v1/chat/completions')
        ->call('testConnection')
        ->assertSet('testResult', 'ok')
        ->assertSee('Connected');
});

test('testing the connection reports failures', function () {
    Http::fake([
        'api.groq.com/*' => Http::response([], 401),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ai')
        ->set('editing', AiProvider::Groq->value)
        ->set('form.api_key', 'gsk_test_bad')
        ->set('form.model', 'llama-3.3-70b-versatile')
        ->set('form.base_url', 'https://api.groq.com/openai/v1/chat/completions')
        ->call('testConnection')
        ->assertSet('testResult', 'error')
        ->assertSee('Connection failed');
});
