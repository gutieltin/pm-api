<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
            ->paginate(100);

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
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
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
        $project->delete();

        return response()->json(['message' => 'Project moved to trash.']);
    }

    public function restore($workspaceid, $projectid)
    {
        // Manual check because the project is hidden by default
        $project = Project::onlyTrashed()->findOrFail($projectid);
        $user = auth()->user();

        // Admin can restore any project
        if ($user->role === 'admin') {
            // Allow
        }
        // Workspace owner can restore projects in their workspace
        elseif ($user->id === $project->workspace->owner_id) {
            // Allow
        }
        // Manager can restore projects if they belong to the workspace
        elseif ($user->role === 'manager' && $user->workspaces()->where('workspace_id', $project->workspace_id)->exists()) {
            // Allow
        } else {
            abort(403, 'Unauthorized to restore this project');
        }

        $project->restore();

        return response()->json(['message' => 'Project and associated tasks restored.']);
    }

    public function trashed($workspaceId)
    {
        $user = auth()->user();

        if (! in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify the manager belongs to this workspace
        if ($user->role === 'manager') {
            $belongsToWorkspace = $user->workspaces()
                ->where('workspaces.id', $workspaceId)
                ->exists();

            if (! $belongsToWorkspace) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $projects = Project::onlyTrashed()
            ->where('workspace_id', $workspaceId)
            ->get();

        return response()->json($projects);
    }

    /**
     * Permanently delete a project from trash.
     */
    public function forceDelete($workspaceId, $projectId)
    {
        $project = Project::onlyTrashed()->findOrFail($projectId);
        $user = auth()->user();

        // Admin can permanently delete any project
        if ($user->role === 'admin') {
            // Allow
        }
        // Workspace owner can permanently delete projects in their workspace
        elseif ($user->id === $project->workspace->owner_id) {
            // Allow
        }
        // Manager can permanently delete projects if they belong to the workspace
        elseif ($user->role === 'manager' && $user->workspaces()->where('workspace_id', $project->workspace_id)->exists()) {
            // Allow
        } else {
            abort(403, 'Unauthorized to permanently delete this project');
        }

        $project->forceDelete();

        return response()->json([
            'message' => 'Project permanently deleted. This action cannot be undone.',
        ], 200);
    }
}
