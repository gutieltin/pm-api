<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Users can view other users in workspaces they belong to.
     * Administrators may view all users.
     */
    public function viewAny(User $authenticatedUser): bool
    {
        // Admins can see all users
        if ($authenticatedUser->role === 'admin') {
            return true;
        }

        // Other users can see users in their workspaces
        return $authenticatedUser->workspaces()->exists();
    }

    /**
     * Create a new policy instance.
     */
    public function delete(User $authenticatedUser, User $userToDelete): bool
    {
        // 1. Only admins can delete users
        // 2. Prevent the admin from accidentally deleting themselves!
        return $authenticatedUser->role === 'admin' && $authenticatedUser->id !== $userToDelete->id;
    }
}
