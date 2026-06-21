<?php

namespace App\Http\Controllers\Api;

use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Mail\TaskAssignedMail;
use App\Mail\TaskStatusChangedMail;
use Illuminate\Support\Facades\Mail;

class TaskController extends Controller
{
public function index(Request $request, Project $project)
{
    $user = $request->user();

    // Start the query and eager load relationships
    $query = $project->tasks()->with(['assignee', 'creator']);

    // Determine if the user is an admin or the workspace owner
    $isAdmin = $user->hasRole('admin') || 
        $user->role === 'admin' || 
        $user->id === $project->workspace->owner_id;

    // If they are a standard employee, only fetch tasks assigned to them
    if (! $isAdmin) {
        $query->where('assignee_id', $user->id);
    }

    $tasks = $query->paginate(20);

    return TaskResource::collection($tasks);
}

    /**
     * Create and Assign Task (Admin/Manager Only).
     */
    public function store(Request $request, Project $project)
    {
        // Only someone who can 'update' a project (Admin) can add tasks to it
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assignee_id' => 'required|exists:users,id', // Must assign to someone
            'priority' => 'required|in:low,medium,high',
            'due_at' => 'nullable|date',
        ]);

        $task = $project->tasks()->create([
            'title' => $validated['title'],
            'description' => $request['description'] ?? null,
            'creator_id' => Auth::id(),
            'assignee_id' => $validated['assignee_id'],
            'priority' => $validated['priority'],
            'status' => 'pending',
            'due_at' => $validated['due_at'] ?? null,
        ]);

        TaskCreated::dispatch($task);
        if ($task->assignee_id) {
    $assignee = \App\Models\User::find($task->assignee_id);
    if ($assignee) {
        Mail::to($assignee->email)->send(new TaskAssignedMail(
            assignee: $assignee,
            task: $task->load('project'),
        ));
    }
}

        return new TaskResource($task);
        // Send email if task has an assignee

    }

    /**
     * Update Task (Adaptive Permissions).
     */
    public function update(Request $request, Task $task)
    {
        // Policy will check:
        // 1. If Admin: Can change everything.
        // 2. If Assignee: Can ONLY change 'status'.
        Gate::authorize('update', $task);

        $user = Auth::user();
        $isAdmin = $user->hasRole('admin') || $user->id === $task->project->workspace->owner_id;

        if ($isAdmin) {
            // Admins can change everything
            $validated = $request->validate([
                'title' => 'sometimes|string',
                'status' => 'sometimes|in:todo,pending,in_progress,review,done',
                'current_status' => 'sometimes|in:todo,pending,in_progress,review,done',
                'assignee_id' => 'sometimes|exists:users,id',
                'priority' => 'sometimes|in:low,medium,high',
            ]);

            // Handle both status and current_status fields
            if (isset($validated['current_status'])) {
                $validated['status'] = $validated['current_status'];
                unset($validated['current_status']);
            }
        } else {
            // Employees (Assignees) can ONLY change the status
            $validated = $request->validate([
                'status' => 'sometimes|in:todo,pending,in_progress,review,done',
                'current_status' => 'sometimes|in:todo,pending,in_progress,review,done',
            ]);

            // Require at least one status field
            if (! isset($validated['status']) && ! isset($validated['current_status'])) {
                return response()->json([
                    'message' => 'The status field is required.',
                    'errors' => ['status' => 'The status field is required.'],
                ], 422);
            }

            // Map current_status to status for database
            if (isset($validated['current_status'])) {
                $validated['status'] = $validated['current_status'];
                unset($validated['current_status']);
            }

            // Only extract status field, ignore any other fields sent by the frontend
            $validated = ['status' => $validated['status']];
        }

        $task->update($validated);

        TaskUpdated::dispatch($task->refresh());
                // Send email if status changed
        $oldStatus = $task->current_status;

$task->update($validated);

// Send status change email if status changed and task has assignee
if (
    isset($validated['current_status']) &&
    $oldStatus !== $validated['current_status'] &&
    $task->assignee_id
) {
    $assignee = \App\Models\User::find($task->assignee_id);
    if ($assignee) {
        Mail::to($assignee->email)->send(new TaskStatusChangedMail(
            assignee: $assignee,
            task: $task->load('project'),
            oldStatus: $oldStatus,
            newStatus: $validated['current_status'],
        ));
    }
}

        return new TaskResource($task);

    }

    public function destroy(Task $task)
    {
        // 1. Checks TaskPolicy@delete
        // If the user isn't an Admin or Workspace Owner, Laravel throws a 403 Forbidden automatically
        Gate::authorize('delete', $task);

        // 2. Perform the deletion
        $task->delete();

        // 3. Return a professional corporate response
        return response()->json([
            'message' => 'Task has been archived successfully.',
        ], 200);
    }

    public function trashed()
    {
        $user = auth()->user();
        // Check if user is Admin or Manager
        if (! in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role === 'admin') {
            $tasks = Task::onlyTrashed()->get();
        } else {
            // Get tasks only from projects in the manager's workspaces
            $workspaceIds = $user->workspaces()->pluck('workspaces.id');
            $tasks = Task::onlyTrashed()
                ->whereHas('project.workspace', function ($q) use ($workspaceIds) {
                    $q->whereIn('id', $workspaceIds);
                })
                ->get();
        }

        return TaskResource::collection($tasks);
    }

    public function restore($id)
    {
        if (! in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // We search through the trashed items specifically
        $task = Task::onlyTrashed()->findOrFail($id);
        $task->restore();

        return response()->json([
            'message' => 'Task restored successfully.',
            'task' => new TaskResource($task),
        ]);
    }

    // TaskController.php

    public function forceDelete($id)
    {
        // 1. Double-check Admin status
        if (! in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Only high-level admins can permanently erase data.'], 403);
        }

        // 2. Find the task even if it is currently in the Trash
        $task = Task::withTrashed()->findOrFail($id);

        // 3. Wipe it from the database forever
        $task->forceDelete();

        return response()->json([
            'message' => 'Task has been permanently erased from the system.',
        ], 200);
    }

    public function show(Task $task)
    {
        // 1. Authorize (Ensure user has permission to view this task)
        Gate::authorize('view', $task);

        $task->load(['project', 'creator', 'assignee']);

        // 3. Return via Resource
        return new TaskResource($task);
    }
}
