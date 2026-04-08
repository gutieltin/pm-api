<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{

    public function before(User $user, $ability)
{
    if ($user->role === 'admin') {
        return true; // System admins can do everything
    }
}
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Workspace $workspace): bool
    {
    // Only the owner OR a workspace admin can add members
    return $user->id === $workspace->owner_id || 
            $workspace->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->role === 'admin'|| $user->id === $workspace->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Workspace $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return false;
    }

    public function createProject(User $user, Workspace $workspace)
{
    // Check if user belongs to this workspace AND is an admin/manager
    return $workspace->users()
        ->where('user_id', $user->id)
        ->whereIn('role', ['admin', 'manager']) // Adjust based on your role column
        ->exists();
}
}
