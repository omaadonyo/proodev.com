<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(?User $user, Project $project): bool
    {
        return $project->isPublished() || ($user && $project->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->user_id === $user->id || $user->isAdmin();
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->user_id === $user->id || $user->isAdmin();
    }

    public function publish(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function recognize(User $user, Project $project): bool
    {
        return $project->user_id !== $user->id && $project->isPublished();
    }
}
