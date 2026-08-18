<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\Ai\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateProjectSummary implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public Project $project) {}

    public function handle(AiService $ai): void
    {
        if (! $ai->available()) {
            return;
        }

        $content = collect([
            $this->project->title,
            $this->project->tagline,
            'PROBLEM: '.$this->project->problem,
            'SOLUTION: '.$this->project->solution,
            'ARCHITECTURE: '.($this->project->architecture ?? ''),
            'LESSONS: '.($this->project->lessons_learned ?? ''),
        ])->filter()->implode("\n\n");

        $summary = $ai->summarize($content);

        if ($summary !== '') {
            $this->project->update(['ai_summary' => $summary]);
        }
    }
}
