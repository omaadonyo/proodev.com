<?php

namespace App\Actions\Projects;

use App\Enums\RecognitionType;
use App\Events\RecognitionReceived;
use App\Models\Project;
use App\Models\ProjectRecognition;
use App\Models\User;

class RecognizeProjectAction
{
    public function handle(User $user, Project $project, RecognitionType $type): ProjectRecognition
    {
        return ProjectRecognition::firstOrCreate([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ], [
            'type' => $type,
        ]);
    }

    public function toggle(User $user, Project $project, RecognitionType $type): array
    {
        $existing = ProjectRecognition::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->type === $type) {
            $existing->delete();

            $project->decrement('recognition_count');

            return ['removed' => true];
        }

        if ($existing) {
            $existing->update(['type' => $type]);
        } else {
            $this->handle($user, $project, $type);
        }

        $project->increment('recognition_count');

        if (! $existing) {
            RecognitionReceived::dispatch($project, $user, $type->value);
        }

        return ['removed' => false];
    }
}
