<?php

namespace App\Actions\Projects;

use App\Data\ProjectData;
use App\Enums\ProjectStatus;
use App\Enums\TimelineEventType;
use App\Events\ProjectPublished;
use App\Jobs\GenerateProjectSummary;
use App\Models\Project;
use App\Models\User;
use App\Services\TimelineService;

class SaveProjectAction
{
    public function __construct(private TimelineService $timeline) {}

    public function handle(User $user, ProjectData $data, ?Project $project = null): Project
    {
        $project ??= new Project(['user_id' => $user->id]);

        $project->fill($data->persist());
        $project->save();

        return $project;
    }

    public function publish(Project $project): Project
    {
        if ($project->isPublished()) {
            return $project;
        }

        $project->update([
            'status' => ProjectStatus::Published,
            'published_at' => now(),
        ]);

        $this->timeline->record(
            $project->user,
            TimelineEventType::ProjectPublished,
            "Published project: {$project->title}",
            $project->tagline,
            [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'title' => $project->title,
                'tagline' => $project->tagline,
            ],
            $project,
        );

        dispatch(new GenerateProjectSummary($project));

        ProjectPublished::dispatch($project);

        return $project;
    }
}
