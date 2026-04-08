<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\CommentController;


// Apply the 'login' rate limiter here (e.g., max 5 tries per minute)
Route::middleware('throttle:login')->group(function () {
    Route::post('/v1/login', [AuthController::class, 'login']); // Changed to POST as login should be POST
});

/*
| Protected Routes (Requires Sanctum Token)
*/

// Apply the 'api' rate limiter to everything else (e.g., 60 requests per minute)
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {

    // Workspace Routes
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::post('/workspaces/{workspace}/members', [WorkspaceController::class, 'addMember']);
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy']);
    Route::get('/workspaces/trashed', [WorkspaceController::class, 'trashed']);
    Route::post('/workspaces/{id}/restore', [WorkspaceController::class, 'restore']);
    
    // Project Routes
    Route::prefix('workspaces/{workspace}')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store'])
                ->middleware('can:create projects'); 
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])
                ->middleware('can:update,project'); 
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
                ->middleware('can:delete,project');
        Route::get('/projects/trashed', [ProjectController::class, 'trashed']);
        Route::post('/projects/{id}/restore', [ProjectController::class, 'restore']);
    });

    // Task Routes 
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::get('/tasks/trashed', [TaskController::class, 'trashed']);
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

    // Admin-only user listing and deletion
    Route::get('/users', [AuthController::class, 'index']);
    Route::delete('/users/{user}', [AuthController::class, 'destroy']);
    
    Route::post('/logout', [AuthController::class, 'logout']);
});