<?php

namespace App\Services\Ai;

use App\Enums\AiProvider;
use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Reads and writes per-provider AI settings. Admin-stored values take
 * precedence over the static defaults in config/ai.php.
 */
class AiSettings
{
    /**
     * Get the merged runtime config for a provider (DB overrides + defaults).
     *
     * @return array<string, mixed>
     */
    public function for(AiProvider $provider): array
    {
        $defaults = (array) config("ai.providers.{$provider->value}", []);
        $row = $this->row($provider);

        return array_merge($defaults, $row?->settings ?? []);
    }

    /**
     * The merged runtime config for the currently active provider.
     *
     * @return array<string, mixed>
     */
    public function activeConfig(): array
    {
        return $this->for($this->active());
    }

    public function isEnabled(AiProvider $provider): bool
    {
        return (bool) ($this->row($provider)?->enabled ?? true);
    }

    /**
     * The provider currently selected for AI analysis.
     */
    public function active(): AiProvider
    {
        $cached = Cache::remember('ai.active', 30, function () {
            return AiProviderSetting::where('active', true)->value('provider');
        });

        return $cached ?? AiProvider::tryFrom((string) config('ai.driver', 'rules')) ?? AiProvider::Rules;
    }

    public function setActive(AiProvider $provider): void
    {
        AiProviderSetting::query()->update(['active' => false]);

        $row = AiProviderSetting::firstOrNew(['provider' => $provider->value]);
        $row->active = true;
        $row->save();

        Cache::forget('ai.active');
    }

    public function update(AiProvider $provider, array $settings): AiProviderSetting
    {
        $row = AiProviderSetting::firstOrNew(['provider' => $provider->value]);
        $row->enabled = (bool) ($settings['enabled'] ?? true);
        $row->settings = array_merge($row->settings ?? [], $settings);
        $row->save();

        Cache::forget('ai.active');

        return $row;
    }

    /**
     * Merged rows for every provider, including stored settings and state.
     *
     * @return array<int, array{provider: AiProvider, enabled: bool, active: bool, settings: array<string, mixed>}>
     */
    public function all(): array
    {
        return collect(AiProvider::cases())->map(function (AiProvider $provider) {
            return [
                'provider' => $provider,
                'enabled' => $this->isEnabled($provider),
                'active' => $this->active() === $provider,
                'settings' => $this->for($provider),
            ];
        })->values()->all();
    }

    private function row(AiProvider $provider): ?AiProviderSetting
    {
        return AiProviderSetting::where('provider', $provider->value)->first();
    }
}
