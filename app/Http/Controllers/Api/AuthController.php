<?php

namespace App\Http\Controllers\Api;

use App\Events\UserCreated;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Mail\ResetPasswordMail;

class AuthController
{
    // POST /api/register
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'workspace_name' => 'required|string|max:255',
        ]);
        $data = DB::transaction(function () use ($validated) {
            // 1. Create the User
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // 2. Create a Workspace for the User
            $workspace = Workspace::create([
                'name' => $validated['workspace_name'],
                'slug' => Str::slug($validated['workspace_name']).'-'.Str::random(5),
                'owner_id' => $user->id,
            ]);

            // 3. Attach the User to the Workspace with 'admin' role
            $workspace->users()->attach($user->id, ['role' => 'admin']);

            // 4. Assign spatie role to the user
            $user->assignRole('admin');

            // 5. Dispatch event to send welcome email
            UserCreated::dispatch($user);

            return [
                'user' => $user,
                'token' => $user->createToken('default')->plainTextToken,
            ];
        });

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $data['user'],
            'token' => $data['token'],
        ], 201);
    }

    // POST /api/login
    public function login(Request $request)
    {
        // force the request to be treated as expecting JSON so that validation
        // failures and other errors return JSON responses instead of redirecting
        // to the web root. this guards against missing Accept headers from
        // clients/tools that forget to set them (e.g. raw curl).
        $request->headers->set('Accept', 'application/json');

        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create a new token for the user. The frontend usually sends a
        // `device_name` (userAgent or "web").  If it's missing for whatever
        // reason we fall back to a generic value instead of passing `null` to
        // Sanctum which would throw a TypeError (seen during curl testing).
        $deviceName = $request->input('device_name') ?? 'web';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('roles', 'workspaces'),
            'must_change_password' => (bool) $user->must_reset_password,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);
        $user = $request->user();

        // 2. Update the password and clear the reset flag
        $user->update([
            'password' => Hash::make($request->password),
            'must_reset_password' => false,
        ]);

        return response()->json([
            'message' => 'Password updated successfully. You now have full access.',
        ]);
    }

    public function logout(Request $request)
    {
        // Delete the token that was used to make this request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * GET /api/v1/users
     * Return users that the authenticated user can see.
     * Admins see all users, others see users in their workspaces.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $user = $request->user();

        if ($user->role === 'admin') {
            return User::all();
        }

        // Return users from workspaces the authenticated user belongs to
        $workspaceIds = $user->workspaces->pluck('id');
        $usersInWorkspaces = DB::table('workspace_user')->whereIn('workspace_id', $workspaceIds)->pluck('user_id');

        // Also include workspace owners
        $workspaceOwnerIds = Workspace::whereIn('id', $workspaceIds)->pluck('owner_id');

        $allUserIds = $usersInWorkspaces->merge($workspaceOwnerIds)->unique();

        return User::whereIn('id', $allUserIds)->get();
    }

    public function destroy(user $user)
    {
        Gate::authorize('delete', $user);

        if ($user->ownedWorkspaces()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this user because they own workspaces. Reassign ownership first.',
            ], 422);
        }
        $user->workspaces()->detach();

        $user->delete();

        return response()->json([
            'message' => "User {$user->name} has been deleted and removed from all workspaces.",
        ], 200);

    }
    public function forgotPassword(Request $request)
{
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        // Don't reveal whether the email exists, for security
        return response()->json([
            'message' => 'If that email exists, a reset link has been sent.'
        ]);
    }

    $token = PasswordFacade::createToken($user);

    $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);

    Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));

    return response()->json([
        'message' => 'If that email exists, a reset link has been sent.'
    ]);
}

public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
    ]);

    $status = PasswordFacade::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->update([
                'password' => Hash::make($password),
                'must_reset_password' => false,
            ]);
        }
    );

    if ($status ===PasswordFacade::PASSWORD_RESET) {
        return response()->json(['message' => 'Password reset successfully.']);
    }

    return response()->json(['message' => 'This reset link is invalid or has expired.'], 400);
}
}
