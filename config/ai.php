<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | The active driver is resolved at runtime from the ai_provider_settings
    | table (managed by admins). Static values here act as defaults for local
    | development and fall back gracefully when no key is configured.
    |
    */

    'driver' => env('AI_DRIVER', 'rules'),

    /*
    |--------------------------------------------------------------------------
    | Provider registry
    |--------------------------------------------------------------------------
    |
    | Each entry is an OpenAI-compatible chat completions endpoint. Defaults
    | stored here are overridden by per-provider rows in the database.
    |
    */

    'providers' => [

        'rules' => [
            'label' => 'Built-in rules engine',
            'requires_key' => false,
            'free' => true,
            'base_url' => null,
            'model' => null,
        ],

        'groq' => [
            'label' => 'Groq',
            'requires_key' => true,
            'free' => true,
            'api_key' => env('GROQ_API_KEY'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1/chat/completions'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'models' => [
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B',
                'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant',
            ],
        ],

        'gemini' => [
            'label' => 'Google Gemini',
            'requires_key' => true,
            'free' => true,
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'models' => [
                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite',
                'gemini-2.5-pro' => 'Gemini 2.5 Pro',
            ],
        ],

        'openrouter' => [
            'label' => 'OpenRouter (free models)',
            'requires_key' => true,
            'free' => true,
            'api_key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1/chat/completions'),
            'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
            'models' => [
                'meta-llama/llama-3.3-70b-instruct:free' => 'Llama 3.3 70B (free)',
                'deepseek/deepseek-r1:free' => 'DeepSeek R1 (free)',
                'qwen/qwen3:free' => 'Qwen 3 (free)',
                'openrouter/free' => 'Auto pick free model',
            ],
        ],

        'cerebras' => [
            'label' => 'Cerebras',
            'requires_key' => true,
            'free' => true,
            'api_key' => env('CEREBRAS_API_KEY'),
            'base_url' => env('CEREBRAS_BASE_URL', 'https://api.cerebras.ai/v1/chat/completions'),
            'model' => env('CEREBRAS_MODEL', 'llama-3.3-70b'),
            'models' => [
                'llama-3.3-70b' => 'Llama 3.3 70B',
                'llama-3.1-8b' => 'Llama 3.1 8B',
            ],
        ],

        'mistral' => [
            'label' => 'Mistral AI',
            'requires_key' => true,
            'free' => true,
            'api_key' => env('MISTRAL_API_KEY'),
            'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1/chat/completions'),
            'model' => env('MISTRAL_MODEL', 'mistral-small-latest'),
            'models' => [
                'mistral-small-latest' => 'Mistral Small',
                'open-mistral-nemo' => 'Open Mistral Nemo',
                'codestral-latest' => 'Codestral',
            ],
        ],
    ],
];
