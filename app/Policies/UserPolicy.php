<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only administrators may view the user index.
     */
    public function viewAny(User $authenticatedUser): bool
    {
        return $authenticatedUser->role === 'admin';
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
