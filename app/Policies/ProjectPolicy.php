<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->workspaces->contains($project->workspace_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, $workspace): bool
    {
        return $user->workspaces()->where('workspace_id', $workspace->id)->whereIn('role', ['admin','manager'])->exists();
    }

    /**
     * Determine whether the user can update the model.(only workspace admin or project leader can update it)
     */
    public function update(User $user, Project $project): bool
    {
        return $user->role === 'admin' || $user->id === $project->workspace->owner_id;
    }

    

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
    return $user->role === 'admin' || $user->id === $project->workspace->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
