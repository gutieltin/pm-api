<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\CommentPosted;
use Illuminate\Support\Facades\Gate;
use App\Mail\CommentAddedMail;
use Illuminate\Support\Facades\Mail;

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
        $comment->load(['user:id,name', 'task.project']);

        // Trigger real-time event
        CommentPosted::dispatch($comment);

        // Send email notifications to task assignee (except commenter)
        if ($task->assignee_id && $task->assignee_id !== auth()->id()) {
            $assignee = \App\Models\User::find($task->assignee_id);
            
            if ($assignee) {
                try {
                    Mail::to($assignee->email)->send(new CommentAddedMail(
                        recipient: $assignee,
                        comment: $comment,
                    ));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Comment Mail Error: ' . $e->getMessage());
                }
            }
        }

        // MOVED TO BOTTOM: Return the response AFTER emails are processed
        return response()->json($comment, 201);
    }

    public function index(Task $task)
    {Gate::authorize('view', $task->project->workspace);

    $comments = $task->comments()->with('user:id,name')->latest()->get();
        return response()->json($comments);
    }
}
