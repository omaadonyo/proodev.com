<?php

namespace App\Services\Ai\Contracts;

interface AiProvider
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function complete(string $system, string $prompt, array $context = []): string;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function structured(string $system, string $prompt, array $context = []): array;
}
