<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

// Only allow users who belong to the workspace of this project to listen
Broadcast::channel('project.{projectId}', function (User $user, int $projectId) {
    // Check if the user is a member of the project's workspace
    return $user->projects()->where('id', $projectId)->exists();
});
