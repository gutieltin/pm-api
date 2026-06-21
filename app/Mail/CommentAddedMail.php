<?php

namespace App\Mail;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public Comment $comment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Comment on: ' . $this->comment->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.comment-added',
            with: [
                'recipient' => $this->recipient,
                'comment' => $this->comment,
                'task' => $this->comment->task,
                'commenter' => $this->comment->user,
                'projectName' => $this->comment->task->project->name ?? 'Unknown Project',
                'taskUrl' => config('app.frontend_url') . '/workspaces/' .
                    ($this->comment->task->project->workspace_id ?? '') .
                    '/projects/' . ($this->comment->task->project_id ?? ''),
            ]
        );
    }
}