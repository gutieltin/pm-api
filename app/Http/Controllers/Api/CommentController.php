<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\CommentPosted;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        // Check if user is part of the workspace this task belongs to
        Gate::authorize('view', $task->project->workspace);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $comment = $task->comments()->create([
            'content' => $validated['content'],
            'user_id' => Auth::id(),
        ]);

        // Load user info for the frontend
        $comment->load('user:id,name');

        // Trigger real-time event
        CommentPosted::dispatch($comment);

        return response()->json($comment, 201);
    }

    public function index(Task $task)
    {Gate::authorize('view', $task->project->workspace);

    $comments = $task->comments()->with('user:id,name')->latest()->get();
        return response()->json($comments);
    }
}
