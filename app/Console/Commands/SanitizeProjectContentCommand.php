<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Support\Markdown;
use Illuminate\Console\Command;

class SanitizeProjectContentCommand extends Command
{
    protected $signature = 'projects:sanitize-content
        {--dry-run : Report what would change without writing to the database}
        {--limit=0 : Only process the first N projects (0 = all)}';

    protected $description = 'Convert raw HTML markup stored in project content fields to clean text';

    /**
     * Fields that used to receive raw repository README markup from the
     * rule-based project draft.
     */
    private const FIELDS = ['problem', 'solution', 'architecture', 'lessons_learned', 'tagline'];

    public function handle(): int
    {
        $query = Project::query()->latest('id');

        if ((int) $this->option('limit') > 0) {
            $query->limit((int) $this->option('limit'));
        }

        $dryRun = (bool) $this->option('dry-run');
        $scanned = 0;
        $changed = 0;
        $fieldsChanged = 0;

        foreach ($query->get() as $project) {
            $scanned++;
            $updates = [];

            foreach (self::FIELDS as $field) {
                $raw = $project->{$field};

                if (! is_string($raw) || trim($raw) === '' || ! $this->containsHtml($raw)) {
                    continue;
                }

                $clean = Markdown::plain($raw);

                if ($clean !== $raw) {
                    $updates[$field] = $clean;
                }
            }

            if ($updates === []) {
                continue;
            }

            $changed++;
            $fieldsChanged += count($updates);

            if ($dryRun) {
                $this->line("  [dry-run] #{$project->id} {$project->title}: ".implode(', ', array_keys($updates)));
            } else {
                Project::withoutTimestamps(fn () => $project->update($updates));
            }
        }

        if ($dryRun) {
            $this->info("Dry run complete — {$changed} of {$scanned} projects would have {$fieldsChanged} field(s) sanitized.");
        } else {
            $this->info("Sanitized {$fieldsChanged} field(s) across {$changed} of {$scanned} projects.");
        }

        return self::SUCCESS;
    }

    private function containsHtml(string $value): bool
    {
        return preg_match('/<[a-zA-Z!\/]/', $value) === 1;
    }
}
