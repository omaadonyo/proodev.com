<?php

namespace App\Services;

use App\Services\Ai\AiService;
use Illuminate\Support\Str;

class ProfileBioService
{
    public function __construct(private AiService $ai) {}

    /**
     * Generate a professional bio from the extracted profile facts.
     *
     * @param  array<string, mixed>  $profile
     */
    public function generate(array $profile): string
    {
        if ($this->ai->available()) {
            $bio = trim((string) $this->ai->professionalBio($this->facts($profile)));

            if ($bio !== '' && $bio !== '…') {
                return $this->clean($bio);
            }
        }

        return $this->template($profile);
    }

    /**
     * Build a fact sheet for the AI provider.
     *
     * @param  array<string, mixed>  $profile
     */
    private function facts(array $profile): string
    {
        $lines = [
            'Name: '.($profile['name'] ?? 'Unknown'),
            'Headline: '.($profile['headline'] ?? 'Not provided'),
            'Location: '.($profile['location'] ?? 'Not provided'),
            'Languages: '.implode(', ', array_slice($profile['languages'] ?? [], 0, 5)),
            'Public repositories: '.($profile['public_repos'] ?? 0),
            'GitHub stars: '.number_format($profile['total_stars'] ?? 0),
            'Followers: '.number_format($profile['followers'] ?? 0),
            'Achievements: '.implode(', ', $profile['achievements'] ?? []),
        ];

        if (! empty($profile['bio'])) {
            $lines[] = 'Existing bio: '.$profile['bio'];
        }

        return implode("\n", $lines);
    }

    /**
     * Deterministic, factual template used when AI is unavailable.
     *
     * @param  array<string, mixed>  $profile
     */
    private function template(array $profile): string
    {
        $parts = [];

        $languages = array_slice($profile['languages'] ?? [], 0, 3);

        if (! empty($profile['headline'])) {
            $parts[] = (string) $profile['headline'];
        } elseif ($languages !== []) {
            $parts[] = 'Software engineer specializing in '.implode(', ', $languages).'.';
        } else {
            $parts[] = 'Software engineer building real-world products.';
        }

        if (($profile['public_repos'] ?? 0) > 0) {
            $repos = "{$profile['public_repos']} public repositories";
            if (($profile['total_stars'] ?? 0) > 0) {
                $repos .= ' and '.number_format((int) $profile['total_stars']).' GitHub stars';
            }
            $parts[] = $repos.'.';
        }

        if (($profile['followers'] ?? 0) > 0) {
            $parts[] = 'Followed by '.number_format((int) $profile['followers']).' developers on GitHub.';
        }

        if (! empty($profile['location'])) {
            $parts[] = 'Based in '.$profile['location'].'.';
        }

        $bio = implode(' ', $parts);

        return Str::endsWith($bio, '.') ? $bio : $bio.'.';
    }

    private function clean(string $bio): string
    {
        $bio = trim($bio, " \n\r\t\"'`");

        return $bio;
    }
}
