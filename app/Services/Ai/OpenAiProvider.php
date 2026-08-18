<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Generic OpenAI-compatible chat completions provider. Works with any
 * compatible endpoint (Groq, Google Gemini, OpenRouter, Cerebras, Mistral…)
 * by reading the active provider's base URL, API key and model.
 */
class OpenAiProvider implements AiProvider
{
    public function __construct(private AiSettings $settings) {}

    public function complete(string $system, string $prompt, array $context = []): string
    {
        $config = $this->settings->activeConfig();

        $headers = ['Content-Type' => 'application/json'];

        if (($config['api_key'] ?? '') !== '') {
            $headers['Authorization'] = 'Bearer '.$config['api_key'];
        }

        $response = Http::withHeaders($headers)
            ->timeout(60)
            ->post((string) $config['base_url'], [
                'model' => $config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
            ])
            ->throw()
            ->json();

        return data_get($response, 'choices.0.message.content', '');
    }

    public function structured(string $system, string $prompt, array $context = []): array
    {
        $content = $this->complete($system, $prompt, $context);

        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = Str::position($content, '{');
        $end = Str::position($content, '}');

        if ($start !== null && $end !== null && $end > $start) {
            $candidate = json_decode(Str::substr($content, $start, $end - $start + 1), true);

            if (is_array($candidate)) {
                return $candidate;
            }
        }

        return [];
    }
}
