<?php

namespace App\Support;

/**
 * Curated context for well-known open-source projects.
 *
 * Used to explain WHY a contribution matters — what the project is, which
 * ecosystem it belongs to, and its defensible reach. Reach lines only use
 * widely reported facts; when no reliable figure exists we use qualitative
 * descriptions instead of inventing numbers.
 */
class ProjectContext
{
    /**
     * Known open-source projects keyed by lowercase repository name
     * (and common aliases).
     *
     * @var array<string, array{project: string, ecosystem: string, context: string, reach: string}>
     */
    protected const PROJECTS = [
        'laravel' => [
            'project' => 'Laravel',
            'ecosystem' => 'PHP',
            'context' => 'The PHP web framework used by teams worldwide to ship polished web applications.',
            'reach' => 'Used by 10 Million Developers',
        ],
        'react' => [
            'project' => 'React',
            'ecosystem' => 'JavaScript',
            'context' => 'A JavaScript library for building user interfaces, maintained by Meta and one of the most widely used frontend libraries.',
            'reach' => 'One of the most widely used frontend libraries',
        ],
        'vue' => [
            'project' => 'Vue.js',
            'ecosystem' => 'JavaScript',
            'context' => 'The progressive JavaScript framework for building user interfaces.',
            'reach' => 'Major frontend ecosystem project',
        ],
        'next.js' => [
            'project' => 'Next.js',
            'ecosystem' => 'React',
            'context' => 'The React framework for the web, maintained by Vercel.',
            'reach' => 'Widely used React meta-framework',
        ],
        'filament' => [
            'project' => 'Filament',
            'ecosystem' => 'Laravel',
            'context' => 'A collection of beautiful full-stack components for Laravel — panels, tables and forms.',
            'reach' => 'Major Laravel ecosystem project',
        ],
        'livewire' => [
            'project' => 'Livewire',
            'ecosystem' => 'Laravel',
            'context' => 'A full-stack framework for Laravel that builds dynamic interfaces without leaving the comfort of Blade.',
            'reach' => 'Major Laravel ecosystem project',
        ],
        'tailwindcss' => [
            'project' => 'Tailwind CSS',
            'ecosystem' => 'CSS',
            'context' => 'A utility-first CSS framework for rapidly building custom user interfaces.',
            'reach' => 'Widely used CSS framework',
        ],
        'flutter' => [
            'project' => 'Flutter',
            'ecosystem' => 'Dart',
            'context' => 'Google\'s UI toolkit for building natively compiled applications for mobile, web and desktop from one codebase.',
            'reach' => 'Major cross-platform framework',
        ],
        'kubernetes' => [
            'project' => 'Kubernetes',
            'ecosystem' => 'Cloud Native',
            'context' => 'The open-source system for automating deployment, scaling and management of containerized applications.',
            'reach' => 'The standard in container orchestration',
        ],
        'vscode' => [
            'project' => 'VS Code',
            'ecosystem' => 'Developer Tools',
            'context' => 'Microsoft\'s open-source code editor, one of the most popular development tools.',
            'reach' => 'One of the most popular code editors',
        ],
        'django' => [
            'project' => 'Django',
            'ecosystem' => 'Python',
            'context' => 'The Python web framework for perfectionists with deadlines — batteries included.',
            'reach' => 'Established open-source framework',
        ],
        'postgres' => [
            'project' => 'PostgreSQL',
            'ecosystem' => 'Databases',
            'context' => 'The world\'s most advanced open-source relational database.',
            'reach' => 'Widely used open-source database',
        ],
    ];

    /**
     * Match a repository URL or slug against the knowledge base.
     *
     * @return array{project: string, ecosystem: string, context: string, reach: string}|null
     */
    public static function forRepository(?string $urlOrSlug): ?array
    {
        if (! $urlOrSlug) {
            return null;
        }

        $haystack = strtolower((string) parse_url($urlOrSlug, PHP_URL_PATH) ?: $urlOrSlug);
        $parts = array_values(array_filter(explode('/', trim($haystack, '/'))));
        $name = strtolower((string) end($parts) ?: $haystack);
        $name = str_replace('.git', '', $name);

        foreach ([str_replace('.git', '', $name), $name] as $candidate) {
            if (isset(self::PROJECTS[$candidate])) {
                return self::PROJECTS[$candidate];
            }
        }

        // Substring fallback so variants like "react-router" still match React.
        foreach (self::PROJECTS as $key => $entry) {
            if (str_contains($name, $key)) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * All known projects (for marketing surfaces).
     *
     * @return array<string, array{project: string, ecosystem: string, context: string, reach: string}>
     */
    public static function all(): array
    {
        return self::PROJECTS;
    }
}