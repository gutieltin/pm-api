<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\User;
use Doctrine\Inflector\Rules\Word;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Resources\WorkspaceResource;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of the user's workspaces.
     */
    public function index()
    {
    $workspaces = Workspace::with('owner')
        ->withCount(['projects', 'users']) 
        ->get();

    return WorkspaceResource::collection($workspaces);
    }

    /**
     * Create a new workspace.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
            'owner_id' => Auth::id(),
        ]);

        $workspace->users()->attach(Auth::id(), ['role' => 'admin']);

        return response()->json([
            'message' => 'Workspace created successfully',
            'data' => $workspace
        ], 201);
    }

    /**
     * Add a member to the workspace.
     */
    public function addMember(Request $request, Workspace $workspace)
    {
    
        Gate::authorize('update', $workspace);

        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required_if:new_user,true|string|max:255',
            'role' => 'required|in:admin,member,employee',
        ]);

        //Generate a random password for new users (if they don't exist) and send them an invite email with a link to set their password.
        $tempPassword = Str::random(16);

      // Find or Create the user
    $user = User::where('email', $validated['email'])->first();
    if (!$user) {
        $user = User::create([
            'name'     => $validated['name'] ?? explode('@', $validated['email'])[0],
            'email'    => $validated['email'],
            'password' => Hash::make($tempPassword), 
            'role'     => 'employee', // Default role for new users
        ]);
    }
    if ($workspace->users()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'User is already a member of this workspace'], 422);
    }

        $workspace->users()->attach($user->id, ['role' => $validated['role']]);

        return response()->json([
            'message' => 'Member added successfully',
            'credentials' => [
                'email' => $user->email,
                'name' => $user->name,
                'role' => $validated['role'],
                'password' => $tempPassword
            ],
            'note'=>'please share the above credentials with the new member. They can use it to log in and set their own password.'
        ], 201);
    }

    public function destroy(Workspace $workspace)
{
    // 1. Authorization check
    if ($workspace->owner_id !== auth()->id()) {
        return response()->json([
            'message' => 'Action denied. Only admins can delete workspaces.'
        ], 403);
    }

    // 2. Delete the workspace
    // Note: If your migration has onDelete('cascade'), 
    // all linked tasks will be deleted automatically.
    $workspace->delete();

    return response()->json([
        'message' => 'Workspace moved to trash.'
    ], 200);
}
    public function trashed()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workspaces = Workspace::onlyTrashed()->with('owner')->get();

        return WorkspaceResource::collection($workspaces);
    }

    public function restore($id)
{
    $workspace = Workspace::onlyTrashed()->findOrFail($id);
    $workspace->restore();
    
    return response()->json(['message' => 'Workspace and its data recovered.']);
}
    public function reassign(Request $request, Workspace $workspace)
    {
        Gate::authorize('update', $workspace);
        $validated = $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);
        return response()->json([
            'message' => 'Ownership reassigned successfully',
            'new_owner_id' => $validated['new_owner_id']
        ], 200);
    }
}
