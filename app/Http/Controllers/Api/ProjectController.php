<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\ProjectResource;

class ProjectController extends Controller
{
    /**
     * List all projects in a workspace.
     */
    public function index(Workspace $workspace)
   {
    // High-performance query: counts tasks without loading them all
    $projects = $workspace->projects()
        ->withCount('tasks')
        ->paginate(15);

    return ProjectResource::collection($projects);
}

    /**
     * Create a project (Admin/Manager Only).
     */
    public function store(Request $request, Workspace $workspace)
    {
        // Policy check: Does the user have 'admin' or 'manager' role in this workspace?
        Gate::authorize('createProject', $workspace);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $project = $workspace->projects()->create([
            'name' => $validated['name'],
            'description' => $validated['description']?? null,
            'due_date' => $validated['due_date']?? null,
            'owner_id' => Auth::id(), // The Admin who created it
        ]);

        return response()->json($project, 201);
    }

    /**
     * Update project details (Admin/Manager Only).
     */
    public function update(Request $request, Workspace $workspace, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,active,archived,completed',
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    public function destroy(Workspace $workspace, Project $project)
    {
        Gate::authorize('delete', $project);
        $project->delete();
        
        return response()->json(['message' => 'Project and its tasks moved to trash.'],200);
    }

    public function restore($workspaceid,$projectid)
{
    // Manual check for Admin/Owner because the project is hidden by default
    $project = Project::onlyTrashed()->findOrFail($projectid);
    
    if (auth()->user()->role !== 'admin' && auth()->user()->id !== $project->workspace->owner_id) {
        abort(403);
    }

    $project->restore();
    return response()->json(['message' => 'Project and associated tasks restored.']);
}

public function trashed($workspaceId)
{
    $projects = Project::onlyTrashed()
        ->where('workspace_id', $workspaceId)
        ->get();

    return response()->json($projects);
}
}
