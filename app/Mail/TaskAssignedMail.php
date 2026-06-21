<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $assignee,
        public Task $task,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Task Assigned: ' . $this->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.task-assigned',
            with: [
                'assignee' => $this->assignee,
                'task' => $this->task,
                'projectName' => $this->task->project->name ?? 'Unknown Project',
                'taskUrl' => config('app.frontend_url') . '/workspaces/' . 
                    ($this->task->project->workspace_id ?? '') . 
                    '/projects/' . ($this->task->project_id ?? ''),
            ]
        );
    }
}