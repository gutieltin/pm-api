<?php

namespace App\Http\Controllers\Api;

use App\Events\UserCreated;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

class WorkspaceController extends Controller
{
    public function members(Workspace $workspace)
    {
        $members = $workspace->users()->withPivot('role')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
                'must_reset_password' => $user->must_reset_password,
            ];
        });

        return response()->json(['data' => $members]);
    }

    /**
     * Display a listing of the user's workspaces.
     */
    public function index()
    {
        $user = auth()->user();

        // Return only workspaces where the user is a member
        $workspaces = Workspace::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with('owner')
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
            'description' => 'nullable|string',
        ]);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => Str::slug($validated['name']).'-'.Str::random(5),
            'owner_id' => Auth::id(),
        ]);

        $workspace->users()->attach(Auth::id(), ['role' => 'admin']);

        return response()->json([
            'message' => 'Workspace created successfully',
            'data' => $workspace,
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
            'role' => 'required|in:admin,manager,employee',
        ]);

        // Generate a random password for new users (if they don't exist) and send them an invite email with a link to set their password.
        $tempPassword = Str::random(16);
        $isNewUser = false;

        // Find or Create the user
        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            $user = User::create([
                'name' => $validated['name'] ?? explode('@', $validated['email'])[0],
                'email' => $validated['email'],
                'password' => Hash::make($tempPassword),
                'role' => $validated['role'],
                'must_reset_password' => true,
            ]);
            $isNewUser = true;
        }
        if ($workspace->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is already a member of this workspace'], 422);
        }

        $workspace->users()->attach($user->id, ['role' => $validated['role']]);


        // Send welcome email
Mail::to($user->email)->send(new WelcomeMail(
    user: $user,
    tempPassword: $tempPassword,
    workspaceName: $workspace->name,
    role: $validated['role'],
));

        return response()->json([
            'message' => 'Member added successfully',
            'credentials' => [
                'email' => $user->email,
                'name' => $user->name,
                'role' => $validated['role'],
                'password' => $tempPassword,
            ],
            'note' => 'please share the above credentials with the new member. They can use it to log in and set their own password.',
        ], 201);
    }

    public function destroy(Workspace $workspace)
    {
        $user = auth()->user();
        // Check if user is Admin or Manager
        if (! in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 2. Delete the workspace
        // Note: If your migration has onDelete('cascade'),
        // all linked tasks will be deleted automatically.
        $workspace->delete();

        return response()->json([
            'message' => 'Workspace moved to trash.',
        ], 200);
    }

    public function trashed()
    {
        $user = auth()->user();
        if (! in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Admins see all trashed workspaces
        // Managers only see workspaces they belong to
        if ($user->role === 'admin') {
            $workspaces = Workspace::onlyTrashed()->with('owner')->get();
        } else {
            // Get only workspaces the manager is a member of
            $workspaceIds = $user->workspaces()->pluck('workspaces.id');
            $workspaces = Workspace::onlyTrashed()
                ->whereIn('id', $workspaceIds)
                ->with('owner')
                ->get();
        }

        return WorkspaceResource::collection($workspaces);
    }

    public function restore($id)
    {
        $workspace = Workspace::onlyTrashed()->findOrFail($id);
        $workspace->restore();

        return response()->json(['message' => 'Workspace and its data recovered.']);
    }

    /**
     * Permanently delete a workspace from trash.
     */
    public function forceDelete($id)
    {
        $workspace = Workspace::onlyTrashed()->findOrFail($id);

        // Only the owner can permanently delete
        if ($workspace->owner_id !== auth()->id()) {
            return response()->json([
                'message' => 'Action denied. Only the workspace owner can permanently delete this workspace.',
            ], 403);
        }

        $workspace->forceDelete();

        return response()->json([
            'message' => 'Workspace permanently deleted. This action cannot be undone.',
        ], 200);
    }

    public function reassign(Request $request, Workspace $workspace)
    {
        Gate::authorize('update', $workspace);
        $validated = $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);

        return response()->json([
            'message' => 'Ownership reassigned successfully',
            'new_owner_id' => $validated['new_owner_id'],
        ], 200);
    }
}
