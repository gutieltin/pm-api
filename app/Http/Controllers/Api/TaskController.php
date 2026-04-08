<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{

    public function index(Project $project)
{
    $tasks = $project->tasks()->with(['assignee', 'creator'])->paginate(20);
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
            'description' => $request['description']?? null,
            'creator_id' => Auth::id(),
            'assignee_id' => $validated['assignee_id'],
            'priority' => $validated['priority'],
            'status' => 'pending',
            'due_at' => $validated['due_at']?? null,
        ]);
    
        TaskCreated::dispatch($task);

        return new TaskResource($task);
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
                'status' => 'sometimes|in:todo,in_progress,review,done',
                'assignee_id' => 'sometimes|exists:users,id',
                'priority' => 'sometimes|in:low,medium,high',
            ]);
        } else {
            // Employees (Assignees) can ONLY change the status
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,review,done',
            ]);
        }

        $task->update($validated);
        
        TaskUpdated::dispatch($task->refresh());

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
        'message' => 'Task has been archived successfully.' 
    ], 200);
}

        public function trashed()
{
    // Check if user is Admin
    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // onlyTrashed() filters the query to only show deleted items
    $tasks = Task::onlyTrashed()->with(['project', 'assignee'])->get();

    return TaskResource::collection($tasks);
}

public function restore($id)
{
    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // We search through the trashed items specifically
    $task = Task::onlyTrashed()->findOrFail($id);
    $task->restore();

    return response()->json([
        'message' => 'Task restored successfully.',
        'task' => new TaskResource($task)
    ]);
}

// TaskController.php

public function forceDelete($id)
{
    // 1. Double-check Admin status
    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'Only high-level admins can permanently erase data.'], 403);
    }

    // 2. Find the task even if it is currently in the Trash
    $task = Task::withTrashed()->findOrFail($id);

    // 3. Wipe it from the database forever
    $task->forceDelete();

    return response()->json([
        'message' => 'Task has been permanently erased from the system.'
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