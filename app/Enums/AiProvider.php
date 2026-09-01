<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum AiProvider: string
{
    use HasLabels;

    case Rules = 'rules';
    case Groq = 'groq';
    case Gemini = 'gemini';
    case OpenRouter = 'openrouter';
    case Cerebras = 'cerebras';
    case Mistral = 'mistral';

    public const LABELS = [
        'rules' => 'Built-in rules engine',
        'groq' => 'Groq',
        'gemini' => 'Google Gemini',
        'openrouter' => 'OpenRouter',
        'cerebras' => 'Cerebras',
        'mistral' => 'Mistral AI',
    ];

    public const ICONS = [
        'rules' => 'cpu-chip',
        'groq' => 'bolt',
        'gemini' => 'sparkles',
        'openrouter' => 'arrow-path',
        'cerebras' => 'server-stack',
        'mistral' => 'cloud',
    ];

    public function icon(): string
    {
        return self::ICONS[$this->value] ?? 'cpu-chip';
    }

    /**
     * Whether this provider needs an API key to function.
     */
    public function requiresKey(): bool
    {
        return $this !== self::Rules;
    }

    /**
     * Whether this is the built-in offline fallback.
     */
    public function isFallback(): bool
    {
        return $this === self::Rules;
    }

    /**
     * Providers that are genuinely free to use without a paid plan.
     *
     * @return array<int, self>
     */
    public static function free(): array
    {
        return [self::Rules, self::Groq, self::Gemini, self::OpenRouter, self::Cerebras, self::Mistral];
    }
}
