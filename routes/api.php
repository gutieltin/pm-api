<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Apply the 'login' rate limiter here (e.g., max 5 tries per minute)
Route::middleware('throttle:login')->group(function () {
    Route::post('/v1/login', [AuthController::class, 'login']);
});

/*
| Protected Routes (Requires Sanctum Token)
*/

// Apply the 'api' rate limiter to everything else (e.g., 60 requests per minute)
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {

    // Workspace Routes
    Route::get('/workspaces/trashed', [WorkspaceController::class, 'trashed']);
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::get('/workspaces/{workspace}/members', [WorkspaceController::class, 'members']);
    Route::post('/workspaces/{workspace}/members', [WorkspaceController::class, 'addMember']);
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy']);
    Route::post('/workspaces/{id}/restore', [WorkspaceController::class, 'restore']);
    Route::delete('/workspaces/{id}/force', [WorkspaceController::class, 'forceDelete']);

    // Project Routes
    Route::prefix('workspaces/{workspace}')->group(function () {
        Route::get('/projects/trashed', [ProjectController::class, 'trashed']);      // ← FIRST
        Route::post('/projects/{id}/restore', [ProjectController::class, 'restore']); // ← SECOND
        Route::delete('/projects/{id}/force', [ProjectController::class, 'forceDelete']); // ← THIRD
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store'])
            ->middleware('can:create projects');
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])
            ->middleware('can:update,project');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
            ->middleware('can:delete,project');
    });

    // Task Routes
    Route::get('/tasks/trashed', [TaskController::class, 'trashed']);
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks/{id}/restore', [TaskController::class, 'restore']);
    Route::delete('/tasks/{id}/force', [TaskController::class, 'forceDelete']);

    // Route::patch('/tasks/{task}', [TaskController::class, 'update'])->whereNumber('task');
    // Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->whereNumber('task');
    // Route::get('/tasks/{task}', [TaskController::class, 'show'])->whereNumber('task');

    // Comments routes
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);
    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);

    // User Profile Routes
    Route::get('/me', function () {
        return auth()->user()->load('roles', 'workspaces');
    });
    // Password update route
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    // Admin-only user listing and deletion
    Route::get('/users', [AuthController::class, 'index']);
    Route::delete('/users/{user}', [AuthController::class, 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::post('v1/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('v1/reset-password', [AuthController::class, 'resetPassword']);


// TEMPORARY - Force upgrade existing user to Admin
Route::get('/upgrade-to-admin', function () {
    $user = \App\Models\User::where('email', 'admin@projectflow.com')->first();
    
    if (!$user) {
        return response()->json(['message' => 'User not found!']);
    }

    // 1. Force the global Spatie permission role
    $user->syncRoles(['admin']);

    // 2. Find their workspace and force the pivot table role
    $workspace = \App\Models\Workspace::where('owner_id', $user->id)->first();
    
    if ($workspace) {
        if ($workspace->users->contains($user->id)) {
            // Update the existing connection
            $workspace->users()->updateExistingPivot($user->id, ['role' => 'admin']);
        } else {
            // Create the connection if it somehow doesn't exist
            $workspace->users()->attach($user->id, ['role' => 'admin']);
        }
    }

    return response()->json([
        'message' => 'Success! You are now a full Admin.',
        'email' => $user->email
    ]);
});