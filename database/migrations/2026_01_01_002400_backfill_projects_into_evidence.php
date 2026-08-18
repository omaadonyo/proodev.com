<?php

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Project::query()
            ->orderBy('id')
            ->chunkById(100, function ($projects) {
                foreach ($projects as $project) {
                    $url = $project->repository_url ?: $project->demo_url;

                    if (! $url) {
                        continue;
                    }

                    $exists = Evidence::where('url', $url)->where('user_id', $project->user_id)->exists();

                    if ($exists) {
                        continue;
                    }

                    Evidence::create([
                        'user_id' => $project->user_id,
                        'type' => EvidenceType::Project,
                        'title' => $project->title,
                        'url' => $url,
                        'source' => $url ? $this->sourceOf($url) : 'project',
                        'description' => $project->tagline,
                        'status' => EvidenceStatus::Ready,
                        'metadata' => [
                            'project_id' => $project->id,
                            'tech_stack' => $project->tech_stack,
                            'engineering_decisions' => $project->engineering_decisions,
                            'demo_url' => $project->demo_url,
                            'repository_url' => $project->repository_url,
                            'verification_status' => $project->verification_status?->value,
                        ],
                        'ai_score' => $project->ai_score,
                        'project_id' => $project->id,
                        'analyzed_at' => $project->published_at ?: now(),
                        'created_at' => $project->created_at ?? Carbon::now(),
                        'updated_at' => $project->updated_at ?? Carbon::now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Evidence::whereNotNull('project_id')->delete();
    }

    private function sourceOf(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            str_contains($host, 'github.') => 'github',
            str_contains($host, 'gitlab.') => 'gitlab',
            str_contains($host, 'bitbucket.') => 'bitbucket',
            default => 'web',
        };
    }
};
